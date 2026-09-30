<?php

namespace App\Services;

use App\Mail\ApplicationDiscardedMail;
use App\Models\Lead;
use Illuminate\Support\Facades\Mail;

class ApplicationDiscardMailService
{
    public function send(Lead $lead, ?string $stage): void
    {
        $email = trim((string) $lead->email);

        if ($email === '') {
            return;
        }

        try {
            Mail::to($email)->send(new ApplicationDiscardedMail(
                $lead->fresh() ?? $lead,
                $stage ?: 'DEFAULT',
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
