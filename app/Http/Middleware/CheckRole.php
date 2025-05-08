<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check() || !$request->user()->hasRole($role)) {
            // Optionally, redirect to a specific page or return a 403 Forbidden response
            // abort(403, 'Unauthorized action.');
            // For now, let's redirect to home if not authorized for simplicity
            return redirect('/'); 
        }

        return $next($request);
    }
}