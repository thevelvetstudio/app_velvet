<?php

namespace App\Mail;

use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Interview $interview, public ?string $whatsappUrl = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Entrevista confirmada  ·  The Velvet Studio');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.interview-scheduled', with: [
            'applicantName' => $this->interview->candidate->lead->full_name,
            'candidateCode' => $this->interview->candidate->code,
            'scheduledAt' => $this->scheduledAtUtc(),
            'whatsappUrl' => $this->whatsappUrl,
        ]);
    }

    public function attachments(): array
    {
        $start = $this->scheduledAtUtc();
        $end = $start->clone()->addHour();
        $uid = 'interview-' . $this->interview->id . '@thevelvetstudio.co';
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//The Velvet Studio//Entrevistas//ES', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT', 'UID:' . $uid, 'DTSTAMP:' . now()->utc()->format('Ymd\\THis\\Z'), 'DTSTART:' . $start->format('Ymd\\THis\\Z'), 'DTEND:' . $end->format('Ymd\\THis\\Z'),
            'SUMMARY:Entrevista  ·  The Velvet Studio', 'DESCRIPTION:Entrevista de selección para ' . $this->interview->candidate->lead->full_name,
            'BEGIN:VALARM', 'TRIGGER:-PT30M', 'ACTION:DISPLAY', 'DESCRIPTION:Tu entrevista con The Velvet Studio comienza en 30 minutos.', 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR', '',
        ]);

        return [Attachment::fromData(fn () => $ics, 'entrevista-velvet.ics')->withMime('text/calendar')];
    }

    private function scheduledAtUtc(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->interview->getRawOriginal('scheduled_at'), 'UTC');
    }
}

