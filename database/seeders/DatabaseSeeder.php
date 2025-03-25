<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Executar o seeder de papéis e permissões
        $this->call(RoleAndPermissionSeeder::class);
        
        // Criar usuário administrador
        $this->call(AdminUserSeeder::class);
        
        // Adicione esta linha ao método run()
        $this->call(PlanSeeder::class);
    }
}
