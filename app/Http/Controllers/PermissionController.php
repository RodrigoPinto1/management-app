<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions', 'users')
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users->count(),
                'is_active' => true,
                'permissions' => $role->permissions->groupBy(fn($p) => explode(':', $p->name)[0])->map(fn($group, $menu) => [
                    'menu' => $menu,
                    'can_create' => $group->contains('name', "$menu:create"),
                    'can_read' => $group->contains('name', "$menu:read"),
                    'can_update' => $group->contains('name', "$menu:update"),
                    'can_delete' => $group->contains('name', "$menu:delete"),
                ])->values(),
            ]);

        $menus = [
            'Gestão' => 'menu-management',
            'Vendas' => 'menu-sales',
            'Calendário' => 'menu-calendar',
            'Configurações' => 'menu-settings',
            'Financeiro' => 'menu-finance',
        ];

        return Inertia::render('permissions/Index', [
            'roles' => $roles,
            'menus' => $menus,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'required|array',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        // Sync permissions
        $permissionNames = [];
        foreach ($validated['permissions'] as $menu => $perms) {
            foreach ($perms as $action) {
                $permissionNames[] = "$menu:$action";
            }
        }

        // Create permissions if they don't exist
        foreach ($permissionNames as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $role->syncPermissions($permissionNames);

        activity('acessos')
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Criou grupo de permissões: {$role->name}");

        return back()->with('success', 'Grupo de permissões criado com sucesso!');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
        ]);

        $role->update(['name' => $validated['name']]);

        // Sync permissions
        $permissionNames = [];
        foreach ($validated['permissions'] as $menu => $perms) {
            foreach ($perms as $action) {
                $permissionNames[] = "$menu:$action";
            }
        }

        // Create permissions if they don't exist
        foreach ($permissionNames as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $role->syncPermissions($permissionNames);

        activity('acessos')
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Atualizou grupo de permissões: {$role->name}");

        return back()->with('success', 'Grupo de permissões atualizado com sucesso!');
    }

    public function destroy(Request $request, Role $role)
    {
        $roleName = $role->name;

        activity('acessos')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Eliminou grupo de permissões: {$roleName}");

        $role->delete();

        return back()->with('success', 'Grupo de permissões eliminado com sucesso!');
    }

    public function list()
    {
        $roles = Role::with('permissions', 'users')
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users->count(),
                'permissions_count' => $role->permissions->count(),
            ]);

        return response()->json(['data' => $roles]);
    }
}
