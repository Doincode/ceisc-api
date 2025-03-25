<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Verificar se o usuário está autenticado
        if (!$user) {
            return response()->json([
                'message' => 'É necessário estar autenticado para acessar este recurso.'
            ], 401);
        }
        
        // Verificar se o usuário é admin ou tem assinatura ativa
        if ($user->hasRole('admin') || $user->hasActiveSubscription()) {
            return $next($request);
        }
        
        return response()->json([
            'message' => 'É necessário ter uma assinatura ativa para acessar este recurso.',
            'subscriptions_url' => url('/api/plans') // URL para que o usuário possa ver os planos disponíveis
        ], 403);
    }
}
