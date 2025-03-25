<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define que a resposta esperada é JSON
        $request->headers->set('Accept', 'application/json');
        
        // Processa a requisição
        $response = $next($request);
        
        // Se a resposta já for um JsonResponse, padronizamos o formato
        if ($response instanceof JsonResponse) {
            $statusCode = $response->getStatusCode();
            $data = $response->getData(true);
            
            // Adiciona campo success com base no status code
            if (!isset($data['success'])) {
                $data['success'] = $statusCode >= 200 && $statusCode < 300;
            }
            
            // Garantimos que sempre tenha uma mensagem
            if (!isset($data['message']) && $statusCode >= 200 && $statusCode < 300) {
                $data['message'] = 'Operação realizada com sucesso.';
            }
            
            // Reorganiza a estrutura para manter os campos na ordem correta
            $newData = [
                'success' => $data['success'] ?? true
            ];
            
            if (isset($data['message'])) {
                $newData['message'] = $data['message'];
            }
            
            // Adiciona o restante dos dados
            foreach ($data as $key => $value) {
                if (!in_array($key, ['success', 'message'])) {
                    $newData[$key] = $value;
                }
            }
            
            $response->setData($newData);
        }
        
        return $response;
    }
}
