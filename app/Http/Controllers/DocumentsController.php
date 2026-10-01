<?php

namespace App\Http\Controllers;

use App\Models\LeadDocument;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DocumentsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LeadDocument::query()->with(['lead.candidate.user']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->whereHas('lead', fn ($lead) => $lead->where(fn ($leadQuery) => $leadQuery
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")));
        }

        if ($type = $request->string('type')->value()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->upper()->value()) {
            $query->where('status', $status);
        }

        $statsQuery = clone $query;
        $documentRows = $query->latest()->get();
        $people = $documentRows->groupBy('lead_id')->map(fn ($personDocuments, $leadId) => [
            'id' => (int) $leadId,
            'person' => $this->payload($personDocuments->first())['person'],
            'documents' => $personDocuments->map(fn (LeadDocument $document) => $this->payload($document))->values()->all(),
        ])->values();

        return Inertia::render('Admin/Documents/Index', [
            'documents' => ['data' => $people],
            'filters' => $request->only('search', 'type', 'status'),
            'types' => LeadDocument::query()->select('type')->distinct()->orderBy('type')->pluck('type')->values(),
            'stats' => [
                'total' => $statsQuery->count(),
                'people' => $statsQuery->clone()->distinct('lead_id')->count('lead_id'),
                'pending' => $statsQuery->clone()->where('status', 'PENDING')->count(),
            ],
            'canManage' => $request->user()->hasPermissionTo('documents.manage'),
        ]);
    }

    public function preview(LeadDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . addslashes($document->original_name) . '"',
        ]);
    }

    public function download(LeadDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name, ['Content-Type' => $document->mime_type]);
    }

    public function updateStatus(Request $request, LeadDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['PENDING', 'VERIFIED', 'REJECTED'])],
        ]);

        $document->update(['status' => $data['status']]);

        return back()->with('success', 'Estado del documento actualizado correctamente.');
    }

    public function updatePersonStatus(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['VERIFIED', 'REJECTED'])],
        ]);

        $updated = $lead->documents()->update(['status' => $data['status']]);

        return back()->with('success', $updated . ' documentos actualizados correctamente.');
    }

    private function payload(LeadDocument $document): array
    {
        $candidate = $document->lead?->candidate;

        return [
            'id' => $document->id,
            'name' => $document->original_name,
            'type' => $document->type,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'status' => $document->status,
            'created_at' => $document->created_at?->toIso8601String(),
            'preview_url' => route('admin.documents.preview', $document),
            'download_url' => route('admin.documents.download', $document),
            'person' => [
                'id' => $document->lead_id,
                'name' => $document->lead?->full_name,
                'email' => $document->lead?->email,
                'code' => $candidate?->code ?: $document->lead?->code,
                'type' => $candidate?->candidate_type?->value ?: $document->lead?->candidate_type?->value,
                'status' => $candidate?->status?->value ?: $document->lead?->status?->value,
                'user_active' => (bool) $candidate?->user?->is_active,
            ],
        ];
    }
}
