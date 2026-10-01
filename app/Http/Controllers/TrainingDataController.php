<?php

namespace App\Http\Controllers;

use Database\Seeders\TrainingDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingDataController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        return Inertia::render('Admin/TrainingData/Index', [
            'rooms' => \App\Models\TrainingRecord::query()->where('type', 'room')->whereNull('owner_user_id')->count(),
            'models' => \App\Models\TrainingRecord::query()->where('type', 'model')->whereNull('owner_user_id')->count(),
            'reservations' => \App\Models\TrainingRecord::query()->where('type', 'reservation')->whereNull('owner_user_id')->count(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        app(TrainingDataSeeder::class)->run();

        return back()->with('success', 'Los datos de prueba fueron restaurados correctamente.');
    }
}
