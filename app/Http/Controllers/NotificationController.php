<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notifications)
    {
        return response()->json([
            'notifications' => $notifications->forUser($request->user(), $request->string('channel')->toString() ?: null),
        ]);
    }

    public function history(Request $request, NotificationService $notifications)
    {
        $channel = $request->string('channel')->toString() ?: null;

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications->paginateForUser($request->user(), $channel, 15),
            'channel' => $channel,
            'channels' => array_values(config('services.ably.channels', [])),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
