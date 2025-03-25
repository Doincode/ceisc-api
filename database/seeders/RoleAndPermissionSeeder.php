<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetar cache de permissões
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Definir permissões para conteúdo
        $contentPermissions = [
            'view contents',
            'create contents',
            'edit contents',
            'delete contents'
        ];

        // Definir permissões para usuários
        $userPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users'
        ];

        // Criar permissões para conteúdo e usuários
        $allPermissions = array_merge($contentPermissions, $userPermissions);
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Criar papel de usuário comum
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions([
            'view contents',
        ]);

        // Criar papel de administrador
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());
    }
} 