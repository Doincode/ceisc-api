<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Garantir que o usuário administrador existe
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'permissions' => json_encode(['create_content', 'edit_content', 'delete_content', 'manage_users'])
            ]
        );

        // Verificar se o papel admin existe, se não, criar
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }

        // Remover todos os papéis atuais e atribuir o papel admin
        $admin->syncRoles([]);
        $admin->assignRole('admin');
    }
} 