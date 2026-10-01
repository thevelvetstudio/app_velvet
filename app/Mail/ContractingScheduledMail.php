<?php

namespace App\Mail;

use App\Models\ContractAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractingScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContractAppointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu contratación ha sido programada · The Velvet Studio');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contracting-scheduled', with: [
            'applicantName' => $this->appointment->candidate->lead->full_name,
            'candidateCode' => $this->appointment->candidate->code,
            'startsAt' => $this->appointment->slot->starts_at->setTimezone('America/Bogota')->locale('es'),
            'endsAt' => $this->appointment->slot->ends_at->setTimezone('America/Bogota')->locale('es'),
        ]);
    }
}
