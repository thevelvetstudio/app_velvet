<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateActivity;
use App\Models\Lead;
use App\Models\LeadActivity;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class RecruitmentDashboardService
{
    public function handle(Request $request): array
    {
        [$from, $to, $period] = $this->period($request);
        $type = $request->string('type')->upper()->value() ?: 'ALL';

        $leads = Lead::query()->when($type !== 'ALL', fn ($query) => $query->where('candidate_type', $type));
        $candidates = Candidate::query()->when($type !== 'ALL', fn ($query) => $query->where('candidate_type', $type));
        $previousFrom = $from->copy()->subDays($from->diffInDays($to) + 1);
        $previousTo = $from->copy()->subSecond();

        $newLeads = $this->count($leads, 'created_at', $from, $to, fn ($query) => $query->where('status', 'NEW'));
        $totalLeads = $this->count($leads, 'created_at', $from, $to);
        $totalCandidates = $this->count($candidates, 'created_at', $from, $to);
        $interviews = $this->count($candidates, 'created_at', $from, $to, fn ($query) => $query->where('status', 'INTERVIEW'));
        $evaluations = $this->count($candidates, 'created_at', $from, $to, fn ($query) => $query->where('status', 'EVALUATION'));
        $onboarding = $this->count($candidates, 'created_at', $from, $to, fn ($query) => $query->where('status', 'ONBOARDING'));
        $active = $this->count($candidates, 'activated_at', $from, $to, fn ($query) => $query->where('status', 'ACTIVE'));

        return [
            'metrics' => [
                ['key' => 'new_leads', 'label' => 'Leads nuevos', 'value' => $newLeads, 'icon' => 'leads', 'change' => $this->change($leads, $from, $to, $previousFrom, $previousTo, fn ($query) => $query->where('status', 'NEW'))],
                ['key' => 'candidates', 'label' => 'Candidatos', 'value' => $totalCandidates, 'icon' => 'candidates', 'change' => $this->change($candidates, $from, $to, $previousFrom, $previousTo)],
                ['key' => 'interviews', 'label' => 'Entrevistas', 'value' => $interviews, 'icon' => 'interviews', 'change' => $this->change($candidates, $from, $to, $previousFrom, $previousTo, fn ($query) => $query->where('status', 'INTERVIEW'))],
                ['key' => 'evaluations', 'label' => 'En evaluación', 'value' => $evaluations, 'icon' => 'evaluation', 'change' => $this->change($candidates, $from, $to, $previousFrom, $previousTo, fn ($query) => $query->where('status', 'EVALUATION'))],
                ['key' => 'onboarding', 'label' => 'Onboarding', 'value' => $onboarding, 'icon' => 'onboarding', 'change' => $this->change($candidates, $from, $to, $previousFrom, $previousTo, fn ($query) => $query->where('status', 'ONBOARDING'))],
                ['key' => 'active', 'label' => 'Activos', 'value' => $active, 'icon' => 'active', 'change' => $this->change($candidates, $from, $to, $previousFrom, $previousTo, fn ($query) => $query->where('status', 'ACTIVE'))],
            ],
            'summary' => ['leads' => $totalLeads, 'candidates' => $totalCandidates],
            'pipeline' => $this->pipeline($leads, $candidates, $from, $to),
            'distribution' => $this->distribution($candidates, $from, $to),
            'chart' => $this->chart($leads, $candidates, $from, $to),
            'recentCandidates' => $candidates->clone()->with('lead')->latest()->limit(5)->get()->map(fn (Candidate $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->lead?->full_name,
                'code' => $candidate->code,
                'type' => $candidate->candidate_type->value,
                'status' => $candidate->status->value,
                'city' => $candidate->lead?->city,
                'created_at' => $candidate->created_at?->toIso8601String(),
            ])->values(),
            'upcomingInterviews' => $candidates->clone()->with('lead')->where('status', 'INTERVIEW')->latest('updated_at')->limit(5)->get()->map(fn (Candidate $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->lead?->full_name,
                'type' => $candidate->candidate_type->value,
                'date' => $candidate->updated_at?->toIso8601String(),
            ])->values(),
            'activity' => $this->activity(),
            'attention' => $this->attention($leads, $candidates, $from, $to),
            'filters' => ['period' => $period, 'type' => $type, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
        ];
    }

    private function period(Request $request): array
    {
        $period = $request->string('period', '30d')->value();
        $today = now()->endOfDay();

        if ($period === 'today') {
            return [now()->startOfDay(), $today, $period];
        }
        if ($period === '7d') {
            return [now()->startOfDay()->subDays(6), $today, $period];
        }
        if ($period === 'month') {
            return [now()->startOfMonth(), $today, $period];
        }
        if ($period === 'custom' && $request->filled(['from', 'to'])) {
            try {
                $from = Carbon::parse($request->string('from')->value())->startOfDay();
                $to = Carbon::parse($request->string('to')->value())->endOfDay();
                if ($from->lte($to)) {
                    return [$from, $to, $period];
                }
            } catch (\Throwable) {
                // Fall back to the stable default period for invalid input.
            }
        }

        return [now()->startOfDay()->subDays(29), $today, '30d'];
    }

    private function count($query, string $dateColumn, Carbon $from, Carbon $to, ?callable $filter = null): int
    {
        $query = $query->clone()->whereBetween($dateColumn, [$from, $to]);
        if ($filter) {
            $filter($query);
        }

        return $query->count();
    }

    private function change($query, Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo, ?callable $filter = null): int
    {
        $current = $this->count($query, 'created_at', $from, $to, $filter);
        $previous = $this->count($query, 'created_at', $previousFrom, $previousTo, $filter);
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function pipeline($leads, $candidates, Carbon $from, Carbon $to): array
    {
        $stages = [
            ['key' => 'NEW', 'label' => 'Leads', 'source' => 'lead'],
            ['key' => 'CONTACTED', 'label' => 'Contactados', 'source' => 'lead'],
            ['key' => 'QUALIFIED', 'label' => 'Precalificados', 'source' => 'lead'],
            ['key' => 'INTERVIEW', 'label' => 'Entrevistas', 'source' => 'candidate'],
            ['key' => 'EVALUATION', 'label' => 'En evaluación', 'source' => 'candidate'],
            ['key' => 'ADMITTED', 'label' => 'Admitidos', 'source' => 'candidate'],
            ['key' => 'ONBOARDING', 'label' => 'Onboarding', 'source' => 'candidate'],
            ['key' => 'ACTIVE', 'label' => 'Activos', 'source' => 'candidate'],
        ];

        return collect($stages)->map(function (array $stage) use ($leads, $candidates, $from, $to) {
            $query = $stage['source'] === 'lead' ? $leads : $candidates;
            $status = $stage['key'] === 'QUALIFIED' ? 'QUALIFIED' : $stage['key'];

            return ['key' => $stage['key'], 'label' => $stage['label'], 'count' => $this->count($query, 'created_at', $from, $to, fn ($builder) => $builder->where('status', $status))];
        })->values()->all();
    }

    private function distribution($candidates, Carbon $from, Carbon $to): array
    {
        return collect(['MODEL' => 'Modelos', 'MONITOR' => 'Monitores'])->map(fn ($label, $type) => ['type' => $type, 'label' => $label, 'count' => $this->count($candidates->clone()->where('candidate_type', $type), 'created_at', $from, $to)])->values()->all();
    }

    private function chart($leads, $candidates, Carbon $from, Carbon $to): array
    {
        $leadDays = $leads->clone()->whereBetween('created_at', [$from, $to])->get(['created_at'])->groupBy(fn ($lead) => $lead->created_at->toDateString())->map->count();
        $candidateDays = $candidates->clone()->whereBetween('created_at', [$from, $to])->get(['created_at'])->groupBy(fn ($candidate) => $candidate->created_at->toDateString())->map->count();
        $days = collect(CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay()))->map(fn ($date) => $date->toDateString());

        return $days->map(fn ($date) => ['date' => $date, 'label' => Carbon::parse($date)->locale('es')->translatedFormat('d MMM'), 'leads' => $leadDays->get($date, 0), 'candidates' => $candidateDays->get($date, 0)])->values()->all();
    }

    private function activity(): array
    {
        $leads = LeadActivity::with('lead')->latest('created_at')->limit(10)->get()->map(fn (LeadActivity $activity) => ['id' => 'lead-'.$activity->id, 'description' => $activity->description ?: 'Nuevo lead recibido', 'name' => $activity->lead?->full_name, 'created_at' => $activity->created_at?->toIso8601String()]);
        $candidates = CandidateActivity::with(['candidate.lead'])->latest('created_at')->limit(10)->get()->map(fn (CandidateActivity $activity) => ['id' => 'candidate-'.$activity->id, 'description' => $activity->description ?: 'Actividad de candidato', 'name' => $activity->candidate?->lead?->full_name, 'created_at' => $activity->created_at?->toIso8601String()]);

        return $leads->concat($candidates)->sortByDesc('created_at')->take(5)->values()->all();
    }

    private function attention($leads, $candidates, Carbon $from, Carbon $to): array
    {
        $items = [];
        $newLeads = $this->count($leads, 'created_at', $from, $to, fn ($query) => $query->where('status', 'NEW'));
        $evaluations = $this->count($candidates, 'created_at', $from, $to, fn ($query) => $query->where('status', 'EVALUATION'));
        $interviews = $this->count($candidates, 'created_at', $from, $to, fn ($query) => $query->where('status', 'INTERVIEW'));
        if ($newLeads) {
            $items[] = ['label' => 'Revisar nuevas solicitudes', 'count' => $newLeads, 'href' => '/admin/leads?status=NEW'];
        }
        if ($evaluations) {
            $items[] = ['label' => 'Candidatos en evaluación', 'count' => $evaluations, 'href' => '/admin/candidates'];
        }
        if ($interviews) {
            $items[] = ['label' => 'Entrevistas por gestionar', 'count' => $interviews, 'href' => '/admin/candidates'];
        }

        return $items;
    }
}
