<?php

namespace App\Http\Controllers;

use App\Models\OnboardingDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OnboardingDraftController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $draft = $this->draft($request);

        return response()->json(['draft' => $draft ? ['current_step' => $draft->current_step, 'candidate_type' => $draft->candidate_type, 'data' => $draft->data] : null]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->input('data', []);
        $candidateType = $request->string('candidate_type', 'MODEL')->upper()->value();
        $step = min(max($request->integer('current_step', 0), 0), 4);
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $phone = trim((string) ($data['phone'] ?? ''));
        $token = $request->cookie('velvet_visitor_token');

        if (!$token && ($email || $phone)) {
            $token = OnboardingDraft::query()
                ->when($email, fn ($query) => $query->where('email', $email))
                ->when(!$email && $phone, fn ($query) => $query->where('phone', $phone))
                ->latest('last_activity_at')
                ->value('visitor_token');
        }

        $token ??= (string) Str::uuid();

        OnboardingDraft::updateOrCreate(
            ['visitor_token' => $token],
            ['candidate_type' => $candidateType, 'email' => $email ?: null, 'phone' => $phone ?: null, 'current_step' => $step, 'data' => $data, 'last_activity_at' => now()],
        );

        return response()->json(['ok' => true])->withCookie(cookie('velvet_visitor_token', $token, 60 * 24 * 365 * 2, '/', null, config('session.secure'), true, false, 'lax'));
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->draft($request)?->delete();

        return response()->json(['ok' => true]);
    }

    private function draft(Request $request): ?OnboardingDraft
    {
        $token = $request->cookie('velvet_visitor_token');

        return $token ? OnboardingDraft::where('visitor_token', $token)->first() : null;
    }
}
