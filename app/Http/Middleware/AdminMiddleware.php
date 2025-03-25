<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se há um usuário autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado. Autenticação necessária.',
            ], 401);
        }
        
        // Verifica se o usuário é admin
        // Método 1: Usando a função isAdmin()
        if (method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin()) {
            return $next($request);
        }
        
        // Método 2: Verificando diretamente a propriedade role
        if (isset($request->user()->role) && $request->user()->role === 'admin') {
            return $next($request);
        }
        
        // Método 3: Usando o método hasRole do spatie/laravel-permission
        if (method_exists($request->user(), 'hasRole') && $request->user()->hasRole('admin')) {
            return $next($request);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Acesso negado. Você não tem permissão para acessar este recurso.',
        ], 403);
    }
}
