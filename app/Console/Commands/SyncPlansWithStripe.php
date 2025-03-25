<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Services\StripeService;
use Illuminate\Console\Command;

class SyncPlansWithStripe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-plans {--plan-id= : ID do plano específico para sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza os planos com o Stripe';

    /**
     * Execute the console command.
     */
    public function handle(StripeService $stripeService)
    {
        $this->info('Iniciando sincronização de planos com o Stripe...');

        $planId = $this->option('plan-id');
        
        if ($planId) {
            $plans = Plan::where('id', $planId)->get();
            $this->info("Sincronizando apenas o plano ID: {$planId}");
        } else {
            $plans = Plan::where('is_active', true)->get();
            $this->info("Sincronizando {$plans->count()} planos ativos");
        }

        $bar = $this->output->createProgressBar($plans->count());
        $bar->start();

        foreach ($plans as $plan) {
            try {
                $result = $stripeService->syncPlanWithStripe($plan);
                $this->info("\nPlano '{$plan->name}' sincronizado com sucesso. Product ID: {$result['product']->id}, Price ID: {$result['price']->id}");
            } catch (\Exception $e) {
                $this->error("\nErro ao sincronizar plano '{$plan->name}': {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nSincronização concluída!");
    }
} 