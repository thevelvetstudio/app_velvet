<?php

namespace App\Http\Middleware;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveOperationalProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(['model', 'monitor'])) {
            return $next($request);
        }

        if (! $user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu perfil está desactivado. Contacta al equipo administrativo.']);
        }

        $candidate = Candidate::query()->where('user_id', $user->id)->first();

        if ($candidate?->status !== CandidateStatus::ACTIVE) {
            // Portal operational controllers switch to the isolated training dataset.
            $request->attributes->set('training_mode', true);
        }

        return $next($request);
    }
}
