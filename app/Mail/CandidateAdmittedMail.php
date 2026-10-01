<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateAdmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Candidate $candidate)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Felicitaciones! Has sido admitido(a) · The Velvet Studio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.candidate-admitted',
            with: [
                'applicantName' => $this->candidate->lead->full_name,
                'candidateCode' => $this->candidate->code,
            ],
        );
    }
}
