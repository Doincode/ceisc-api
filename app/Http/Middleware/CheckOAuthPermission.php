<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOAuthPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Verifica se há um usuário autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Authentication required',
            ], 401);
        }
        
        // Admin tem todas as permissões
        if ($request->user()->isAdmin()) {
            return $next($request);
        }
        
        // Verifica se o usuário tem a permissão necessária
        if (!$this->hasPermission($request->user(), $permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Insufficient permissions',
            ], 403);
        }
        
        return $next($request);
    }
    
    /**
     * Verifica se o usuário tem a permissão especificada
     *
     * @param \App\Models\User $user
     * @param string $permission
     * @return bool
     */
    private function hasPermission($user, $permission): bool
    {
        // Para verificar múltiplas permissões
        if (str_contains($permission, '|')) {
            $permissions = explode('|', $permission);
            foreach ($permissions as $p) {
                if ($this->hasPermission($user, $p)) {
                    return true;
                }
            }
            return false;
        }
        
        // Verifica se o usuário tem a permissão utilizando o Spatie
        return $user->hasPermissionTo($permission);
    }
} 