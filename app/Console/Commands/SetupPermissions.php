<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SetupPermissions extends Command
{
    protected $signature = 'permissions:setup';
    protected $description = 'Setup initial roles and permissions';

    public function handle()
    {
        $this->info('A configurar permissões...');

        // Criar permissões para cada menu
        $menus = ['menu-management', 'menu-sales', 'menu-calendar', 'menu-settings', 'menu-finance'];
        $actions = ['create', 'read', 'update', 'delete'];

        foreach ($menus as $menu) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$menu:$action"]);
            }
        }

        $this->info('✓ Permissões criadas');

        // Criar grupos padrão
        $admin = Role::firstOrCreate(['name' => 'Administrador']);
        $manager = Role::firstOrCreate(['name' => 'Gerente']);
        $operator = Role::firstOrCreate(['name' => 'Operador']);
        $viewer = Role::firstOrCreate(['name' => 'Visualizador']);

        // Admin tem todas as permissões
        $admin->syncPermissions(Permission::all());
        $this->info('✓ Grupo "Administrador" criado com todas as permissões');

        // Gerente tem tudo menos financeiro
        $managerPerms = Permission::whereNotLike('name', 'menu-finance:%')->get();
        $manager->syncPermissions($managerPerms);
        $this->info('✓ Grupo "Gerente" criado');

        // Operador tem apenas read e create em gestão e vendas
        $operatorPerms = Permission::where(function ($q) {
            $q->whereIn('name', ['menu-management:read', 'menu-management:create'])
                ->orWhereIn('name', ['menu-sales:read', 'menu-sales:create'])
                ->orWhereIn('name', ['menu-calendar:read', 'menu-calendar:create']);
        })->get();
        $operator->syncPermissions($operatorPerms);
        $this->info('✓ Grupo "Operador" criado');

        // Visualizador tem apenas read
        $viewerPerms = Permission::where('name', 'like', '%:read')->get();
        $viewer->syncPermissions($viewerPerms);
        $this->info('✓ Grupo "Visualizador" criado');

        // Atribuir roles ao utilizador de teste
        $user = User::first();
        if ($user) {
            $user->assignRole('Administrador');
            $this->info("✓ Utilizador '{$user->name}' agora é Administrador");
        }

        $this->info('✓ Permissões configuradas com sucesso!');
    }
}
