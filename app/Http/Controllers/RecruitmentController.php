<?php

namespace App\Http\Controllers;

use App\Actions\ConvertLeadToCandidate;
use App\Actions\CreateLead;
use App\Mail\ApplicationReceivedMail;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Candidate;
use App\Models\Lead;
use App\Models\OnboardingDraft;
use App\Services\RecruitmentDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

    public function leads(Request $request): Response
    {
        $query = Lead::query()->with('assignee')->latest();
        if ($search = $request->string('search')->trim()->value()) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        }
        if ($type = $request->string('type')->value()) {
            $query->where('candidate_type', $type);
        }
        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Recruitment/Leads/Index', ['leads' => $query->paginate(15)->withQueryString(), 'filters' => $request->only('search', 'type', 'status')]);
    }

    public function lead(Lead $lead): Response
    {
        return Inertia::render('Admin/Recruitment/Leads/Show', ['lead' => $lead->load(['activities.user', 'candidate'])]);
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
        }
        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Recruitment/Candidates/Index', ['candidates' => $query->paginate(15)->withQueryString(), 'filters' => $request->only('search', 'type', 'status')]);
    }

    public function candidate(Candidate $candidate): Response
    {
        return Inertia::render('Admin/Recruitment/Candidates/Show', ['candidate' => $candidate->load(['lead', 'activities.user'])]);
    }
}
