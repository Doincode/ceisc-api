<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define que a resposta esperada é JSON
        $request->headers->set('Accept', 'application/json');
        
        // Processa a requisição
        $response = $next($request);
        
        // Se a resposta já for um JsonResponse, tentamos padronizar o formato
        if ($response instanceof JsonResponse) {
            try {
                // Apenas se for uma resposta bem-sucedida e não tiver exceção
                if (!$response->exception && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $data = $response->getData(true);
                    
                    // Apenas adiciona campos se for um array e não tiver sido definido
                    if (is_array($data) && !isset($data['success'])) {
                        $data['success'] = true;
                        
                        if (!isset($data['message'])) {
                            $data['message'] = 'Operação realizada com sucesso.';
                        }
                        
                        $response->setData($data);
                    }
                }
            } catch (\Throwable $e) {
                // Se ocorrer algum erro ao tentar padronizar, ignoramos e retornamos a resposta original
                // para não quebrar as respostas existentes
            }
        }
        
        return $response;
    }
} 