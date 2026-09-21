<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrequalificationFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Candidate $candidate, public string $formUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Completa tus requisitos iniciales · The Velvet Studio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.prequalification-form',
            with: [
                'applicantName' => $this->candidate->lead->full_name,
                'candidateCode' => $this->candidate->code,
                'formUrl' => $this->formUrl,
            ],
        );
    }
}
