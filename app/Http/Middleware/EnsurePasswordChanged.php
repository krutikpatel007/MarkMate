<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Redirect users with temporary passwords to the first-login password screen.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->must_change_password
            && ! $request->routeIs('password.*')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
