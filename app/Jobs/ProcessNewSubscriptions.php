<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Notifications\SubscriptionPaymentConfirmed;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNewSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Minutos para verificar assinaturas recentemente criadas
     * Se for 0, verifica todas as assinaturas criadas
     *
     * @var int
     */
    protected $recentlyCreatedMinutes = 0;

    /**
     * Create a new job instance.
     *
     * @param int $recentlyCreatedMinutes
     * @return void
     */
    public function __construct(int $recentlyCreatedMinutes = 0)
    {
        $this->connection = 'redis';
        $this->queue = 'subscriptions';
        $this->recentlyCreatedMinutes = $recentlyCreatedMinutes;
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
        
        Log::info('Iniciando processamento de novas assinaturas', [
            'job_id' => $jobId,
            'recently_created_minutes' => $this->recentlyCreatedMinutes
        ]);

        // Construir a query base
        $query = Subscription::where('status', 'active')
            ->where('payment_status', 'paid')
            ->with(['user', 'plan']);
        
        // Se precisamos verificar apenas assinaturas recentemente criadas
        if ($this->recentlyCreatedMinutes > 0) {
            $cutoffTime = Carbon::now()->subMinutes($this->recentlyCreatedMinutes);
            $query->where('created_at', '>=', $cutoffTime);
            Log::info("Verificando apenas assinaturas criadas nos últimos {$this->recentlyCreatedMinutes} minutos", [
                'job_id' => $jobId,
                'cutoff_time' => $cutoffTime->toDateTimeString()
            ]);
        }
        
        // Obter as assinaturas criadas
        $newSubscriptions = $query->get();

        $count = $newSubscriptions->count();
        Log::info("Encontradas {$count} novas assinaturas para processar", [
            'job_id' => $jobId,
            'count' => $count
        ]);

        foreach ($newSubscriptions as $subscription) {
            try {
                Log::info("Enviando email de confirmação para assinatura", [
                    'job_id' => $jobId,
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'email' => $subscription->user->email
                ]);
                
                // Enviar notificação de confirmação de pagamento
                $subscription->user->notify(new SubscriptionPaymentConfirmed($subscription));
                
                Log::info("Email de confirmação enviado com sucesso", [
                    'job_id' => $jobId,
                    'subscription_id' => $subscription->id
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao enviar email de confirmação para assinatura", [
                    'job_id' => $jobId,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info('Processamento de novas assinaturas concluído com sucesso', [
            'job_id' => $jobId,
            'processed_count' => $count
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Falha no job ProcessNewSubscriptions: ' . $exception->getMessage());
    }
} 