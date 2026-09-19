<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibimos tu aplicación · The Velvet Studio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-received',
            with: [
                'applicantName' => $this->lead->full_name,
                'candidateType' => $this->lead->candidate_type?->value,
                'submittedAt' => $this->lead->created_at,
            ],
        );
    }
}
