<?php

namespace Abdalmolood\AiSecurityGuardian\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthorizeAiSecurity
{
    public function handle(Request $request, Closure $next)
    {
        // The local/testing bypass is opt-in. Running `local` env on a
        // reachable host should NOT silently expose the dashboard (which maps
        // out the application's known vulnerabilities). Defaults to false.
        if (config('ai-security-guardian.ui.allow_unauthenticated_local', false)
            && app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        if (Gate::allows('viewAiSecurity', [$request->user()])) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to AI Security Guardian Dashboard.');
    }
}
