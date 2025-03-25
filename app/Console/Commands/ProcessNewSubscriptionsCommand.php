<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNewSubscriptions;
use Illuminate\Console\Command;

class ProcessNewSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-new {--now : Processar imediatamente sem usar fila} {--recently-created=0 : Verificar apenas assinaturas criadas nos últimos X minutos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa novas assinaturas e envia emails de confirmação';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando processamento de novas assinaturas...');
        
        $recentlyCreated = (int) $this->option('recently-created');
        
        if ($this->option('now')) {
            $this->info('Processando novas assinaturas imediatamente (sem fila)...');
            (new ProcessNewSubscriptions($recentlyCreated))->handle();
        } else {
            $this->info('Enviando job para fila do Redis...');
            ProcessNewSubscriptions::dispatch($recentlyCreated);
        }
        
        $this->info('Comando concluído.');
        
        return Command::SUCCESS;
    }
} 