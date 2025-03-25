<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSubscription
{
    /**
     * Handle an incoming request.
     * Verifica se o usuário possui uma assinatura ativa ou é um administrador.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Verificar se o usuário está autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'É necessário estar autenticado para acessar este recurso.'
            ], 401);
        }
        
        // Usuários admin têm acesso independente de assinatura
        if ($user->role === 'admin' || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return $next($request);
        }
        
        // Verificar se o usuário tem uma assinatura ativa
        if ($user->hasActiveSubscription()) {
            return $next($request);
        }
        
        // Caso não tenha assinatura e não seja admin, retornar erro 403
        return response()->json([
            'success' => false,
            'message' => 'É necessário ter uma assinatura ativa para acessar este conteúdo.',
            'plans_url' => url('/api/plans'), // URL para que o usuário possa ver os planos disponíveis
            'has_active_subscription' => false
        ], 403);
    }
} 