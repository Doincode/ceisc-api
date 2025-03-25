<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check {--notify : Apenas notificar sem renovar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica assinaturas expiradas e renova automaticamente se configurado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando assinaturas...');
        
        $notifyOnly = $this->option('notify');
        
        if ($notifyOnly) {
            $this->info('Modo de notificação apenas - não serão feitas renovações');
        }

        // Usar transação para garantir consistência dos dados
        DB::beginTransaction();
        
        try {
            // Buscar assinaturas ativas que expiraram
            $expiredSubscriptions = Subscription::where('status', 'active')
                ->where('end_date', '<', now())
                ->with(['user', 'plan']) // Eager loading para reduzir consultas
                ->get();

            $this->info("Encontradas {$expiredSubscriptions->count()} assinaturas expiradas.");

            foreach ($expiredSubscriptions as $subscription) {
                if ($subscription->auto_renew && !$notifyOnly) {
                    // Em um sistema real, aqui seria feita a cobrança automática
                    // e a renovação só ocorreria após confirmação do pagamento
                    $this->info("Renovando assinatura #{$subscription->id} do usuário #{$subscription->user_id}");
                    $subscription->renew();
                    
                    // Registrar no log
                    Log::info("Assinatura #{$subscription->id} renovada automaticamente", [
                        'user_id' => $subscription->user_id,
                        'plan' => $subscription->plan->name,
                        'new_end_date' => $subscription->end_date
                    ]);
                } else {
                    $this->info("Marcando assinatura #{$subscription->id} como expirada");
                    $subscription->status = 'expired';
                    $subscription->save();
                    
                    // Registrar no log
                    Log::info("Assinatura #{$subscription->id} marcada como expirada", [
                        'user_id' => $subscription->user_id,
                        'plan' => $subscription->plan->name
                    ]);
                }
            }

            // Verificar assinaturas que estão próximas de expirar (3 dias antes)
            // Usando uma única consulta com whereDate para melhor performance
            $nearExpirationDate = Carbon::now()->addDays(3)->toDateString();
            $nearExpirationSubscriptions = Subscription::where('status', 'active')
                ->whereDate('end_date', $nearExpirationDate)
                ->with(['user', 'plan']) // Eager loading para reduzir consultas
                ->get();

            $this->info("Encontradas {$nearExpirationSubscriptions->count()} assinaturas próximas de expirar.");

            foreach ($nearExpirationSubscriptions as $subscription) {
                // Em um sistema real, aqui seria enviada uma notificação ao usuário
                $this->info("Notificando usuário #{$subscription->user_id} sobre assinatura #{$subscription->id} próxima de expirar");
                
                // Registrar no log
                Log::info("Notificação de expiração enviada para assinatura #{$subscription->id}", [
                    'user_id' => $subscription->user_id,
                    'plan' => $subscription->plan->name,
                    'expiration_date' => $subscription->end_date
                ]);
            }
            
            DB::commit();
            $this->info('Verificação de assinaturas concluída com sucesso.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Erro ao processar assinaturas: ' . $e->getMessage());
            Log::error('Erro ao processar assinaturas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 