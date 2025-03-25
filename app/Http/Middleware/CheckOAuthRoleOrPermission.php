<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOAuthRoleOrPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roleOrPermission): Response
    {
        // Verifica se há um usuário autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Authentication required',
            ], 401);
        }
        
        // Admin tem acesso a tudo
        if ($request->user()->isAdmin()) {
            return $next($request);
        }
        
        // Separa os parâmetros
        $roleOrPermissions = is_array($roleOrPermission) 
            ? $roleOrPermission 
            : explode('|', $roleOrPermission);
        
        // Verifica se o usuário tem alguma das roles ou permissões
        foreach ($roleOrPermissions as $value) {
            // Verifica se é uma role
            if (str_starts_with($value, 'role:')) {
                $role = substr($value, 5);
                if ($request->user()->hasRole($role)) {
                    return $next($request);
                }
            } 
            // Verifica se é uma permissão
            else {
                if ($request->user()->hasPermissionTo($value)) {
                    return $next($request);
                }
            }
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Forbidden: Insufficient permissions',
        ], 403);
    }
} 