<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadDocument;

class CreateLead
{
    public function handle(array $data): Lead
    {
        unset($data['data_consent']);
        $document = $data['identity_document'] ?? null;
        unset($data['identity_document']);
        $data['privacy_accepted_at'] = now();

        $lead = Lead::create([
            ...$data,
            'code' => 'VEL-LEAD-'.str_pad((string) ((Lead::max('id') ?? 0) + 1), 6, '0', STR_PAD_LEFT),
            'status' => LeadStatus::NEW,
            'utm_source' => request()->string('utm_source')->value() ?: null,
            'utm_medium' => request()->string('utm_medium')->value() ?: null,
            'utm_campaign' => request()->string('utm_campaign')->value() ?: null,
            'utm_content' => request()->string('utm_content')->value() ?: null,
            'utm_term' => request()->string('utm_term')->value() ?: null,
            'referrer' => request()->headers->get('referer'),
            'landing_page' => request()->fullUrl(),
        ]);

        LeadActivity::create(['lead_id' => $lead->id, 'type' => 'created', 'description' => 'Solicitud recibida.']);

        if ($document) {
            LeadDocument::create([
                'lead_id' => $lead->id,
                'original_name' => $document->getClientOriginalName(),
                'path' => $document->store('lead-documents', 'local'),
                'mime_type' => $document->getMimeType(),
                'size' => $document->getSize(),
            ]);
        }

        return $lead;
    }
}
