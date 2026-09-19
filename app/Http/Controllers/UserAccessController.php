<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAccessController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Access/Users', [
            'users' => User::with('roles')->orderBy('name')->paginate(20),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Access/UserForm', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['roles' => ['nullable', 'array'], 'roles.*' => ['string', 'exists:roles,slug']]);
        $user->syncRoles($data['roles'] ?? []);

        return to_route('admin.users.index')->with('success', 'Roles del usuario actualizados.');
    }
}
