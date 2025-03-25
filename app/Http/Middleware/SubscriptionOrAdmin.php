<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se o usuário está autenticado
        if (!$request->user()) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }
        
        // Verificar se o usuário é admin ou tem assinatura ativa
        $user = $request->user();
        if ($user->hasRole('admin') || $user->hasActiveSubscription()) {
            return $next($request);
        }
        
        // Caso contrário, retornar erro 403
        return response()->json([
            'message' => 'É necessário ter uma assinatura ativa para acessar este recurso.',
            'subscriptions_url' => url('/api/plans')
        ], 403);
    }
} 