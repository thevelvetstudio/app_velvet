<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Mail\InterviewScheduledMail;
use App\Models\Candidate;
use App\Models\CandidateActivity;
use App\Models\Interview;
use App\Models\InterviewSlot;
use App\Services\InterviewInvitationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function __construct(private readonly InterviewInvitationService $invitationService)
    {
    }

    public function index(): Response
    {
        $slots = InterviewSlot::query()->with('interview.candidate.lead')
            ->where('starts_at', '>=', now())->orderBy('starts_at')->limit(250)->get();
        $candidates = Candidate::query()->with('lead')->whereIn('status', [CandidateStatus::PREQUALIFIED, CandidateStatus::INTERVIEW])
            ->whereHas('lead')->whereDoesntHave('interviews', fn ($query) => $query->whereIn('status', ['INVITED', 'SCHEDULED']))->latest()->get();
        $interviews = Interview::query()->with('candidate.lead', 'slot')->whereIn('status', ['INVITED', 'SCHEDULED'])
            ->latest()->limit(100)->get();

        return Inertia::render('Admin/Recruitment/Interviews/Index', compact('slots', 'candidates', 'interviews'));
    }

    public function generateSlots(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date', 'after_or_equal:today'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $timezone = 'America/Bogota';
        $validated['weekdays'] = array_map('intval', $validated['weekdays']);
        $from = Carbon::parse($validated['from_date'], $timezone)->startOfDay();
        $to = Carbon::parse($validated['to_date'], $timezone)->startOfDay();
        if ($from->diffInDays($to) > 90) {
            return back()->withErrors(['to_date' => 'El rango máximo para generar horarios es de 90 días.']);
        }
        $created = 0;
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            if (in_array($cursor->dayOfWeekIso, $validated['weekdays'], true)) {
                $start = Carbon::createFromFormat('Y-m-d H:i', $cursor->format('Y-m-d') . ' ' . $validated['start_time'], $timezone);
                $end = Carbon::createFromFormat('Y-m-d H:i', $cursor->format('Y-m-d') . ' ' . $validated['end_time'], $timezone);
                for ($slotStart = $start->copy(); $slotStart->lt($end); $slotStart->addHour()) {
                    $slotEnd = $slotStart->copy()->addHour();
                    if ($slotStart->isFuture() && ! InterviewSlot::where('starts_at', $slotStart->utc())->where('ends_at', $slotEnd->utc())->exists()) {
                        InterviewSlot::create(['starts_at' => $slotStart->utc(), 'ends_at' => $slotEnd->utc(), 'created_by' => $request->user()->id]);
                        $created++;
                    }
                }
            }
            $cursor->addDay();
        }

        return back()->with('success', $created ? "Se generaron {$created} franjas de entrevista de una hora." : 'No había franjas nuevas para generar.');
    }

    public function invite(Request $request, Candidate $candidate): RedirectResponse
    {
        $candidate->loadMissing('lead');
        abort_unless(in_array($candidate->status, [CandidateStatus::PREQUALIFIED, CandidateStatus::INTERVIEW], true), 422, 'La candidata debe estar precalificada antes de agendar una entrevista.');
        $interview = null;
        try {
            $interview = $this->invitationService->send($candidate, $request->user());
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['interview' => 'No se pudo enviar la invitación. Revisa la configuración de correo.']);
        }
        if (! $interview) {
            return back()->withErrors(['interview' => 'Genera al menos una franja disponible antes de enviar la invitación.']);
        }
        return back()->with('success', 'Horarios de entrevista enviados al correo registrado.');
    }

    public function updateStatus(Request $request, Interview $interview): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['SCHEDULED', 'COMPLETED', 'NO_SHOW', 'CANCELLED'])]]);
        $interview->update(['status' => $validated['status']]);
        if ($validated['status'] === 'CANCELLED' && $interview->slot) $interview->slot->update(['status' => 'AVAILABLE']);
        return back()->with('success', 'Estado de la entrevista actualizado.');
    }

    public function reschedule(Request $request, Interview $interview): RedirectResponse
    {
        abort_unless($interview->status === 'SCHEDULED', 422, 'Solo se pueden reprogramar entrevistas agendadas.');
        $interview->loadMissing('candidate.lead', 'slot');

        $hasAlternative = InterviewSlot::where('status', 'AVAILABLE')->where('starts_at', '>', now())->exists()
            || ($interview->slot && $interview->slot->starts_at->isFuture());
        if (! $hasAlternative) {
            return back()->withErrors(['interview' => 'Genera una nueva franja disponible antes de reprogramar.']);
        }

        if ($interview->slot) {
            $interview->slot->update(['status' => 'AVAILABLE']);
        }
        $interview->update(['status' => 'CANCELLED']);
        CandidateActivity::create([
            'candidate_id' => $interview->candidate_id,
            'user_id' => $request->user()->id,
            'type' => 'interview_rescheduled',
            'description' => 'La entrevista fue liberada para que la candidata elija un nuevo horario.',
            'metadata' => ['previous_scheduled_at' => $interview->scheduled_at?->toIso8601String()],
        ]);

        return $this->invite($request, $interview->candidate);
    }

    public function booking(Interview $interview, string $token): Response
    {
        $this->validateInvitation($interview, $token);
        $slots = InterviewSlot::where('status', 'AVAILABLE')->where('starts_at', '>', now())->orderBy('starts_at')->get();
        $interview->load('candidate.lead');
        return Inertia::render('Public/InterviewBooking', [
            'candidate' => ['name' => $interview->candidate->lead->full_name, 'code' => $interview->candidate->code, 'type' => $interview->candidate->candidate_type->value],
            'slots' => $slots,
            'expiresAt' => $interview->invitation_expires_at,
            'actionUrl' => request()->fullUrl(),
        ]);
    }

    public function book(Request $request, Interview $interview, string $token): Response|RedirectResponse
    {
        $this->validateInvitation($interview, $token);
        $validated = $request->validate(['slot_id' => ['required', 'integer', 'exists:interview_slots,id']]);
        try {
            $interview = DB::transaction(function () use ($interview, $validated) {
                $slot = InterviewSlot::whereKey($validated['slot_id'])->lockForUpdate()->firstOrFail();
                abort_if($slot->status !== 'AVAILABLE' || $slot->starts_at->isPast(), 409, 'Este horario ya no está disponible. Elige otro.');
                $slot->update(['status' => 'BOOKED']);
                $interview->update(['interview_slot_id' => $slot->id, 'scheduled_at' => $slot->starts_at, 'status' => 'SCHEDULED', 'confirmed_at' => now(), 'invitation_token_hash' => null]);
                CandidateActivity::create(['candidate_id' => $interview->candidate_id, 'type' => 'interview_scheduled', 'description' => 'La candidata agendó su entrevista.', 'metadata' => ['scheduled_at' => $slot->starts_at->toIso8601String()]]);
                return $interview->fresh(['candidate.lead', 'slot']);
            });
        } catch (\Throwable $exception) {
            if ($exception->getCode() === '409' || str_contains($exception->getMessage(), 'Este horario')) return back()->withErrors(['slot_id' => 'Este horario ya no está disponible. Elige otro.']);
            throw $exception;
        }
        $whatsappUrl = $this->whatsappUrl($interview);
        try {
            Mail::to($interview->candidate->lead->email)->send(new InterviewScheduledMail($interview, $whatsappUrl));
        } catch (\Throwable $exception) {
            report($exception);
        }
        return Inertia::render('Public/InterviewBookingSuccess', ['candidate' => ['name' => $interview->candidate->lead->full_name, 'code' => $interview->candidate->code], 'scheduledAt' => $interview->scheduled_at, 'whatsappUrl' => $whatsappUrl]);
    }

    private function validateInvitation(Interview $interview, string $token): void
    {
        abort_unless($interview->status === 'INVITED' && $interview->invitation_token_hash && hash_equals($interview->invitation_token_hash, hash('sha256', $token)), 403, 'Este enlace no es válido.');
        abort_if($interview->invitation_expires_at?->isPast(), 410, 'Este enlace ya expiró.');
    }

    private function whatsappUrl(Interview $interview): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) config('services.velvet.whatsapp_number'));
        if (! $phone) return null;
        if (! str_starts_with($phone, '57')) $phone = '57' . $phone;
        $message = "Hola, soy {$interview->candidate->lead->full_name}. Necesito reprogramar mi entrevista de The Velvet Studio.";
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

}
