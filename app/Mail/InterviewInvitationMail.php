<?php

namespace App\Mail;

use App\Models\Candidate;
use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Candidate $candidate, public Interview $interview, public string $bookingUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Elige el horario de tu entrevista · The Velvet Studio');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.interview-invitation', with: [
            'applicantName' => $this->candidate->lead->full_name,
            'candidateCode' => $this->candidate->code,
            'bookingUrl' => $this->bookingUrl,
            'expiresAt' => $this->interview->invitation_expires_at,
        ]);
    }
}
