<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado. Autenticação necessária.',
            ], 401);
        }
        
        // Verificar se o usuário é admin diretamente pela propriedade role
        if (isset($request->user()->role) && $request->user()->role === 'admin') {
            return $next($request);
        }
        
        // Verificar através do método isAdmin() se existir
        if (method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin()) {
            return $next($request);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Acesso negado. Você não tem permissão para acessar este recurso.',
        ], 403);
    }
}
