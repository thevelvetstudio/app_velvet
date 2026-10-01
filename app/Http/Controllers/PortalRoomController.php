<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\RoomUsageLog;
use App\Enums\CandidateStatus;
use App\Models\LeadDocument;
use App\Models\TrainingRecord;
use App\Services\RealtimePublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PortalRoomController extends Controller
{
    public function modelDashboard(Request $request): Response
    {
        if ($this->isTraining($request)) {
            return Inertia::render('Portal/Model/Dashboard', $this->trainingModelPayload($request));
        }

        $candidate = $this->modelCandidate($request);
        $operationalActive = $candidate?->status === CandidateStatus::ACTIVE && (bool) $candidate?->user?->is_active;

        return Inertia::render('Portal/Model/Dashboard', [
            'candidate' => $candidate ? $this->candidatePayload($candidate) : null,
            'rooms' => $this->roomsPayload(),
            'reservations' => $candidate ? $this->reservationQuery()->where('candidate_id', $candidate->id)->latest('starts_at')->limit(30)->get()->map(fn (RoomReservation $reservation) => $this->reservationPayload($reservation))->values() : [],
            'canReserve' => $operationalActive && $request->user()->hasPermissionTo('rooms.reserve_own'),
            'canUse' => $operationalActive && $request->user()->hasPermissionTo('rooms.use_own'),
            'operationalActive' => $operationalActive,
            'contractDocuments' => $this->ownDocuments($request),
        ]);
    }

    public function monitorDashboard(Request $request): Response
    {
        if ($this->isTraining($request)) {
            return Inertia::render('Portal/Monitor/Overview', $this->trainingMonitorPayload($request));
        }

        return Inertia::render('Portal/Monitor/Overview', $this->monitorPayload($request));
    }

    public function monitorRooms(Request $request): Response
    {
        if ($this->isTraining($request)) {
            return Inertia::render('Portal/Monitor/Dashboard', $this->trainingMonitorPayload($request));
        }

        return Inertia::render('Portal/Monitor/Dashboard', $this->monitorPayload($request));
    }

    private function monitorPayload(Request $request): array
    {
        $operationalActive = $request->user()->candidate?->status === CandidateStatus::ACTIVE && (bool) $request->user()->is_active;
        $reservations = $this->reservationQuery()
            ->whereIn('status', ['SCHEDULED', 'IN_USE'])
            ->where('ends_at', '>=', now('America/Bogota')->copy()->subHours(2)->utc())
            ->orderBy('starts_at')
            ->limit(100)
            ->get()
            ->map(fn (RoomReservation $reservation) => $this->reservationPayload($reservation))
            ->values();

        return [
            'rooms' => $this->roomsPayload(),
            'reservations' => $reservations,
            'candidates' => Candidate::query()
                ->with('lead:id,first_name,last_name')
                ->where('candidate_type', 'MODEL')
                ->whereNotIn('status', ['DISCARDED', 'WITHDRAWN'])
                ->orderBy('id', 'desc')
                ->get(['id', 'code', 'lead_id'])
                ->map(fn (Candidate $candidate) => $this->candidatePayload($candidate))
                ->values(),
            'canReserve' => $operationalActive && $request->user()->hasPermissionTo('rooms.manage_reservations'),
            'canUse' => $operationalActive && $request->user()->hasPermissionTo('rooms.manage_usage'),
            'operationalActive' => $operationalActive,
            'contractDocuments' => $this->ownDocuments($request),
        ];
    }

    public function previewContractDocument(Request $request, LeadDocument $document)
    {
        $this->authorizeContractDocument($request, $document);
        return response()->file(Storage::disk('local')->path($document->path), ['Content-Type' => $document->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($document->original_name) . '"']);
    }

    public function downloadContractDocument(Request $request, LeadDocument $document)
    {
        $this->authorizeContractDocument($request, $document);
        return Storage::disk('local')->download($document->path, $document->original_name, ['Content-Type' => $document->mime_type]);
    }

    public function storeModelReservation(Request $request): RedirectResponse
    {
        if ($this->isTraining($request)) {
            return $this->storeTrainingReservation($request, false);
        }

        abort_unless($request->user()->hasPermissionTo('rooms.reserve_own'), 403);
        $candidate = $this->modelCandidate($request);
        abort_unless($candidate, 403, 'Tu usuario todavía no tiene una modelo vinculada.');

        $data = $request->validate($this->reservationRules());
        $reservation = $this->createReservation($request, $data, $candidate, null);

        $this->publish('room.reservation.created', ['room_id' => $reservation->room_id, 'reservation_id' => $reservation->id]);

        return back()->with('success', 'La room quedó reservada correctamente.');
    }

    public function storeMonitorReservation(Request $request): RedirectResponse
    {
        if ($this->isTraining($request)) {
            return $this->storeTrainingReservation($request, true);
        }

        abort_unless($request->user()->hasPermissionTo('rooms.manage_reservations'), 403);
        $data = $request->validate(array_merge($this->reservationRules(), [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
        ]));
        $candidate = Candidate::query()->where('candidate_type', 'MODEL')->findOrFail($data['candidate_id']);
        $reservation = $this->createReservation($request, $data, $candidate, null);

        $this->publish('room.reservation.created', ['room_id' => $reservation->room_id, 'reservation_id' => $reservation->id]);

        return back()->with('success', 'La reserva de la modelo quedó programada.');
    }

    public function updateModelReservationStatus(Request $request, int $reservation): RedirectResponse
    {
        if ($this->isTraining($request)) {
            return $this->updateTrainingReservationStatus($request, false);
        }

        $reservation = RoomReservation::findOrFail($reservation);
        abort_unless($request->user()->hasPermissionTo('rooms.use_own'), 403);
        $candidate = $this->modelCandidate($request);
        abort_unless($candidate && $reservation->candidate_id === $candidate->id, 403);
        $this->changeReservationStatus($request, $reservation, ['IN_USE', 'COMPLETED']);

        return back()->with('success', 'El estado de tu uso fue actualizado.');
    }

    public function updateMonitorReservationStatus(Request $request, int $reservation): RedirectResponse
    {
        if ($this->isTraining($request)) {
            return $this->updateTrainingReservationStatus($request, true);
        }

        $reservation = RoomReservation::findOrFail($reservation);
        abort_unless($request->user()->hasPermissionTo('rooms.manage_usage'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['IN_USE', 'COMPLETED', 'CANCELLED', 'NO_SHOW'])]]);
        $this->changeReservationStatus($request, $reservation, [$data['status']]);

        return back()->with('success', 'El estado de la reserva fue actualizado en tiempo real.');
    }

    public function realtimeToken(Request $request): JsonResponse
    {
        abort_unless(collect(['rooms.view', 'rooms.view_own', 'rooms.view_team'])->contains(fn (string $permission) => $request->user()->hasPermissionTo($permission)), 403);
        abort_unless(config('services.ably.api_key'), 503, 'Ably no está configurado.');
        [$keyName, $keySecret] = explode(':', config('services.ably.api_key'), 2);
        $response = Http::withBasicAuth($keyName, $keySecret)->acceptJson()->post("https://rest.ably.io/keys/{$keyName}/requestToken", [
            'keyName' => $keyName,
            'capability' => json_encode([config('services.ably.channels.rooms') => ['subscribe']]),
            'ttl' => 3600000,
            'timestamp' => now()->valueOf(),
        ]);

        abort_unless($response->successful(), 502, 'No se pudo conectar al servicio realtime.');

        return response()->json($response->json());
    }

    private function isTraining(Request $request): bool
    {
        return $request->user()?->hasRole(['model', 'monitor'])
            && ($request->user()->candidate?->status !== CandidateStatus::ACTIVE || ! $request->user()->is_active);
    }

    private function trainingModelPayload(Request $request): array
    {
        $records = $this->trainingRecordsFor($request);

        return [
            'candidate' => ['name' => $request->user()->name],
            'rooms' => $this->trainingRoomsPayload($records),
            'reservations' => $this->trainingReservationsPayload($records, $request->user()->id),
            'canReserve' => $request->user()->hasPermissionTo('rooms.reserve_own'),
            'canUse' => $request->user()->hasPermissionTo('rooms.use_own'),
            'operationalActive' => false,
            'trainingMode' => true,
            'contractDocuments' => $this->ownDocuments($request),
        ];
    }

    private function trainingMonitorPayload(Request $request): array
    {
        $records = $this->trainingRecordsFor($request);

        return [
            'rooms' => $this->trainingRoomsPayload($records),
            'reservations' => $this->trainingReservationsPayload($records),
            'candidates' => $records->where('type', 'model')->map(fn (TrainingRecord $record) => [
                'id' => $record->id,
                'code' => $record->payload['code'] ?? $record->record_key,
                'name' => $record->payload['name'] ?? 'Modelo de prueba',
            ])->values(),
            'canReserve' => $request->user()->hasPermissionTo('rooms.manage_reservations'),
            'canUse' => $request->user()->hasPermissionTo('rooms.manage_usage'),
            'operationalActive' => false,
            'trainingMode' => true,
            'contractDocuments' => [],
        ];
    }

    private function trainingRecordsFor(Request $request)
    {
        return TrainingRecord::query()
            ->where(function ($query) use ($request) {
                $query->whereNull('owner_user_id')->orWhere('owner_user_id', $request->user()->id);
            })
            ->get();
    }

    private function trainingRoomsPayload($records): array
    {
        $now = now('America/Bogota');
        $reservations = $this->trainingReservationsPayload($records);

        return $records->where('type', 'room')->map(function (TrainingRecord $record) use ($reservations, $now) {
            $payload = $record->payload;
            $roomReservations = collect($reservations)->where('room_id', $record->id)->whereIn('status', ['SCHEDULED', 'IN_USE']);
            $current = $roomReservations->first(fn ($item) => Carbon::parse($item['starts_at'])->lte($now) && Carbon::parse($item['ends_at'])->gt($now));
            $upcoming = $roomReservations->first(fn ($item) => Carbon::parse($item['starts_at'])->gt($now));
            $status = $payload['status'] ?? 'AVAILABLE';
            if ($status === 'AVAILABLE') $status = $current ? ($current['status'] === 'IN_USE' ? 'IN_USE' : 'RESERVED') : ($upcoming ? 'RESERVED' : 'AVAILABLE');

            return ['id' => $record->id, 'code' => $payload['code'], 'name' => $payload['name'], 'description' => $payload['description'] ?? null, 'status' => $status, 'effective_status' => $status, 'is_active' => true, 'current' => $current, 'upcoming' => $upcoming];
        })->values()->all();
    }

    private function trainingReservationsPayload($records, ?int $ownerUserId = null): array
    {
        $rooms = $records->where('type', 'room')->keyBy('record_key');
        $models = $records->where('type', 'model')->keyBy('record_key');

        return $records->where('type', 'reservation')->filter(fn (TrainingRecord $record) => $ownerUserId === null || $record->owner_user_id === null || $record->owner_user_id === $ownerUserId)->map(function (TrainingRecord $record) use ($rooms, $models) {
            $payload = $record->payload;
            $room = $rooms->get($payload['room_key']);
            $model = $models->get($payload['model_key']);
            return ['id' => $record->id, 'room_id' => $room?->id, 'room_name' => $room?->payload['name'], 'candidate_id' => $model?->id, 'candidate_name' => $model?->payload['name'], 'purpose' => $payload['purpose'], 'starts_at' => $payload['starts_at'], 'ends_at' => $payload['ends_at'], 'status' => $payload['status'], 'notes' => $payload['notes'] ?? null];
        })->values()->all();
    }

    private function storeTrainingReservation(Request $request, bool $monitor): RedirectResponse
    {
        $rules = $this->reservationRules();
        $rules['room_id'] = ['required', 'integer', 'exists:training_records,id'];
        if ($monitor) $rules['candidate_id'] = ['required', 'integer', 'exists:training_records,id'];
        $data = $request->validate($rules);
        $room = TrainingRecord::query()->where('type', 'room')->findOrFail($data['room_id']);
        $model = $monitor ? TrainingRecord::query()->where('type', 'model')->findOrFail($data['candidate_id']) : TrainingRecord::query()->where('type', 'model')->first();
        $startsAt = Carbon::parse($data['starts_at'], 'America/Bogota');
        $endsAt = Carbon::parse($data['ends_at'], 'America/Bogota');
        abort_if($startsAt->isPast(), 422, 'La reserva debe comenzar en el futuro.');

        TrainingRecord::create(['type' => 'reservation', 'record_key' => 'training-reservation-'.uniqid(), 'owner_user_id' => $monitor ? null : $request->user()->id, 'payload' => ['room_key' => $room->record_key, 'model_key' => $model?->record_key, 'purpose' => $data['purpose'], 'starts_at' => $startsAt->utc()->toIso8601String(), 'ends_at' => $endsAt->utc()->toIso8601String(), 'status' => 'SCHEDULED', 'notes' => $data['notes'] ?? null]]);

        return back()->with('success', 'Registro de práctica creado en el sandbox.');
    }

    private function updateTrainingReservationStatus(Request $request, bool $monitor): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['IN_USE', 'COMPLETED', 'CANCELLED', 'NO_SHOW'])]]);
        $record = TrainingRecord::query()->where('type', 'reservation')->findOrFail($request->route('reservation'));
        abort_unless($monitor || $record->owner_user_id === $request->user()->id || $record->owner_user_id === null, 403);
        $payload = $record->payload;
        $payload['status'] = $data['status'];
        $record->update(['payload' => $payload]);

        return back()->with('success', 'Registro de práctica actualizado.');
    }

    private function modelCandidate(Request $request): ?Candidate
    {
        return Candidate::query()
            ->with('lead:id,first_name,last_name,email')
            ->where('candidate_type', 'MODEL')
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->orWhereHas('lead', fn ($lead) => $lead->where('email', $request->user()->email));
            })
            ->first();
    }

    private function ownDocuments(Request $request): array
    {
        $candidate = Candidate::query()->where('user_id', $request->user()->id)->first();
        if (! $candidate) return [];

        return LeadDocument::query()->where('lead_id', $candidate->lead_id)->latest()->get()->map(fn (LeadDocument $document) => [
            'id' => $document->id,
            'name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'created_at' => $document->created_at?->toIso8601String(),
            'preview_url' => route('portal.documents.preview', $document),
            'download_url' => route('portal.documents.download', $document),
        ])->values()->all();
    }

    private function authorizeContractDocument(Request $request, LeadDocument $document): void
    {
        abort_unless(Candidate::query()->where('user_id', $request->user()->id)->where('lead_id', $document->lead_id)->exists(), 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
    }

    private function createReservation(Request $request, array $data, Candidate $candidate, ?int $userId): RoomReservation
    {
        $startsAt = Carbon::parse($data['starts_at'], 'America/Bogota')->utc();
        $endsAt = Carbon::parse($data['ends_at'], 'America/Bogota')->utc();
        if ($startsAt->isPast()) {
            throw ValidationException::withMessages(['starts_at' => 'La reserva debe comenzar en el futuro.']);
        }

        return DB::transaction(function () use ($request, $data, $startsAt, $endsAt, $candidate, $userId) {
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);
            if (! $room->is_active || $room->status !== 'AVAILABLE') {
                throw ValidationException::withMessages(['room_id' => 'La room no está disponible para nuevas reservas.']);
            }

            $overlap = fn ($query) => $query->whereIn('status', ['SCHEDULED', 'IN_USE'])
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate();
            if ($overlap(RoomReservation::query()->where('room_id', $room->id))->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'La room ya está reservada en ese horario.']);
            }
            if ($overlap(RoomReservation::query()->where('candidate_id', $candidate->id))->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'La modelo ya tiene otra room asignada en ese horario.']);
            }

            return RoomReservation::create([
                'room_id' => $room->id,
                'candidate_id' => $candidate->id,
                'user_id' => $userId,
                'reserved_by' => $request->user()->id,
                'purpose' => $data['purpose'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'SCHEDULED',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    private function changeReservationStatus(Request $request, RoomReservation $reservation, array $allowedNext): void
    {
        DB::transaction(function () use ($request, $reservation, $allowedNext) {
            $reservation->refresh();
            $next = request()->validate(['status' => ['sometimes', Rule::in(['IN_USE', 'COMPLETED', 'CANCELLED', 'NO_SHOW'])]])['status'] ?? $allowedNext[0];
            $allowedFrom = match ($next) {
                'IN_USE' => ['SCHEDULED'],
                'COMPLETED' => ['SCHEDULED', 'IN_USE'],
                'CANCELLED', 'NO_SHOW' => ['SCHEDULED'],
            };
            if (! in_array($next, $allowedNext, true) || ! in_array($reservation->status, $allowedFrom, true)) {
                throw ValidationException::withMessages(['status' => "No se puede pasar de {$reservation->status} a {$next}."]);
            }

            $now = now('America/Bogota')->utc();
            $attributes = ['status' => $next];
            if ($next === 'IN_USE') {
                $attributes['checked_in_at'] = $reservation->checked_in_at ?: $now;
                $attributes['started_at'] = $reservation->started_at ?: $now;
            }
            if (in_array($next, ['COMPLETED', 'CANCELLED', 'NO_SHOW'], true)) $attributes['ended_at'] = $now;
            $previous = $reservation->status;
            $reservation->update($attributes);
            RoomUsageLog::create([
                'room_id' => $reservation->room_id,
                'room_reservation_id' => $reservation->id,
                'changed_by' => $request->user()->id,
                'from_status' => $previous,
                'to_status' => $next,
                'notes' => $next === 'IN_USE' ? 'Uso iniciado.' : ($next === 'COMPLETED' ? 'Uso finalizado.' : null),
            ]);
        });

        $this->publish('room.reservation.updated', ['room_id' => $reservation->room_id, 'reservation_id' => $reservation->id]);
    }

    private function reservationRules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'purpose' => ['required', 'string', 'max:180'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function roomsPayload()
    {
        $now = now('America/Bogota');
        return Room::query()->with(['reservations' => function ($query) use ($now) {
            $query->whereIn('status', ['SCHEDULED', 'IN_USE'])
                ->where('ends_at', '>=', $now->copy()->utc())
                ->orderBy('starts_at')
                ->with(['candidate.lead:id,first_name,last_name', 'user:id,name', 'reservedBy:id,name']);
        }])->orderBy('code')->get()->map(fn (Room $room) => $this->roomPayload($room, $now))->values();
    }

    private function reservationQuery()
    {
        return RoomReservation::query()->with(['room:id,code,name', 'candidate.lead:id,first_name,last_name', 'user:id,name', 'reservedBy:id,name']);
    }

    private function roomPayload(Room $room, Carbon $now): array
    {
        $current = $room->reservations->first(fn (RoomReservation $reservation) => $reservation->starts_at <= $now && $reservation->ends_at > $now);
        $upcoming = $room->reservations->first(fn (RoomReservation $reservation) => $reservation->starts_at > $now);
        $effectiveStatus = $room->status;
        if ($room->status === 'AVAILABLE') $effectiveStatus = $current ? ($current->status === 'IN_USE' ? 'IN_USE' : 'RESERVED') : ($upcoming ? 'RESERVED' : 'AVAILABLE');

        return [
            'id' => $room->id,
            'code' => $room->code,
            'name' => $room->name,
            'description' => $room->description,
            'status' => $effectiveStatus,
            'effective_status' => $effectiveStatus,
            'is_active' => $room->is_active,
            'current' => $current ? $this->reservationPayload($current) : null,
            'upcoming' => $upcoming ? $this->reservationPayload($upcoming) : null,
        ];
    }

    private function reservationPayload(RoomReservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'room_id' => $reservation->room_id,
            'room_name' => $reservation->room?->name,
            'room_code' => $reservation->room?->code,
            'purpose' => $reservation->purpose,
            'status' => $reservation->status,
            'starts_at' => $reservation->starts_at?->timezone('America/Bogota')->toIso8601String(),
            'ends_at' => $reservation->ends_at?->timezone('America/Bogota')->toIso8601String(),
            'candidate_id' => $reservation->candidate_id,
            'candidate_name' => $reservation->candidate?->lead?->full_name,
            'candidate_code' => $reservation->candidate?->code,
            'reserved_by' => $reservation->reservedBy?->name,
            'notes' => $reservation->notes,
        ];
    }

    private function candidatePayload(Candidate $candidate): array
    {
        return ['id' => $candidate->id, 'code' => $candidate->code, 'name' => $candidate->lead?->full_name];
    }

    private function publish(string $event, array $data): void
    {
        app(RealtimePublisher::class)->publish($event, $data);
    }
}
