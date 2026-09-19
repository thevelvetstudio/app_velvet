<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CookieConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->cookie('velvet_visitor_token') ?: (string) Str::uuid();

        CookieConsent::updateOrCreate(
            ['visitor_token' => $token],
            [
                'accepted_at' => now(),
                'preferences' => $request->input('preferences', ['essential' => true, 'analytics' => false]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        return response()->json(['ok' => true])->withCookie(cookie('velvet_cookie_consent', 'accepted', 60 * 24 * 365, '/', null, config('session.secure'), true, false, 'lax'))->withCookie(cookie('velvet_visitor_token', $token, 60 * 24 * 365 * 2, '/', null, config('session.secure'), true, false, 'lax'));
    }
}
