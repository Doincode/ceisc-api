<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOAuthRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Verifica se há um usuário autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Authentication required',
            ], 401);
        }
        
        // Verifica se o usuário tem a role necessária
        if (!$this->hasRole($request->user(), $role)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Insufficient permissions',
            ], 403);
        }
        
        return $next($request);
    }
    
    /**
     * Verifica se o usuário tem a role especificada
     *
     * @param \App\Models\User $user
     * @param string $role
     * @return bool
     */
    private function hasRole($user, $role): bool
    {
        // Verifica a role necessária
        if ($role === 'admin') {
            return $user->isAdmin();
        }
        
        if ($role === 'user') {
            return true; // Todos usuários autenticados têm pelo menos a role 'user'
        }
        
        // Para verificar múltiplas roles
        if (str_contains($role, '|')) {
            $roles = explode('|', $role);
            foreach ($roles as $r) {
                if ($this->hasRole($user, $r)) {
                    return true;
                }
            }
            return false;
        }
        
        return false;
    }
} 