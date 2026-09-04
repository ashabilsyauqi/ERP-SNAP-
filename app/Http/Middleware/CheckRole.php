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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if ((auth()->user()->isSuperAdmin() || auth()->user()->isOwner()) && $request->has('branch_id')) {
            session(['selected_branch_id' => $request->input('branch_id')]);
        }

        if (in_array(auth()->user()->role, $roles)) {
            return $next($request);
        }

        // SuperAdmin (KINGAshabil) & Owner have access to everything
        if (auth()->user()->isSuperAdmin() || auth()->user()->isOwner()) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}
