<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica autenticação
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado. Autenticação necessária.'
            ], 401);
        }
        
        // Método 1: Verificar propriedade role diretamente
        if ($request->user()->role === 'admin') {
            return $next($request);
        }
        
        // Método 2: Usar método isAdmin se existir
        if (method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin()) {
            return $next($request);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Acesso negado. Apenas administradores podem acessar este recurso.'
        ], 403);
    }
}
