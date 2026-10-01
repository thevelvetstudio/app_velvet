<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractingRequirementsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Candidate $candidate, public string $uploadUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Documentación y siguiente paso de tu contratación · The Velvet Studio');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contracting-requirements', with: [
            'applicantName' => $this->candidate->lead->full_name,
            'candidateCode' => $this->candidate->code,
            'uploadUrl' => $this->uploadUrl,
        ]);
    }
}
