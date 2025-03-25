<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdmin extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'user:promote-admin {email : O email do usuário a ser promovido}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Promove um usuário a administrador';

    /**
     * Executa o comando.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário com email {$email} não encontrado.");
            return 1;
        }
        
        if ($user->isAdmin()) {
            $this->info("Usuário {$email} já é um administrador.");
            return 0;
        }
        
        $user->update([
            'role' => 'admin',
            'permissions' => ['create_content', 'edit_content', 'delete_content', 'manage_users']
        ]);
        
        $this->info("Usuário {$email} foi promovido a administrador com sucesso!");
        
        return 0;
    }
} 