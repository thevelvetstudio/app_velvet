<?php

namespace App\Http\Requests\Auth;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\CandidateActivity;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(array_merge($this->only('email', 'password'), ['is_active' => true]), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $candidate = Candidate::query()->with('lead')
            ->where('user_id', Auth::id())
            ->where('status', CandidateStatus::ONBOARDING)
            ->first();
        if ($candidate) {
            $candidate->update(['status' => CandidateStatus::INDUCTION]);
            $activity = CandidateActivity::create([
                'candidate_id' => $candidate->id,
                'user_id' => Auth::id(),
                'type' => 'first_login',
                'description' => 'Primer ingreso al dashboard. El candidato pasó automáticamente a En inducción.',
                'metadata' => ['user_id' => Auth::id()],
            ]);
            try {
                app(\App\Services\RealtimePublisher::class)->publishLead($candidate->lead, 'candidate.status_changed', [
                    'candidate_id' => $candidate->id,
                    'status' => CandidateStatus::INDUCTION->value,
                    'activity' => $activity->only(['id', 'description', 'created_at']),
                    'notification' => ['title' => 'Primer ingreso registrado', 'description' => "{$candidate->lead->full_name} inició su inducción."],
                ]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
