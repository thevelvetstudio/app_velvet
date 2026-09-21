<?php

namespace App\Http\Controllers;

use App\Actions\ConvertLeadToCandidate;
use App\Actions\CreateLead;
use App\Mail\ApplicationReceivedMail;
use App\Mail\PrequalificationCompletedMail;
use App\Mail\PrequalificationFormMail;
use App\Enums\CandidateStatus;
use App\Enums\LeadStatus;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Candidate;
use App\Models\CandidateActivity;
use App\Models\LeadActivity;
use App\Models\LeadDocument;
use App\Models\Lead;
use App\Models\OnboardingDraft;
use App\Services\RecruitmentDashboardService;
use App\Services\InterviewInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RecruitmentController extends Controller
{
    public function apply(Request $request): Response
    {
        $type = $request->string('type', 'MODEL')->upper()->value();
        $draft = $request->cookie('velvet_visitor_token')
            ? OnboardingDraft::where('visitor_token', $request->cookie('velvet_visitor_token'))->first()
            : null;

        return Inertia::render('Public/Apply', [
            'type' => $type,
            'draft' => $draft ? ['current_step' => $draft->current_step, 'candidate_type' => $draft->candidate_type, 'data' => $draft->data] : null,
        ]);
    }

    public function store(StoreLeadRequest $request, CreateLead $createLead): Response
    {
        $lead = $createLead->handle($request->validated());

        if ($token = $request->cookie('velvet_visitor_token')) {
            OnboardingDraft::where('visitor_token', $token)->delete();
        }

        try {
            Mail::to($lead->email)->send(new ApplicationReceivedMail($lead));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return Inertia::render('Public/ApplySuccess', ['lead' => ['code' => $lead->code, 'name' => $lead->full_name]]);
    }

    public function prequalification(Request $request, Candidate $candidate, string $token): Response
    {
        $this->validatePrequalificationLink($candidate, $token);

        return Inertia::render('Public/Prequalification', [
            'actionUrl' => $request->fullUrl(),
            'identitySessionUrl' => URL::temporarySignedRoute(
                'prequalification.identity.session',
                $candidate->prequalification_expires_at,
                ['candidate' => $candidate->id, 'token' => $token],
            ),
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->lead->full_name,
                'email' => $candidate->lead->email,
                'type' => $candidate->candidate_type?->value,
                'initial' => [
                    'availability' => $candidate->lead->availability,
                    'work_mode' => $candidate->lead->work_mode,
                    'experience' => $candidate->lead->experience,
                    'motivation' => $candidate->lead->motivation,
                ],
            ],
            'identityVerification' => [
                'enabled' => $this->diditIsConfigured(),
                'status' => $candidate->identity_verification_status ?: 'NOT_STARTED',
                'data' => Arr::only($candidate->identity_verification_data ?? [], [
                    'age_verified', 'name_verified', 'match_bypassed', 'date_of_birth', 'name', 'document_status', 'liveness_status', 'face_match_status', 'failure_reason',
                ]),
            ],
        ]);
    }

    public function storePrequalification(Request $request, Candidate $candidate, string $token): Response
    {
        $this->validatePrequalificationLink($candidate, $token);

        $validated = $request->validate([
            'availability' => ['required', 'in:Tiempo completo,Medio tiempo,Por horas'],
            'work_mode' => ['required', 'string', 'max:100'],
            'experience' => ['required', 'string', 'min:20', 'max:5000'],
            'motivation' => ['required', 'string', 'min:20', 'max:5000'],
            'portfolio_url' => ['nullable', 'url', 'max:500'],
            'has_equipment' => ['required', 'boolean'],
            'identity_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'accept_terms' => ['accepted'],
        ]);

        if ($this->diditIsConfigured() && ! $this->identityVerificationIsApproved($candidate)) {
            throw ValidationException::withMessages([
                'identity_verification' => 'Completa y aprueba la verificación de identidad antes de enviar los requisitos.',
            ]);
        }

        if (! $this->diditIsConfigured() && empty($validated['identity_document'])) {
            throw ValidationException::withMessages([
                'identity_document' => 'Carga el documento de identidad mientras se configura la verificación automática.',
            ]);
        }

        $document = $validated['identity_document'] ?? null;
        $data = Arr::except($validated, ['identity_document', 'accept_terms']);

        DB::transaction(function () use ($candidate, $document, $data): void {
            $candidate->lead->update([
                'availability' => $data['availability'],
                'work_mode' => $data['work_mode'],
                'experience' => $data['experience'],
                'motivation' => $data['motivation'],
            ]);

            if ($document) {
                LeadDocument::create([
                    'lead_id' => $candidate->lead_id,
                    'type' => 'identity',
                    'original_name' => $document->getClientOriginalName(),
                    'path' => $document->store('lead-documents', 'local'),
                    'mime_type' => $document->getMimeType(),
                    'size' => $document->getSize(),
                ]);
            }

            $candidate->update([
                'status' => CandidateStatus::PREQUALIFIED,
                'prequalification_data' => $data,
                'prequalification_completed_at' => now(),
                'prequalification_token_hash' => null,
                'prequalification_expires_at' => null,
            ]);
            CandidateActivity::create([
                'candidate_id' => $candidate->id,
                'type' => 'prequalification_completed',
                'description' => 'Formulario de requisitos iniciales completado. Candidata precalificada.',
            ]);
        });

        // Notificar el avance no debe impedir que la candidata vea la
        // confirmación si el proveedor de correo tiene una interrupción temporal.
        try {
            $candidate->loadMissing('lead');
            Mail::to($candidate->lead->email)->send(new PrequalificationCompletedMail($candidate));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return Inertia::render('Public/PrequalificationSuccess', [
            'candidate' => ['name' => $candidate->lead->full_name, 'code' => $candidate->code],
        ]);
    }

    public function createIdentityVerificationSession(Request $request, Candidate $candidate, string $token): JsonResponse
    {
        $this->validatePrequalificationLink($candidate, $token);

        if (! $this->diditIsConfigured()) {
            return response()->json([
                'message' => 'La verificación de identidad aún no está configurada.',
            ], 503);
        }

        if ($request->boolean('sync') && $candidate->didit_session_id) {
            try {
                $decision = Http::timeout(20)
                    ->acceptJson()
                    ->withHeaders(['x-api-key' => config('services.didit.api_key')])
                    ->get(rtrim(config('services.didit.base_url'), '/') . "/v3/session/{$candidate->didit_session_id}/decision/");

                if ($decision->successful() && is_array($decision->json())) {
                    $this->syncDiditDecision($candidate, $decision->json(), $candidate->didit_session_id);
                    $candidate->refresh();

                    return response()->json([
                        'status' => $candidate->identity_verification_status,
                        'data' => $candidate->identity_verification_data,
                    ]);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($this->identityVerificationIsApproved($candidate)) {
            return response()->json([
                'status' => 'APPROVED',
                'message' => 'La identidad ya fue verificada.',
            ]);
        }

        if (! $request->boolean('restart')
            && $candidate->identity_verification_status === 'IN_PROGRESS'
            && $candidate->identity_verification_url) {
            return response()->json([
                'status' => 'IN_PROGRESS',
                'url' => $candidate->identity_verification_url,
            ]);
        }

        // Didit usa `callback` para devolver al navegador al terminar la verificación.
        // El webhook POST se configura por separado en la consola de Didit.
        $callbackUrl = route('prequalification.identity.callback', [
            'candidate' => $candidate->id,
            'token' => $token,
        ]);
        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders(['x-api-key' => config('services.didit.api_key')])
            ->post(rtrim(config('services.didit.base_url'), '/') . '/v3/session/', [
                'workflow_id' => config('services.didit.workflow_id'),
                'vendor_data' => "candidate:{$candidate->id}",
                'callback' => $callbackUrl,
                'callback_method' => 'both',
                'metadata' => [
                    'candidate_id' => (string) $candidate->id,
                    'candidate_code' => $candidate->code,
                ],
            ]);

        if ($response->failed()) {
            report(new \RuntimeException('Didit session creation failed: ' . $response->body()));

            return response()->json([
                'message' => 'No se pudo iniciar la verificación. Inténtalo nuevamente.',
            ], 502);
        }

        $sessionId = $response->json('session_id');
        $sessionUrl = $response->json('url') ?: $response->json('session_url');
        if (! $sessionId || ! $sessionUrl) {
            report(new \UnexpectedValueException('Didit response did not include a session URL.'));

            return response()->json([
                'message' => 'La respuesta de verificación no fue válida.',
            ], 502);
        }

        $candidate->update([
            'identity_verification_status' => 'IN_PROGRESS',
            'didit_session_id' => $sessionId,
            'identity_verification_url' => $sessionUrl,
            'identity_verification_failure_reason' => null,
        ]);
        CandidateActivity::create([
            'candidate_id' => $candidate->id,
            'type' => 'identity_verification_started',
            'description' => 'Verificación de identidad iniciada.',
            'metadata' => ['provider' => 'didit', 'session_id' => $sessionId],
        ]);

        return response()->json(['status' => 'IN_PROGRESS', 'url' => $sessionUrl]);
    }

    public function diditCallback(Request $request, Candidate $candidate, string $token): RedirectResponse
    {
        // El callback es público porque Didit lo visita con GET, pero el token
        // aleatorio y su expiración siguen protegiendo el formulario.
        $this->validatePrequalificationLink($candidate, $token);

        $expiresAt = $candidate->prequalification_expires_at ?: now()->addMinutes(15);
        $returnUrl = URL::temporarySignedRoute('prequalification.show', $expiresAt, [
            'candidate' => $candidate->id,
            'token' => $token,
            'identity_status' => $request->query('status'),
            'verification_session_id' => $request->query('verificationSessionId'),
        ]);

        // El webhook es la fuente principal, pero consultar la decisión aquí
        // permite actualizar la pantalla inmediatamente y cubre sesiones de
        // prueba cuando todavía no se ha configurado el destino en Didit.
        $sessionId = $request->query('verificationSessionId');
        if ($sessionId && $this->diditIsConfigured()) {
            try {
                $decision = Http::timeout(20)
                    ->acceptJson()
                    ->withHeaders(['x-api-key' => config('services.didit.api_key')])
                    ->get(rtrim(config('services.didit.base_url'), '/') . "/v3/session/{$sessionId}/decision/");

                if ($decision->successful() && is_array($decision->json())) {
                    $this->syncDiditDecision($candidate, $decision->json(), $sessionId);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->to($returnUrl);
    }

    public function diditWebhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $secret = (string) config('services.didit.webhook_secret');

        abort_unless($secret && $this->diditSignatureIsValid($request, $rawBody, $secret), 401, 'Firma de webhook inválida.');

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json(['message' => 'JSON inválido.'], 400);
        }

        $sessionId = data_get($payload, 'session_id') ?: data_get($payload, 'data.session_id');
        $vendorData = data_get($payload, 'vendor_data') ?: data_get($payload, 'data.vendor_data');
        $candidateId = is_string($vendorData) && preg_match('/^candidate:(\d+)$/', $vendorData, $matches) ? (int) $matches[1] : null;
        $candidate = ($sessionId ? Candidate::where('didit_session_id', $sessionId)->first() : null) ?: ($candidateId ? Candidate::find($candidateId) : null);

        // Acknowledge unknown or duplicated sessions so Didit does not retry forever.
        if (! $candidate) {
            return response()->json(['received' => true]);
        }

        $this->syncDiditDecision($candidate, $payload, $sessionId);

        return response()->json(['received' => true]);
    }

    private function syncDiditDecision(Candidate $candidate, array $payload, ?string $sessionId = null): void
    {
        $providerStatus = data_get($payload, 'status') ?: data_get($payload, 'data.status') ?: data_get($payload, 'decision.status');
        $status = match ($providerStatus) {
            'Approved' => 'APPROVED',
            'In Review' => 'IN_REVIEW',
            'Declined' => 'DECLINED',
            'Expired' => 'EXPIRED',
            'Abandoned' => 'ABANDONED',
            'Resubmitted', 'Not Finished' => 'IN_PROGRESS',
            default => 'IN_PROGRESS',
        };
        $summary = $this->diditIdentitySummary($payload, $candidate, $status);
        if ($status === 'APPROVED' && app()->environment('local')) {
            $summary['match_bypassed'] = true;
            $summary['age_verified'] = true;
            $summary['name_verified'] = true;
        } elseif ($status === 'APPROVED' && (! $summary['age_verified'] || ! $summary['name_verified'])) {
            $status = 'IN_REVIEW';
            $reasons = [];
            if (! $summary['age_verified']) {
                $reasons[] = 'la mayoría de edad o la fecha de nacimiento';
            }
            if (! $summary['name_verified']) {
                $reasons[] = 'el nombre del documento';
            }
            $summary['failure_reason'] = 'Didit aprobó el documento, pero no coincide ' . implode(' ni ', $reasons) . ' registrada en el onboarding.';
        }

        $sameDecision = $candidate->didit_session_id === ($sessionId ?: $candidate->didit_session_id)
            && $candidate->identity_verification_status === $status;
        $candidate->update([
            'identity_verification_status' => $status,
            'didit_session_id' => $sessionId ?: $candidate->didit_session_id,
            'identity_verification_data' => $summary,
            'identity_verified_at' => $status === 'APPROVED' ? now() : null,
            'identity_verification_failure_reason' => $summary['failure_reason'] ?? null,
        ]);
        if (! $sameDecision) {
            CandidateActivity::create([
                'candidate_id' => $candidate->id,
                'type' => 'identity_verification_updated',
                'description' => match ($status) {
                    'APPROVED' => 'Identidad verificada y mayoría de edad confirmada.',
                    'DECLINED' => 'La verificación de identidad fue rechazada.',
                    'IN_REVIEW' => 'Didit aprobó el documento, pero los datos requieren revisión manual.',
                    default => 'Estado de la verificación de identidad actualizado.',
                },
                'metadata' => ['provider' => 'didit', 'session_id' => $sessionId, 'status' => $status],
            ]);
        }
    }

    public function dashboard(Request $request, RecruitmentDashboardService $dashboard): Response
    {
        $request->validate([
            'period' => ['nullable', 'in:today,7d,30d,month,custom'],
            'type' => ['nullable', 'in:MODEL,MONITOR'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return Inertia::render('Admin/Recruitment/Dashboard', [
            ...$dashboard->handle($request),
            'user' => ['name' => $request->user()->name, 'email' => $request->user()->email],
        ]);
    }

    public function workflows(): Response
    {
        $counts = Candidate::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        $status = fn (string $key, string $label, string $description, string $tone = 'purple') => [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'count' => $counts[$key] ?? 0,
            'tone' => $tone,
        ];

        return Inertia::render('Admin/Recruitment/Workflows/Index', [
            'flows' => [
                [
                    'id' => 'admission',
                    'name' => 'Admisión de candidatos',
                    'description' => 'Ruta principal desde la solicitud hasta la admisión y el inicio del onboarding.',
                    'nodes' => [
                        $status('NEW', 'Nuevo', 'Solicitud recibida'),
                        $status('CONTACTED', 'Contactado', 'Primer contacto'),
                        $status('PREQUALIFIED', 'Precalificado', 'Requisitos completos', 'blue'),
                        $status('INTERVIEW', 'Entrevista', 'Cita y evaluación inicial', 'blue'),
                        $status('EVALUATION', 'Evaluación', 'Revisión del equipo', 'blue'),
                        $status('ADMITTED', 'Admitido', 'Ingreso aprobado', 'green'),
                        $status('ONBOARDING', 'Onboarding', 'Documentación de ingreso', 'green'),
                        $status('ACTIVE', 'Activo', 'Talento habilitado', 'green'),
                    ],
                    'branches' => [
                        $status('WAITING', 'En espera', 'Pendiente de decisión', 'amber'),
                        $status('DISCARDED', 'Descartado', 'No continúa el proceso', 'red'),
                    ],
                ],
                [
                    'id' => 'activation',
                    'name' => 'Activación y permanencia',
                    'description' => 'Seguimiento posterior a la admisión hasta la activación del talento.',
                    'nodes' => [
                        $status('ADMITTED', 'Admitido', 'Ingreso aprobado', 'green'),
                        $status('ONBOARDING', 'Onboarding', 'Documentación de ingreso', 'green'),
                        $status('CONTRACTING', 'Contratación', 'Formalización', 'blue'),
                        $status('INDUCTION', 'Inducción', 'Preparación inicial', 'blue'),
                        $status('READY_TO_ACTIVATE', 'Listo para activar', 'Revisión final', 'green'),
                        $status('ACTIVE', 'Activo', 'Talento habilitado', 'green'),
                    ],
                    'branches' => [$status('WITHDRAWN', 'Retirado', 'Proceso cerrado', 'red')],
                ],
            ],
            'summary' => [
                'total' => Candidate::count(),
                'active' => $counts['ACTIVE'] ?? 0,
                'admitted' => $counts['ADMITTED'] ?? 0,
                'inProgress' => collect($counts)->only(['CONTACTED', 'PREQUALIFIED', 'INTERVIEW', 'EVALUATION', 'ONBOARDING', 'CONTRACTING', 'INDUCTION', 'READY_TO_ACTIVATE'])->sum(),
            ],
        ]);
    }

    public function processes(Request $request): Response
    {
        $query = Lead::query()->with(['candidate.interviews'])->latest('updated_at');
        $search = $request->string('search')->trim()->value();
        $candidateStatuses = array_column(CandidateStatus::cases(), 'value');
        $leadStatuses = array_column(LeadStatus::cases(), 'value');

        if ($search) {
            $query->where(fn ($searchQuery) => $searchQuery
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }
        if ($type = $request->string('type')->upper()->value()) {
            $query->where('candidate_type', $type);
        }
        if ($status = $request->string('status')->upper()->value()) {
            if (in_array($status, $candidateStatuses, true)) {
                $query->whereHas('candidate', fn ($candidateQuery) => $candidateQuery->where('status', $status));
            } elseif (in_array($status, $leadStatuses, true)) {
                $query->where('status', $status);
            }
        }

        $processes = $query->paginate(15)->withQueryString()->through(function (Lead $lead): array {
            $candidate = $lead->candidate;
            $stage = $candidate?->status?->value ?: $lead->status?->value;

            return [
                'id' => $lead->id,
                'candidate_id' => $candidate?->id,
                'code' => $candidate?->code ?: $lead->code,
                'name' => $lead->full_name,
                'email' => $lead->email,
                'type' => $lead->candidate_type?->value,
                'lead_status' => $lead->status?->value,
                'stage' => $stage,
                'stage_label' => $candidate?->status?->label() ?: ($lead->status?->value === 'CONVERTED' ? 'Convertido' : ucfirst(strtolower($lead->status?->value ?? 'Nuevo'))),
                'identity_status' => $candidate?->identity_verification_status,
                'prequalification_completed' => (bool) $candidate?->prequalification_completed_at,
                'interview_scheduled' => (bool) $candidate?->interviews?->whereIn('status', ['SCHEDULED', 'CONFIRMED'])->count(),
                'updated_at' => $lead->updated_at?->toIso8601String(),
            ];
        });

        return Inertia::render('Admin/Recruitment/Processes/Index', [
            'processes' => $processes,
            'filters' => $request->only('search', 'type', 'status'),
            'stageOptions' => collect(CandidateStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all(),
        ]);
    }

    public function leads(Request $request): Response
    {
        $query = Lead::query()->with(['assignee', 'candidate'])->latest();
        if ($search = $request->string('search')->trim()->value()) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        }
        if ($type = $request->string('type')->value()) {
            $query->where('candidate_type', $type);
        }
        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        $leads = $query->paginate(15)->withQueryString()->through(function (Lead $lead): array {
            return [
                ...Arr::only($lead->toArray(), ['id', 'code', 'first_name', 'last_name', 'email', 'phone', 'candidate_type', 'city', 'status', 'created_at', 'updated_at']),
                'application_status' => $lead->candidate?->status?->value ?: $lead->status?->value,
                'application_status_label' => $lead->candidate?->status?->label() ?: match ($lead->status?->value) {
                    'NEW' => 'Nueva',
                    'CONTACTED' => 'Contactada',
                    'QUALIFIED' => 'Calificada',
                    'UNRESPONSIVE' => 'Sin respuesta',
                    'DISCARDED' => 'Descartada',
                    'CONVERTED' => 'Convertida',
                    default => 'Sin estado',
                },
            ];
        });

        return Inertia::render('Admin/Recruitment/Leads/Index', ['leads' => $leads, 'filters' => $request->only('search', 'type', 'status')]);
    }

    public function validations(Request $request): Response
    {
        $query = Candidate::query()
            ->with('lead')
            ->whereNotNull('didit_session_id')
            ->latest('updated_at');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(fn ($searchQuery) => $searchQuery
                ->whereHas('lead', fn ($lead) => $lead
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orWhere('code', 'like', "%{$search}%"));
        }

        if ($status = $request->string('status')->value()) {
            $query->where('identity_verification_status', $status);
        }

        $validations = $query->paginate(15)->withQueryString()->through(fn (Candidate $candidate) => [
            'id' => $candidate->id,
            'code' => $candidate->code,
            'name' => $candidate->lead?->full_name,
            'email' => $candidate->lead?->email,
            'candidate_status' => $candidate->status?->value,
            'identity_status' => $candidate->identity_verification_status ?: 'NOT_STARTED',
            'failure_reason' => $candidate->identity_verification_failure_reason,
            'session_id' => $candidate->didit_session_id,
            'verified_at' => $candidate->identity_verified_at?->toIso8601String(),
            'data' => $candidate->identity_verification_data ?? [],
        ]);

        return Inertia::render('Admin/Recruitment/Validations/Index', [
            'validations' => $validations,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function lead(Lead $lead): Response
    {
        return Inertia::render('Admin/Recruitment/Leads/Show', ['lead' => $lead->load(['activities.user', 'candidate'])]);
    }

    public function updateLead(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate($this->leadDataRules());
        $identityDataChanged = $this->identityDataChanged($validated, $lead);
        $lead->update(Arr::except($validated, ['candidate_type']));
        if ($lead->candidate) {
            $lead->candidate->update(array_merge(
                ['candidate_type' => $validated['candidate_type']],
                $identityDataChanged ? $this->identityResetAttributes() : [],
            ));
        }
        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type' => 'data_updated',
            'description' => 'Información de la aplicación actualizada por el equipo.',
            'metadata' => ['fields' => array_keys($validated), 'identity_verification_reset' => $identityDataChanged],
        ]);

        return back()->with('success', 'Información del lead actualizada correctamente.');
    }

    public function discardLead(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->candidate) {
            return back()->withErrors(['discard' => 'Este lead ya es candidato. Descártalo desde su perfil de candidato.']);
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $lead->update(['status' => LeadStatus::DISCARDED, 'discard_reason' => $validated['reason']]);
        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type' => 'discarded',
            'description' => 'Lead descartado: ' . $validated['reason'],
            'metadata' => ['reason' => $validated['reason']],
        ]);

        return to_route('admin.leads')->with('success', 'Lead descartado correctamente.');
    }

    public function convert(Lead $lead, ConvertLeadToCandidate $convert): RedirectResponse
    {
        $candidate = $convert->handle($lead);

        return to_route('admin.leads.show', $lead)->with('success', "Lead convertido a {$candidate->code}.");
    }

    public function candidates(Request $request): Response
    {
        $query = Candidate::query()->with('lead')->latest();
        if ($search = $request->string('search')->trim()->value()) {
            $query->whereHas('lead', fn ($lead) => $lead->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        if ($type = $request->string('type')->value()) {
            $query->where('candidate_type', $type);

            // Modelos y monitores son directorios de personas admitidas;
            // las candidaturas en selección permanecen en "Candidatos".
            if (in_array($type, ['MODEL', 'MONITOR'], true)) {
                $query->whereIn('status', [
                    'ADMITTED',
                    'WAITING',
                    'ONBOARDING',
                    'CONTRACTING',
                    'INDUCTION',
                    'READY_TO_ACTIVATE',
                    'ACTIVE',
                ]);
            }
        }
        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Recruitment/Candidates/Index', ['candidates' => $query->paginate(15)->withQueryString(), 'filters' => $request->only('search', 'type', 'status')]);
    }

    public function candidate(Candidate $candidate): Response
    {
        return Inertia::render('Admin/Recruitment/Candidates/Show', ['candidate' => $candidate->load(['lead', 'activities.user', 'interviews.slot'])]);
    }

    public function updateCandidate(Request $request, Candidate $candidate): RedirectResponse
    {
        $validated = $request->validate($this->leadDataRules());
        $identityDataChanged = $this->identityDataChanged($validated, $candidate->lead);
        $candidate->lead->update(Arr::except($validated, ['candidate_type']));
        $candidate->update(array_merge(
            ['candidate_type' => $validated['candidate_type']],
            $identityDataChanged ? $this->identityResetAttributes() : [],
        ));
        CandidateActivity::create([
            'candidate_id' => $candidate->id,
            'user_id' => $request->user()->id,
            'type' => 'data_updated',
            'description' => 'Información del candidato actualizada por el equipo.',
            'metadata' => ['fields' => array_keys($validated), 'identity_verification_reset' => $identityDataChanged],
        ]);

        return back()->with('success', 'Información del candidato actualizada correctamente.');
    }

    public function discardCandidate(Request $request, Candidate $candidate): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $candidate->update([
            'status' => CandidateStatus::DISCARDED,
            'discarded_at' => now(),
            'discard_reason' => $validated['reason'],
        ]);
        CandidateActivity::create([
            'candidate_id' => $candidate->id,
            'user_id' => $request->user()->id,
            'type' => 'discarded',
            'description' => 'Candidato descartado: ' . $validated['reason'],
            'metadata' => ['reason' => $validated['reason']],
        ]);

        return to_route('admin.candidates.show', $candidate)->with('success', 'Candidato descartado correctamente.');
    }

    public function updateCandidateStatus(Request $request, Candidate $candidate, InterviewInvitationService $invitationService): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(array_column(CandidateStatus::cases(), 'value'))]]);
        $nextStatus = CandidateStatus::from($validated['status']);
        $previousStatus = $candidate->status;

        if ($nextStatus === CandidateStatus::DISCARDED && ! $request->filled('reason')) {
            return back()->withErrors(['status' => 'Usa la opción “Descartar” e indica el motivo.']);
        }

        if ($nextStatus === CandidateStatus::ADMITTED && ! $this->identityVerificationIsApproved($candidate)) {
            return back()->withErrors(['status' => 'La candidata debe tener la identidad verificada y la mayoría de edad confirmada antes de ser admitida.']);
        }

        if ($previousStatus === $nextStatus) {
            return back();
        }

        if ($nextStatus === CandidateStatus::INTERVIEW) {
            try {
                $interview = $invitationService->send($candidate, $request->user(), false);
            } catch (\Throwable $exception) {
                report($exception);

                return back()->withErrors(['status' => 'No se pudo enviar el formulario de horarios. Revisa la configuración de correo.']);
            }

            if (! $interview) {
                return back()->withErrors(['status' => 'Genera al menos una franja disponible antes de pasar el candidato a entrevista.']);
            }
        }

        $attributes = ['status' => $nextStatus];
        if ($nextStatus === CandidateStatus::ADMITTED && ! $candidate->admitted_at) {
            $attributes['admitted_at'] = now();
        }
        if ($nextStatus === CandidateStatus::ACTIVE && ! $candidate->activated_at) {
            $attributes['activated_at'] = now();
        }
        if ($nextStatus === CandidateStatus::DISCARDED && ! $candidate->discarded_at) {
            $attributes['discarded_at'] = now();
        }
        if ($nextStatus === CandidateStatus::CONTRACTING && ! $candidate->contracted_at) {
            $attributes['contracted_at'] = now();
        }

        $candidate->update($attributes);
        CandidateActivity::create([
            'candidate_id' => $candidate->id,
            'user_id' => $request->user()->id,
            'type' => 'status_changed',
            'description' => "Estado actualizado a {$nextStatus->label()}.",
            'metadata' => ['from' => $previousStatus?->value, 'to' => $nextStatus->value],
        ]);

        return back()->with('success', "Estado actualizado a {$nextStatus->label()}.");
    }

    private function leadDataRules(): array
    {
        return [
            'candidate_type' => ['required', Rule::in(['MODEL', 'MONITOR'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['WOMAN', 'MAN', 'TRANS_WOMAN', 'TRANS_MAN', 'NON_BINARY', 'GENDER_FLUID', 'AGENDER', 'SELF_DESCRIBE', 'PREFER_NOT_TO_SAY'])],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'required_if:candidate_type,MODEL', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'experience' => ['nullable', 'string', 'max:5000'],
            'motivation' => ['nullable', 'string', 'max:5000'],
            'availability' => ['required', 'string', 'max:100'],
            'work_mode' => array_merge(['required', 'string', 'max:100'], request()->input('candidate_type') === 'MONITOR' ? ['in:En estudio'] : []),
            'source' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function identityDataChanged(array $validated, Lead $lead): bool
    {
        foreach (['first_name', 'last_name', 'birth_date'] as $field) {
            $current = $field === 'birth_date'
                ? $lead->birth_date?->toDateString()
                : $lead->getAttribute($field);

            if ((string) $current !== (string) ($validated[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function identityResetAttributes(): array
    {
        return [
            'identity_verification_status' => 'NOT_STARTED',
            'identity_verification_data' => null,
            'identity_verification_failure_reason' => null,
            'identity_verified_at' => null,
            'identity_verification_url' => null,
            'didit_session_id' => null,
        ];
    }

    public function sendPrequalification(Request $request, Candidate $candidate): RedirectResponse
    {
        $candidate->loadMissing('lead');

        if ($candidate->prequalification_completed_at || in_array($candidate->status, [CandidateStatus::PREQUALIFIED, CandidateStatus::ADMITTED, CandidateStatus::WAITING, CandidateStatus::DISCARDED, CandidateStatus::ONBOARDING, CandidateStatus::CONTRACTING, CandidateStatus::INDUCTION, CandidateStatus::READY_TO_ACTIVATE, CandidateStatus::ACTIVE, CandidateStatus::WITHDRAWN], true)) {
            return back()->withErrors(['prequalification' => 'Esta candidata ya completó la precalificación o avanzó a una etapa posterior.']);
        }

        $token = Str::random(64);
        $expiresAt = now()->addDays(7);
        $formUrl = URL::temporarySignedRoute('prequalification.show', $expiresAt, ['candidate' => $candidate->id, 'token' => $token]);

        try {
            Mail::to($candidate->lead->email)->send(new PrequalificationFormMail($candidate, $formUrl));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['prequalification' => 'No se pudo enviar el formulario. Revisa la configuración del correo e inténtalo de nuevo.']);
        }

        $candidate->update([
            'status' => $candidate->status === CandidateStatus::NEW ? CandidateStatus::CONTACTED : $candidate->status,
            'prequalification_token_hash' => hash('sha256', $token),
            'prequalification_sent_at' => now(),
            'prequalification_expires_at' => $expiresAt,
        ]);
        CandidateActivity::create([
            'candidate_id' => $candidate->id,
            'user_id' => $request->user()->id,
            'type' => 'prequalification_sent',
            'description' => 'Formulario de precalificación enviado al correo registrado.',
            'metadata' => ['expires_at' => $expiresAt->toIso8601String()],
        ]);

        return back()->with('success', 'Formulario de precalificación enviado correctamente.');
    }

    private function validatePrequalificationLink(Candidate $candidate, string $token): void
    {
        abort_unless($candidate->prequalification_token_hash && hash_equals($candidate->prequalification_token_hash, hash('sha256', $token)), 403, 'Este enlace no es válido.');
        abort_if($candidate->prequalification_expires_at?->isPast(), 410, 'Este enlace ya expiró.');
        abort_if($candidate->prequalification_completed_at, 409, 'Este formulario ya fue completado.');
    }

    private function diditIsConfigured(): bool
    {
        // Los tests funcionales usan el flujo de revisión manual con un archivo
        // simulado; la integración externa no debe activarse por las variables
        // del .env local del desarrollador.
        return ! app()->environment('testing')
            && filled(config('services.didit.api_key'))
            && filled(config('services.didit.workflow_id'));
    }

    private function identityVerificationIsApproved(Candidate $candidate): bool
    {
        return $candidate->identity_verification_status === 'APPROVED'
            && (app()->environment('local') || data_get($candidate->identity_verification_data ?? [], 'age_verified') === true);
    }

    private function diditSignatureIsValid(Request $request, string $rawBody, string $secret): bool
    {
        $signatureV2 = $request->header('X-Signature-V2');
        if ($signatureV2) {
            $provided = preg_replace('/^sha256=/i', '', trim($signatureV2));
            $payload = json_decode($rawBody, true);
            if (is_array($payload)) {
                $expectedCanonical = hash_hmac('sha256', $this->canonicalJson($payload), $secret);
                if (hash_equals($expectedCanonical, $provided)) {
                    return true;
                }
            }

            // Keep compatibility with Didit V2 implementations that sign raw JSON.
            return hash_equals(hash_hmac('sha256', $rawBody, $secret), $provided);
        }

        $signature = $request->header('X-Signature');
        if (! $signature) {
            return false;
        }
        $provided = preg_replace('/^sha256=/i', '', trim($signature));
        $timestamp = $request->header('X-Timestamp');
        $candidates = [$rawBody, $timestamp ? "{$timestamp}.{$rawBody}" : null];

        foreach (array_filter($candidates) as $signedBody) {
            if (hash_equals(hash_hmac('sha256', $signedBody, $secret), $provided)) {
                return true;
            }
        }

        return false;
    }

    private function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return '[' . implode(',', array_map(fn ($item) => $this->canonicalJson($item), $value)) . ']';
            }

            $keys = array_keys($value);
            sort($keys, SORT_STRING);
            $pairs = [];
            foreach ($keys as $key) {
                $pairs[] = json_encode((string) $key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ':' . $this->canonicalJson($value[$key]);
            }

            return '{' . implode(',', $pairs) . '}';
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function diditIdentitySummary(array $payload, Candidate $candidate, string $status): array
    {
        $dateOfBirth = $this->findNestedValue($payload, ['date_of_birth', 'birth_date', 'dateOfBirth', 'dob']);
        $dateOfBirth = is_string($dateOfBirth) ? $this->normalizeDate($dateOfBirth) : null;
        $onboardingBirthDate = $candidate->lead?->birth_date?->toDateString();
        $ageVerified = false;

        if ($dateOfBirth) {
            try {
                $ageVerified = Carbon::parse($dateOfBirth)->isPast()
                    && Carbon::parse($dateOfBirth)->age >= 18
                    && (! $onboardingBirthDate || $dateOfBirth === $onboardingBirthDate);
            } catch (\Throwable) {
                $ageVerified = false;
            }
        }

        $documentName = $this->findNestedValue($payload, ['full_name', 'name']);
        $nameVerified = ! $documentName || $this->namesMatch(
            $candidate->lead?->first_name . ' ' . $candidate->lead?->last_name,
            (string) $documentName,
        );

        return [
            'provider' => 'didit',
            'provider_status' => $status,
            'name' => $documentName ?: $this->findNestedValue($payload, ['given_name']),
            'date_of_birth' => $dateOfBirth,
            'age_verified' => $ageVerified,
            'name_verified' => $nameVerified,
            'document_status' => $this->findNestedValue($payload, ['id_verification', 'id_verifications', 'document_status']),
            'liveness_status' => $this->findNestedValue($payload, ['liveness', 'liveness_checks']),
            'face_match_status' => $this->findNestedValue($payload, ['face_match', 'face_matches']),
            'images' => $this->diditImageSummary($payload),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Didit report image URLs are temporary and may be absent depending on the workflow.
     * Keep only HTTPS URLs and the image types useful to authorized reviewers.
     */
    private function diditImageSummary(array $payload): array
    {
        $images = [];
        $allowed = [
            'front_image' => 'Documento (frente)',
            'full_front_image' => 'Documento completo (frente)',
            'back_image' => 'Documento (reverso)',
            'full_back_image' => 'Documento completo (reverso)',
            'portrait_image' => 'Retrato del documento',
            'selfie_image' => 'Selfie',
            'face_image' => 'Imagen facial',
        ];

        $walk = function (mixed $value) use (&$walk, &$images, $allowed): void {
            if (! is_array($value)) {
                return;
            }

            foreach ($value as $key => $child) {
                $normalizedKey = Str::of((string) $key)->lower()->replace(['-', ' '], '_')->toString();
                if (isset($allowed[$normalizedKey]) && is_string($child) && filter_var($child, FILTER_VALIDATE_URL) && Str::startsWith($child, 'https://')) {
                    $images[$normalizedKey] = ['label' => $allowed[$normalizedKey], 'url' => $child];
                    continue;
                }
                $walk($child);
            }
        };

        $walk($payload);

        return array_values($images);
    }

    private function namesMatch(string $expected, string $actual): bool
    {
        $normalize = static function (string $value): array {
            $value = Str::lower(Str::ascii($value));
            $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';

            return array_values(array_filter(explode(' ', trim($value))));
        };

        $expectedTokens = $normalize($expected);
        $actualTokens = $normalize($actual);

        return $expectedTokens !== [] && $actualTokens !== []
            && count(array_diff($expectedTokens, $actualTokens)) === 0;
    }

    private function findNestedValue(mixed $value, array $keys): mixed
    {
        if (! is_array($value)) {
            return null;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $value)) {
                $found = $value[$key];
                if (is_array($found) && array_is_list($found)) {
                    return $found[0] ?? null;
                }

                return is_array($found) ? ($found['status'] ?? $found['value'] ?? null) : $found;
            }
        }

        foreach ($value as $child) {
            $found = $this->findNestedValue($child, $keys);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function normalizeDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
