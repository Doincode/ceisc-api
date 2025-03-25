<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringMail;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckExpiringSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiring {days=3 : Dias antes da expiração para verificar} {--check-expired : Verifica também assinaturas expiradas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica assinaturas prestes a expirar e/ou expiradas e envia emails de notificação';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysToCheck = (int) $this->argument('days');
        $checkExpired = $this->option('check-expired');
        $dateToCheck = Carbon::now()->addDays($daysToCheck);
        
        $this->info("Verificando assinaturas que expiram até {$dateToCheck->format('d/m/Y')}");
        
        if ($checkExpired) {
            $this->info("Verificando também assinaturas já expiradas");
            $this->checkExpiredSubscriptions();
        }
        
        // Procurar assinaturas ativas que expiram dentro do prazo especificado
        $expiringSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<=', $dateToCheck)
            ->whereDate('end_date', '>', Carbon::now())
            ->with(['user', 'plan'])
            ->get();
            
        $count = $expiringSubscriptions->count();
        $this->info("Encontradas {$count} assinaturas prestes a expirar nos próximos {$daysToCheck} dias");
        
        if ($count === 0) {
            return;
        }
        
        // Para cada assinatura, verificar e enviar email
        $emailsSent = 0;
        
        foreach ($expiringSubscriptions as $subscription) {
            // Pular assinaturas sem usuários associados
            if (!$subscription->user) {
                $this->warn("Assinatura ID {$subscription->id} não tem usuário associado");
                continue;
            }
            
            // Calcular dias restantes
            $daysLeft = Carbon::now()->diffInDays($subscription->end_date);
            
            $this->line("Processando: {$subscription->user->email} - Expira em {$daysLeft} dias");
            
            try {
                // Enviar email através da fila
                Mail::to($subscription->user->email)
                    ->queue(new SubscriptionExpiringMail(
                        $subscription->user,
                        $subscription,
                        $daysLeft
                    ));
                
                $emailsSent++;
                
            } catch (\Exception $e) {
                $this->error("Erro ao enviar email para {$subscription->user->email}: {$e->getMessage()}");
                Log::error("Erro ao enviar email de expiração: " . $e->getMessage(), [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user->id,
                    'email' => $subscription->user->email,
                ]);
            }
        }
        
        $this->info("{$emailsSent} emails de notificação foram enfileirados com sucesso");
    }
    
    /**
     * Verifica assinaturas que já expiraram mas ainda estão ativas no sistema
     */
    protected function checkExpiredSubscriptions()
    {
        $expiredSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->where('end_date', '<', Carbon::now())
            ->with(['user', 'plan'])
            ->get();
            
        $count = $expiredSubscriptions->count();
        $this->info("Encontradas {$count} assinaturas já expiradas que ainda estão ativas");
        
        if ($count === 0) {
            return;
        }
        
        foreach ($expiredSubscriptions as $subscription) {
            $this->line("Processando assinatura expirada: ID {$subscription->id} - Usuário: {$subscription->user?->email}");
            
            try {
                // Atualizar o status para expirado
                $subscription->status = 'expired';
                $subscription->save();
                
                // Notificar o usuário, se existir
                if ($subscription->user) {
                    $this->line("Enviando email de notificação para {$subscription->user->email}");
                    
                    // Poderia criar um novo tipo de email para assinaturas expiradas
                    // Mas vamos reutilizar o existente para simplicidade
                    Mail::to($subscription->user->email)
                        ->queue(new SubscriptionExpiringMail(
                            $subscription->user,
                            $subscription,
                            0 // Zero dias, pois já expirou
                        ));
                }
                
            } catch (\Exception $e) {
                $this->error("Erro ao processar assinatura expirada ID {$subscription->id}: {$e->getMessage()}");
                Log::error("Erro ao processar assinatura expirada: " . $e->getMessage(), [
                    'subscription_id' => $subscription->id
                ]);
            }
        }
        
        $this->info("{$count} assinaturas expiradas foram processadas com sucesso");
    }
}
