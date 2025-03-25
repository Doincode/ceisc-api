<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use App\Mail\SubscriptionExpiredNotification;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ProcessExpiredSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Minutos para verificar assinaturas recentemente expiradas
     * Se for 0, verifica todas as assinaturas expiradas
     *
     * @var int
     */
    protected $recentlyExpiredMinutes = 0;

    /**
     * Número de tentativas para o job
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Timeout do job em segundos
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param int $recentlyExpiredMinutes
     * @return void
     */
    public function __construct(int $recentlyExpiredMinutes = 0)
    {
        $this->connection = 'redis';
        $this->queue = 'subscriptions';
        $this->recentlyExpiredMinutes = $recentlyExpiredMinutes;
        $this->onQueue('subscriptions');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $jobId = $this->job ? $this->job->getJobId() : 'direct_execution';
        
        Log::info('Iniciando processamento de assinaturas expiradas', [
            'job_id' => $jobId,
            'recently_expired_minutes' => $this->recentlyExpiredMinutes
        ]);

        try {
            // Construir a query base
            $query = Subscription::where('status', 'active');
            
            // Se precisamos verificar apenas assinaturas recentemente expiradas
            if ($this->recentlyExpiredMinutes > 0) {
                $cutoffTime = Carbon::now()->subMinutes($this->recentlyExpiredMinutes);
                $query->where('end_date', '>=', $cutoffTime);
                $query->where('end_date', '<=', Carbon::now());
                
                Log::info("Verificando apenas assinaturas expiradas nos últimos {$this->recentlyExpiredMinutes} minutos", [
                    'job_id' => $jobId,
                    'cutoff_time' => $cutoffTime->toDateTimeString()
                ]);
            } else {
                // Buscar todas assinaturas que expiraram (end_date <= hoje)
                $query->whereDate('end_date', '<=', Carbon::today());
                
                Log::info("Verificando todas as assinaturas expiradas", [
                    'job_id' => $jobId
                ]);
            }
            
            // Obter as assinaturas expiradas com relacionamentos necessários
            $expiredSubscriptions = $query->with(['user', 'plan'])->get();

            $count = $expiredSubscriptions->count();
            Log::info("Encontradas {$count} assinaturas expiradas para processar", [
                'job_id' => $jobId,
                'count' => $count
            ]);

            foreach ($expiredSubscriptions as $subscription) {
                try {
                    // Registrar no log informações detalhadas sobre a assinatura
                    Log::info("Processando assinatura expirada", [
                        'job_id' => $jobId,
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'plan_id' => $subscription->plan_id,
                        'end_date' => $subscription->end_date->toDateTimeString()
                    ]);
                    
                    // Atualizar status da assinatura
                    $subscription->status = 'expired';
                    $subscription->save();
                    
                    Log::info("Status da assinatura atualizado para 'expired'", [
                        'job_id' => $jobId,
                        'subscription_id' => $subscription->id
                    ]);
                    
                    // Enviar email de notificação
                    if ($subscription->user && $subscription->user->email) {
                        // Criar um identificador único para rastrear este email nos logs
                        $emailTrackingId = uniqid('email_');
                        
                        Log::info("Enviando email de notificação", [
                            'job_id' => $jobId,
                            'subscription_id' => $subscription->id,
                            'user_id' => $subscription->user->id,
                            'email' => $subscription->user->email,
                            'email_tracking_id' => $emailTrackingId
                        ]);
                        
                        Mail::to($subscription->user->email)
                            ->queue(new SubscriptionExpiredNotification($subscription));
                        
                        Log::info("Email de notificação enfileirado com sucesso", [
                            'job_id' => $jobId,
                            'subscription_id' => $subscription->id,
                            'email_tracking_id' => $emailTrackingId
                        ]);
                    } else {
                        Log::warning("Não foi possível enviar email para assinatura", [
                            'job_id' => $jobId,
                            'subscription_id' => $subscription->id,
                            'reason' => 'usuário ou email não encontrado'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao processar assinatura expirada", [
                        'job_id' => $jobId,
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info("Processamento de assinaturas expiradas concluído com sucesso", [
                'job_id' => $jobId,
                'processed_count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error("Erro geral no processamento de assinaturas expiradas", [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-lançar exceção para que o Laravel saiba que o job falhou
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Falha no job ProcessExpiredSubscriptions', [
            'job_id' => $this->job ? $this->job->getJobId() : 'unknown',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
} 