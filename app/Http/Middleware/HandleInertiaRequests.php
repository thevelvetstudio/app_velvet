<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\RealtimePublisher;
use App\Services\NotificationService;
use App\Enums\CandidateStatus;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->load('roles.permissions');

        $candidate = $user?->candidate;
        $portalRole = $user?->hasRole(['model', 'monitor']) ?? false;

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'candidate_id' => $candidate?->id,
                    'candidate_status' => $candidate?->status?->value,
                    'candidate_access_active' => (bool) $user->is_active,
                    'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name, 'slug' => $role->slug])->values(),
                    'permissions' => $user->roles->flatMap(fn ($role) => $role->permissions->pluck('slug'))->unique()->values(),
                ]) : null,
            ],
            'operationalAccess' => $portalRole ? [
                'is_portal' => true,
                'is_active' => $candidate?->status === CandidateStatus::ACTIVE && (bool) $candidate?->user?->is_active,
                'status' => $candidate?->status?->value,
                'label' => $candidate?->status?->label(),
                'training_mode' => $candidate?->status !== CandidateStatus::ACTIVE || ! $candidate?->user?->is_active,
            ] : null,
            'realtime' => $user ? [
                'counters' => app(RealtimePublisher::class)->counters(),
                'notifications' => app(NotificationService::class)->forUser($user),
            ] : null,
        ];
    }
}
