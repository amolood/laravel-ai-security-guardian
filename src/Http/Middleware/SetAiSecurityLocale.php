<?php

namespace Abdalmolood\AiSecurityGuardian\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAiSecurityLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = collect(config('ai-security-guardian.ui.available_locales', ['en', 'ar']))
            ->filter()
            ->map(fn ($locale) => (string) $locale)
            ->values()
            ->all();

        $fallbackLocale = config('ai-security-guardian.ui.locale', config('app.locale', 'en'));
        $requestedLocale = $request->query('lang') ?: $request->session()->get('ai-security-guardian.locale', $fallbackLocale);
        $locale = in_array($requestedLocale, $availableLocales, true) ? $requestedLocale : $fallbackLocale;

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('ai-security-guardian.locale', $locale);
        }

        return $next($request);
    }
}
