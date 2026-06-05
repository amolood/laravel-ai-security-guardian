<?php

namespace Abdalmolood\AiSecurityGuardian\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthorizeAiSecurity
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        if (Gate::allows('viewAiSecurity', [$request->user()])) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to AI Security Guardian Dashboard.');
    }
}
