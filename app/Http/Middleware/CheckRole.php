<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user() || !$this->hasRole($request->user(), $role)) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        return $next($request);
    }

    private function hasRole($user, $role): bool
    {
        if ($role === 'admin') {
            return $user->isAdmin();
        }

        if ($role === 'manager') {
            return $user->isManager() || $user->isAdmin();
        }

        return true;
    }
} 