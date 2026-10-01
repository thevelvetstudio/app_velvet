<?php

namespace App\Services;

use App\Mail\CandidateAdmittedMail;
use App\Models\Lead;
use Illuminate\Support\Facades\Mail;

class AdmittedMailService
{
    public function send(Lead $lead): void
    {
        $email = trim((string) $lead->email);

        if ($email === '' || ! $lead->candidate) {
            return;
        }

        try {
            Mail::to($email)->send(new CandidateAdmittedMail(
                $lead->fresh(['candidate.lead'])?->candidate ?? $lead->candidate,
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
