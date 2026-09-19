<?php

namespace App\Actions;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\CandidateActivity;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\DB;

class ConvertLeadToCandidate
{
    public function handle(Lead $lead): Candidate
    {
        return DB::transaction(function () use ($lead) {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            if ($lead->candidate || $lead->status->value === 'CONVERTED') {
                abort(422, 'Este lead ya fue convertido.');
            }
            $candidate = Candidate::create(['code' => 'VEL-CAN-'.str_pad((string) ((Candidate::max('id') ?? 0) + 1), 6, '0', STR_PAD_LEFT), 'lead_id' => $lead->id, 'candidate_type' => $lead->candidate_type, 'status' => CandidateStatus::NEW]);
            $lead->update(['status' => 'CONVERTED']);
            LeadActivity::create(['lead_id' => $lead->id, 'type' => 'conversion', 'description' => "Convertido a {$candidate->code}."]);
            CandidateActivity::create(['candidate_id' => $candidate->id, 'type' => 'created', 'description' => 'Candidato creado desde lead.']);

            return $candidate;
        });
    }
}
