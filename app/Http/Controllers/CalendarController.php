<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        // La primera visita muestra la agenda del día actual; el usuario puede cambiar a semana o mes.
        $view = in_array($request->query('view'), ['day', 'week', 'month'], true) ? $request->query('view') : 'day';
        $focusDate = $this->focusDate($request->query('date'), $view);
        $month = $focusDate->copy()->startOfMonth();
        [$from, $to] = match ($view) {
            'day' => [$focusDate->copy()->startOfDay(), $focusDate->copy()->endOfDay()],
            'week' => [$focusDate->copy()->startOfWeek(Carbon::MONDAY), $focusDate->copy()->endOfWeek(Carbon::SUNDAY)],
            default => [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()],
        };
        $user = $request->user();

        $reminders = CalendarEvent::with(['assignee:id,name', 'candidate.lead:id,first_name,last_name'])
            ->whereNotIn('status', ['CANCELLED', 'DONE'])
            ->where(function ($query) use ($user) {
                $query->whereNull('assigned_to')->orWhere('assigned_to', $user->id);
            })
            ->where('starts_at', '<=', $to->copy()->utc())
            ->where(function ($query) use ($from) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $from->copy()->utc());
            })
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event) => $this->reminderEvent($event))
            ->values();

        $interviews = Interview::with('candidate.lead')
            ->where('status', 'SCHEDULED')
            ->whereBetween('scheduled_at', [$from->copy()->utc(), $to->copy()->utc()])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Interview $interview) => $this->interviewEvent($interview))
            ->values();

        $invitationDeadlines = Interview::with('candidate.lead')
            ->where('status', 'INVITED')
            ->whereBetween('invitation_expires_at', [$from->copy()->utc(), $to->copy()->utc()])
            ->orderBy('invitation_expires_at')
            ->get()
            ->map(fn (Interview $interview) => [
                'id' => 'invitation-'.$interview->id,
                'source' => 'deadline',
                'title' => 'Vence invitación · '.$interview->candidate->lead->full_name,
                'description' => 'La candidata aún debe elegir un horario de entrevista.',
                'type' => 'DEADLINE',
                'status' => 'OPEN',
                'starts_at' => $interview->invitation_expires_at?->timezone('America/Bogota')->toIso8601String(),
                'ends_at' => null,
                'all_day' => false,
                'href' => route('admin.candidates.show', $interview->candidate_id),
            ])
            ->values();

        $prequalificationDeadlines = Candidate::with('lead')
            ->whereNull('prequalification_completed_at')
            ->whereBetween('prequalification_expires_at', [$from->copy()->utc(), $to->copy()->utc()])
            ->orderBy('prequalification_expires_at')
            ->get()
            ->map(fn (Candidate $candidate) => [
                'id' => 'prequalification-'.$candidate->id,
                'source' => 'deadline',
                'title' => 'Vence precalificación · '.$candidate->lead->full_name,
                'description' => 'El enlace para completar los requisitos iniciales está próximo a vencer.',
                'type' => 'DEADLINE',
                'status' => 'OPEN',
                'starts_at' => $candidate->prequalification_expires_at?->timezone('America/Bogota')->toIso8601String(),
                'ends_at' => null,
                'all_day' => false,
                'href' => route('admin.candidates.show', $candidate->id),
            ])
            ->values();

        return Inertia::render('Admin/Calendar/Index', [
            'view' => $view,
            'date' => $focusDate->toDateString(),
            'month' => $month->format('Y-m'),
            'events' => $reminders->concat($interviews)->concat($invitationDeadlines)->concat($prequalificationDeadlines)->sortBy('starts_at')->values(),
            'users' => $user->hasPermissionTo('calendar.manage')
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : [],
            'canManage' => $user->hasPermissionTo('calendar.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $event = CalendarEvent::create([
            ...$this->eventAttributes($data),
            'created_by' => $request->user()->id,
        ]);

        return to_route('admin.calendar', ['month' => $event->starts_at->timezone('America/Bogota')->format('Y-m')])
            ->with('success', 'Recordatorio creado correctamente.');
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $data = $this->validated($request);
        $calendarEvent->update($this->eventAttributes($data));

        return back()->with('success', 'Recordatorio actualizado correctamente.');
    }

    public function destroy(CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->update(['status' => 'CANCELLED']);

        return back()->with('success', 'Recordatorio eliminado del calendario.');
    }

    public function complete(CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->update(['status' => 'DONE']);

        return back()->with('success', 'Recordatorio marcado como completado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'type' => ['required', Rule::in(['REMINDER', 'TASK', 'FOLLOW_UP', 'IMPORTANT'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'all_day' => ['sometimes', 'boolean'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function eventAttributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'] ?? 'OPEN',
            'starts_at' => Carbon::parse($data['starts_at'], 'America/Bogota')->utc(),
            'ends_at' => ! empty($data['ends_at']) ? Carbon::parse($data['ends_at'], 'America/Bogota')->utc() : null,
            'all_day' => (bool) ($data['all_day'] ?? false),
            'assigned_to' => $data['assigned_to'] ?? null,
        ];
    }

    private function month(?string $value): Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return now('America/Bogota')->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('!Y-m', $value, 'America/Bogota')->startOfMonth();
        } catch (\Throwable) {
            return now('America/Bogota')->startOfMonth();
        }
    }

    private function focusDate(?string $value, string $view): Carbon
    {
        if ($view === 'month') {
            return $this->month($value ? substr($value, 0, 7) : null);
        }

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return now('America/Bogota')->startOfDay();
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value, 'America/Bogota')->startOfDay();
        } catch (\Throwable) {
            return now('America/Bogota')->startOfDay();
        }
    }

    private function reminderEvent(CalendarEvent $event): array
    {
        return [
            'id' => 'reminder-'.$event->id,
            'source' => 'reminder',
            'title' => $event->title,
            'description' => $event->description,
            'type' => $event->type,
            'status' => $event->status,
            'starts_at' => $event->starts_at?->timezone('America/Bogota')->toIso8601String(),
            'ends_at' => $event->ends_at?->timezone('America/Bogota')->toIso8601String(),
            'all_day' => $event->all_day,
            'assigned_to' => $event->assignee?->name,
            'assigned_to_id' => $event->assigned_to,
            'editable' => true,
            'event_id' => $event->id,
        ];
    }

    private function interviewEvent(Interview $interview): array
    {
        return [
            'id' => 'interview-'.$interview->id,
            'source' => 'interview',
            'title' => 'Entrevista · '.$interview->candidate->lead->full_name,
            'description' => 'Entrevista de selección. Código '.$interview->candidate->code.'.',
            'type' => 'INTERVIEW',
            'status' => 'SCHEDULED',
            'starts_at' => $interview->scheduled_at?->timezone('America/Bogota')->toIso8601String(),
            'ends_at' => $interview->scheduled_at?->copy()->addHour()->timezone('America/Bogota')->toIso8601String(),
            'all_day' => false,
            'href' => route('admin.candidates.show', $interview->candidate_id),
        ];
    }
}
