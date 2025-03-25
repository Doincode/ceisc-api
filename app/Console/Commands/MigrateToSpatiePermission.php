<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class MigrateToSpatiePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-to-spatie-permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra os dados de permissões e roles do sistema atual para o Spatie Permission';

    protected $guardName = 'api';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migração para Spatie Permission...');
        
        // Limpar possíveis dados existentes nas tabelas de permissão
        $this->info('Limpando tabelas existentes...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Criar roles padrão
        $this->info('Criando roles...');
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => $this->guardName]);
        $userRole = Role::create(['name' => 'user', 'guard_name' => $this->guardName]);
        $managerRole = Role::create(['name' => 'manager', 'guard_name' => $this->guardName]);
        
        // Criar permissões a partir das existentes
        $this->info('Criando permissões...');
        $permissionsMap = [
            'create_content' => 'create content',
            'edit_content' => 'edit content',
            'delete_content' => 'delete content',
            'manage_users' => 'manage users',
            'view_users' => 'view users',
            'create_users' => 'create users',
            'edit_users' => 'edit users',
            'delete_users' => 'delete users',
            'view_plans' => 'view plans',
            'create_plans' => 'create plans',
            'edit_plans' => 'edit plans',
            'delete_plans' => 'delete plans',
        ];
        
        $permissionObjects = [];
        foreach ($permissionsMap as $oldName => $newName) {
            $permissionObjects[$oldName] = Permission::create(['name' => $newName, 'guard_name' => $this->guardName]);
        }
        
        // Associar todas as permissões à role admin
        $this->info('Atribuindo permissões às roles...');
        $adminRole->givePermissionTo(Permission::where('guard_name', $this->guardName)->get());
        
        // Atribuir algumas permissões à role manager
        $managerPermissions = [
            'create content', 'edit content', 'view users'
        ];
        foreach ($managerPermissions as $permName) {
            $permission = Permission::where('name', $permName)
                ->where('guard_name', $this->guardName)
                ->first();
            if ($permission) {
                $managerRole->givePermissionTo($permission);
            }
        }
        
        // Migrar usuários
        $this->info('Migrando usuários...');
        $users = User::all();
        $count = 0;
        
        foreach ($users as $user) {
            // Migrar role
            if ($user->role === 'admin') {
                $user->assignRole($adminRole);
                $this->info("Usuário ID {$user->id} ({$user->email}) atribuído como admin");
                $count++;
            } elseif ($user->role === 'manager') {
                $user->assignRole($managerRole);
                $this->info("Usuário ID {$user->id} ({$user->email}) atribuído como manager");
                $count++;
            } else {
                $user->assignRole($userRole);
                $this->info("Usuário ID {$user->id} ({$user->email}) atribuído como user");
                $count++;
            }
            
            // Migrar permissões específicas se necessário
            if (!empty($user->permissions) && $user->role !== 'admin') {
                foreach ($user->permissions as $oldPerm) {
                    if (isset($permissionsMap[$oldPerm])) {
                        $newPermName = $permissionsMap[$oldPerm];
                        $permission = Permission::where('name', $newPermName)
                            ->where('guard_name', $this->guardName)
                            ->first();
                        
                        if ($permission) {
                            $user->givePermissionTo($permission);
                            $this->info("  - Permissão {$newPermName} atribuída");
                        }
                    }
                }
            }
        }
        
        $this->info("Migração concluída! {$count} usuários processados.");
        
        return Command::SUCCESS;
    }
}
