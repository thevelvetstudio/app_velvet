<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Mail\ContractingScheduledMail;
use App\Models\Candidate;
use App\Models\CandidateActivity;
use App\Models\ContractAppointment;
use App\Models\ContractSlot;
use App\Models\LeadDocument;
use App\Services\RealtimePublisher;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContractingController extends Controller
{
    private const TIMEZONE = 'America/Bogota';

    public function index(): Response
    {
        $slots = ContractSlot::query()->with('appointment.candidate.lead')
            ->where('starts_at', '>=', now('UTC'))
            ->orderBy('starts_at')->limit(250)->get()
            ->map(fn (ContractSlot $slot) => $this->formatSlot($slot));

        $candidates = Candidate::query()->with('lead')
            ->whereIn('status', [CandidateStatus::WAITING, CandidateStatus::CONTRACTING])
            ->whereHas('lead')
            ->whereDoesntHave('contractAppointments', fn ($query) => $query->whereIn('status', ['SCHEDULED']))
            ->latest()->get();

        $appointments = ContractAppointment::query()->with('candidate.lead', 'slot')
            ->whereIn('status', ['SCHEDULED', 'COMPLETED'])
            ->latest()->limit(100)->get()
            ->map(function (ContractAppointment $appointment) {
                $data = $appointment->toArray();
                $data['slot'] = $appointment->slot ? $this->formatSlot($appointment->slot) : null;
                return $data;
            });

        return Inertia::render('Admin/Recruitment/Contracting/Index', compact('slots', 'candidates', 'appointments'));
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

        $from = Carbon::parse($validated['from_date'], self::TIMEZONE)->startOfDay();
        $to = Carbon::parse($validated['to_date'], self::TIMEZONE)->startOfDay();
        if ($from->diffInDays($to) > 90) {
            return back()->withErrors(['to_date' => 'El rango máximo para generar horarios es de 90 días.']);
        }

        $created = 0;
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            if (in_array($cursor->dayOfWeekIso, array_map('intval', $validated['weekdays']), true)) {
                $start = Carbon::createFromFormat('Y-m-d H:i', $cursor->format('Y-m-d') . ' ' . $validated['start_time'], self::TIMEZONE);
                $end = Carbon::createFromFormat('Y-m-d H:i', $cursor->format('Y-m-d') . ' ' . $validated['end_time'], self::TIMEZONE);
                for ($slotStart = $start->copy(); $slotStart->lt($end); $slotStart->addHour()) {
                    $slotEnd = $slotStart->copy()->addHour();
                    if ($slotStart->isFuture() && ! ContractSlot::where('starts_at', $slotStart->utc())->where('ends_at', $slotEnd->utc())->exists()) {
                        ContractSlot::create(['starts_at' => $slotStart->utc(), 'ends_at' => $slotEnd->utc(), 'created_by' => $request->user()->id]);
                        $created++;
                    }
                }
            }
            $cursor->addDay();
        }

        if ($created) {
            app(RealtimePublisher::class)->publish('contract.slots_generated', ['notification' => ['title' => 'Franjas de contratación', 'description' => "Se generaron {$created} franjas para contratación."]]);
        }

        return back()->with('success', $created ? "Se generaron {$created} franjas de contratación." : 'No había franjas nuevas para generar.');
    }

    public function destroyAvailableSlots(): RedirectResponse
    {
        $deleted = ContractSlot::query()->where('status', 'AVAILABLE')->whereDoesntHave('appointment')->delete();
        return back()->with('success', $deleted ? "Se eliminaron {$deleted} franjas disponibles." : 'No había franjas disponibles para eliminar.');
    }

    public function book(Request $request): RedirectResponse
    {
        $validated = $request->validate(['candidate_id' => ['required', 'integer', 'exists:candidates,id'], 'slot_id' => ['required', 'integer', 'exists:contract_slots,id'], 'notes' => ['nullable', 'string', 'max:2000']]);

        [$appointment, $activity] = DB::transaction(function () use ($request, $validated): array {
            $candidate = Candidate::query()->with('lead')->lockForUpdate()->findOrFail($validated['candidate_id']);
            if (! in_array($candidate->status, [CandidateStatus::WAITING, CandidateStatus::CONTRACTING], true)) {
                throw ValidationException::withMessages(['candidate_id' => 'El candidato debe estar en espera o en contratación.']);
            }
            if ($candidate->contractAppointments()->where('status', 'SCHEDULED')->exists()) {
                throw ValidationException::withMessages(['candidate_id' => 'El candidato ya tiene una contratación programada.']);
            }

            $slot = ContractSlot::query()->lockForUpdate()->findOrFail($validated['slot_id']);
            abort_if($slot->status !== 'AVAILABLE' || $slot->starts_at->isPast(), 409, 'Esta franja ya no está disponible.');
            $slot->update(['status' => 'BOOKED']);
            $appointment = ContractAppointment::create(['candidate_id' => $candidate->id, 'contract_slot_id' => $slot->id, 'created_by' => $request->user()->id, 'notes' => $validated['notes'] ?? null]);
            if ($candidate->status !== CandidateStatus::CONTRACTING) {
                $candidate->update(['status' => CandidateStatus::CONTRACTING]);
            }
            $activity = CandidateActivity::create(['candidate_id' => $candidate->id, 'user_id' => $request->user()->id, 'type' => 'contracting_scheduled', 'description' => 'Contratación física programada. El candidato pasó a En contratación.', 'metadata' => ['appointment_id' => $appointment->id, 'slot_id' => $slot->id]]);
            return [$appointment->fresh(['candidate.lead', 'slot']), $activity];
        });

        app(RealtimePublisher::class)->publishLead($appointment->candidate->lead, 'candidate.status_changed', ['candidate_id' => $appointment->candidate_id, 'status' => CandidateStatus::CONTRACTING->value, 'activity' => $activity->only(['id', 'description', 'created_at']), 'notification' => ['title' => 'Contratación programada', 'description' => "Se programó la contratación de {$appointment->candidate->lead->full_name}."]]);
        try {
            Mail::to($appointment->candidate->lead->email)->send(new ContractingScheduledMail($appointment));
        } catch (\Throwable $exception) {
            report($exception);
        }
        return back()->with('success', 'Contratación programada y candidato movido a En contratación.');
    }

    public function reschedule(Request $request, ContractAppointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:contract_slots,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        [$appointment, $activity] = DB::transaction(function () use ($request, $appointment, $validated): array {
            $appointment = ContractAppointment::query()->with('candidate.lead', 'slot')->lockForUpdate()->findOrFail($appointment->id);
            if ($appointment->status !== 'SCHEDULED' || $appointment->candidate->status !== CandidateStatus::CONTRACTING) {
                throw ValidationException::withMessages(['appointment' => 'Solo se puede reprogramar una contratación activa.']);
            }
            if ((int) $appointment->contract_slot_id === (int) $validated['slot_id']) {
                throw ValidationException::withMessages(['slot_id' => 'Selecciona una franja diferente a la actual.']);
            }

            $newSlot = ContractSlot::query()->lockForUpdate()->findOrFail($validated['slot_id']);
            abort_if($newSlot->status !== 'AVAILABLE' || $newSlot->starts_at->isPast(), 409, 'La nueva franja ya no está disponible.');
            $oldSlotId = $appointment->contract_slot_id;
            $oldStartsAt = $appointment->slot?->starts_at?->toIso8601String();
            $appointment->slot?->update(['status' => 'AVAILABLE']);
            $newSlot->update(['status' => 'BOOKED']);
            $appointment->update(['contract_slot_id' => $newSlot->id]);
            $activity = CandidateActivity::create([
                'candidate_id' => $appointment->candidate_id,
                'user_id' => $request->user()->id,
                'type' => 'contracting_rescheduled',
                'description' => 'La contratación física fue reprogramada. Motivo: ' . $validated['reason'],
                'metadata' => ['appointment_id' => $appointment->id, 'old_slot_id' => $oldSlotId, 'new_slot_id' => $newSlot->id, 'reason' => $validated['reason'], 'old_starts_at' => $oldStartsAt],
            ]);

            return [$appointment->fresh(['candidate.lead', 'slot']), $activity];
        });

        app(RealtimePublisher::class)->publishLead($appointment->candidate->lead, 'candidate.updated', ['candidate_id' => $appointment->candidate_id, 'appointment_id' => $appointment->id, 'activity' => $activity->only(['id', 'description', 'created_at']), 'notification' => ['title' => 'Contratación reprogramada', 'description' => "Se reprogramó la contratación de {$appointment->candidate->lead->full_name}."]]);
        try {
            Mail::to($appointment->candidate->lead->email)->send(new ContractingScheduledMail($appointment));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Contratación reprogramada y nueva confirmación enviada.');
    }

    public function complete(Request $request, ContractAppointment $appointment): RedirectResponse
    {
        [$appointment, $activity] = DB::transaction(function () use ($request, $appointment): array {
            $appointment = ContractAppointment::query()->with('candidate.lead', 'slot')->lockForUpdate()->findOrFail($appointment->id);
            if ($appointment->status !== 'SCHEDULED' || $appointment->candidate->status !== CandidateStatus::CONTRACTING) {
                throw ValidationException::withMessages(['appointment' => 'Solo se puede finalizar una contratación programada.']);
            }
            $appointment->update(['status' => 'COMPLETED', 'completed_at' => now()]);
            $appointment->candidate->update(['status' => CandidateStatus::ONBOARDING, 'contracted_at' => $appointment->candidate->contracted_at ?: now()]);
            $activity = CandidateActivity::create(['candidate_id' => $appointment->candidate_id, 'user_id' => $request->user()->id, 'type' => 'contracting_completed', 'description' => 'Contratación finalizada. El candidato pasó a En onboarding.', 'metadata' => ['appointment_id' => $appointment->id]]);
            return [$appointment->fresh(['candidate.lead', 'slot']), $activity];
        });

        app(RealtimePublisher::class)->publishLead($appointment->candidate->lead, 'candidate.status_changed', ['candidate_id' => $appointment->candidate_id, 'status' => CandidateStatus::ONBOARDING->value, 'activity' => $activity->only(['id', 'description', 'created_at']), 'notification' => ['title' => 'Contratación finalizada', 'description' => "{$appointment->candidate->lead->full_name} pasó a onboarding."]]);
        return back()->with('success', 'Contratación finalizada. El candidato pasó a En onboarding.');
    }

    public function publicDocuments(Request $request, Candidate $candidate): Response
    {
        abort_unless(in_array($candidate->status, [CandidateStatus::WAITING, CandidateStatus::CONTRACTING], true), 403, 'Este enlace ya no está disponible.');

        return Inertia::render('Public/ContractingDocuments', [
            'candidate' => ['name' => $candidate->lead->full_name, 'code' => $candidate->code],
        ]);
    }

    public function storePublicDocuments(Request $request, Candidate $candidate): RedirectResponse
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        abort_unless(in_array($candidate->status, [CandidateStatus::WAITING, CandidateStatus::CONTRACTING], true), 403, 'Este enlace ya no está disponible.');

        $stored = [];
        try {
            foreach ($validated['documents'] as $document) {
                $path = $document->store('lead-documents/contracts', 'local');
                $stored[] = $path;
                LeadDocument::create([
                    'lead_id' => $candidate->lead_id,
                    'type' => 'contract_document',
                    'original_name' => $document->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $document->getMimeType(),
                    'size' => $document->getSize(),
                    'status' => 'PENDING',
                    'metadata' => ['source' => 'candidate_upload', 'candidate_id' => $candidate->id],
                ]);
            }
        } catch (\Throwable $exception) {
            foreach ($stored as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        $activity = CandidateActivity::create(['candidate_id' => $candidate->id, 'user_id' => null, 'type' => 'candidate_documents_uploaded', 'description' => 'El candidato cargó documentos de contratación mediante el enlace seguro.', 'metadata' => ['count' => count($stored), 'source' => 'signed_link']]);
        $candidate->load('lead');
        app(RealtimePublisher::class)->publishLead($candidate->lead, 'candidate.updated', ['candidate_id' => $candidate->id, 'activity' => $activity->only(['id', 'description', 'created_at']), 'notification' => ['title' => 'Documentos recibidos', 'description' => "{$candidate->lead->full_name} cargó documentos de contratación."]]);

        return back()->with('success', 'Documentos recibidos correctamente. El equipo administrativo los revisará.');
    }

    public function uploadDocuments(Request $request, Candidate $candidate): RedirectResponse
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        if ($candidate->status !== CandidateStatus::CONTRACTING) {
            throw ValidationException::withMessages(['documents' => 'Los documentos de contratación solo se cargan durante En contratación.']);
        }

        $stored = [];
        try {
            foreach ($validated['documents'] as $document) {
                $path = $document->store('lead-documents/contracts', 'local');
                $stored[] = $path;
                LeadDocument::create([
                    'lead_id' => $candidate->lead_id,
                    'type' => 'contract_document',
                    'original_name' => $document->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $document->getMimeType(),
                    'size' => $document->getSize(),
                    'status' => 'COMPLETED',
                ]);
            }
        } catch (\Throwable $exception) {
            foreach ($stored as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        $activity = CandidateActivity::create(['candidate_id' => $candidate->id, 'user_id' => $request->user()->id, 'type' => 'contract_documents_uploaded', 'description' => 'Se cargaron documentos restantes de contratación.', 'metadata' => ['count' => count($stored)]]);
        $candidate->load('lead');
        app(RealtimePublisher::class)->publishLead($candidate->lead, 'candidate.updated', ['candidate_id' => $candidate->id, 'activity' => $activity->only(['id', 'description', 'created_at']), 'notification' => ['title' => 'Documentos de contratación cargados', 'description' => "Se cargaron documentos de {$candidate->lead->full_name}." ]]);
        return back()->with('success', 'Documentos de contratación cargados correctamente.');
    }

    private function formatSlot(ContractSlot $slot): array
    {
        $startsAt = Carbon::createFromFormat('Y-m-d H:i:s', $slot->getRawOriginal('starts_at'), 'UTC')->setTimezone(self::TIMEZONE);
        $endsAt = Carbon::createFromFormat('Y-m-d H:i:s', $slot->getRawOriginal('ends_at'), 'UTC')->setTimezone(self::TIMEZONE);
        return array_merge($slot->toArray(), ['starts_at' => $startsAt->toIso8601String(), 'ends_at' => $endsAt->toIso8601String()]);
    }
}
