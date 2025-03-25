<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Não autorizado. Autenticação necessária.',
                'success' => false
            ], 401);
        }

        // Verificar se o usuário é admin
        if (method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin()) {
            return $next($request);
        }

        // Verificar usando o método do Spatie Permission
        if (method_exists($request->user(), 'hasRole') && $request->user()->hasRole('admin')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Acesso negado. Você não tem permissão para acessar este recurso.',
            'success' => false
        ], 403);
    }
}
