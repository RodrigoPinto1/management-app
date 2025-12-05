<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserAccessController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'role_id' => $user->roles->first()?->id,
                'role_name' => $user->roles->first()?->name,
                'is_active' => true,
            ]);

        $roles = Role::select('id', 'name')->get();

        return Inertia::render('users/Access', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => bcrypt('password'), // Temporary password
        ]);

        $role = Role::find($validated['role_id']);
        $user->assignRole($role);

        activity('acessos')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Criou utilizador: {$user->name} no grupo {$role->name}");

        return back()->with('success', 'Utilizador criado com sucesso!');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'role_id' => 'required|exists:roles,id',
        ]);

        $oldRole = $user->roles->first()?->name;
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $role = Role::find($validated['role_id']);
        $user->syncRoles($role);

        $newRole = $role->name;

        activity('acessos')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ])
            ->log("Atualizou utilizador: {$user->name} de {$oldRole} para {$newRole}");

        return back()->with('success', 'Utilizador atualizado com sucesso!');
    }

    public function destroy(Request $request, User $user)
    {
        $userName = $user->name;
        $userRole = $user->roles->first()?->name;

        activity('acessos')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Eliminou utilizador: {$userName} do grupo {$userRole}");

        $user->delete();

        return back()->with('success', 'Utilizador eliminado com sucesso!');
    }

    public function list()
    {
        $users = User::with('roles')
            ->paginate(10)
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '-',
                'role_name' => $user->roles->first()?->name ?? '-',
                'is_active' => true,
            ]);

        return response()->json($users);
    }
}
