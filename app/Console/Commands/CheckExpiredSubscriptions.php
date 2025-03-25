<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessExpiredSubscriptions;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expired {--now : Processar imediatamente sem usar fila} {--recently-expired=0 : Verificar apenas assinaturas expiradas nos últimos X minutos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica assinaturas expiradas e envia notificações';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $recentlyExpired = (int) $this->option('recently-expired');
        
        $this->info('Iniciando verificação de assinaturas expiradas...');
        Log::info('Comando check-expired iniciado', [
            'recently_expired' => $recentlyExpired,
            'immediate' => $this->option('now')
        ]);
        
        if ($recentlyExpired > 0) {
            $this->info("Verificando apenas assinaturas expiradas nos últimos {$recentlyExpired} minutos");
        } else {
            $this->info('Verificando todas as assinaturas expiradas');
        }
        
        if ($this->option('now')) {
            $this->info('Processando assinaturas expiradas imediatamente (sem fila)...');
            Log::info('Executando job diretamente, sem fila', [
                'recently_expired' => $recentlyExpired
            ]);
            
            try {
                $job = new ProcessExpiredSubscriptions($recentlyExpired);
                $job->handle();
                
                $this->info('Processamento concluído com sucesso.');
                Log::info('Job executado com sucesso');
            } catch (\Exception $e) {
                $this->error('Erro ao processar assinaturas expiradas: ' . $e->getMessage());
                Log::error('Erro ao executar job diretamente', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return Command::FAILURE;
            }
        } else {
            $this->info('Enviando job para fila do Redis...');
            Log::info('Enviando job para a fila Redis', [
                'recently_expired' => $recentlyExpired,
                'queue' => 'subscriptions'
            ]);
            
            try {
                ProcessExpiredSubscriptions::dispatch($recentlyExpired);
                
                $this->info('Job enviado para a fila com sucesso.');
                Log::info('Job enviado para a fila com sucesso');
            } catch (\Exception $e) {
                $this->error('Erro ao enviar job para a fila: ' . $e->getMessage());
                Log::error('Erro ao enviar job para a fila', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return Command::FAILURE;
            }
        }
        
        $this->info('Comando concluído.');
        Log::info('Comando check-expired concluído');
        
        return Command::SUCCESS;
    }
} 