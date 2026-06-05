<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class EnvScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Environment Configuration Scanner';
    }

    public function scan(): Collection
    {
        $findings = collect();
        $isProduction = app()->environment('production');

        // Check APP_DEBUG
        if ($isProduction && config('app.debug')) {
            $findings->push(new Finding(
                title: 'APP_DEBUG is enabled in production',
                description: 'The APP_DEBUG environment variable is set to true in a production environment. This can expose sensitive information and stack traces to end users.',
                severity: Severity::CRITICAL,
                category: 'configuration',
                affectedFile: '.env',
                recommendation: 'Set APP_DEBUG=false in the .env file.',
                safeAutoFixAllowed: true,
                humanReviewRequired: false,
                businessImpact: 'Information disclosure that could lead to further compromise.',
                technicalImpact: 'Exposes database credentials, application paths, and API keys via stack traces.',
            ));
        }

        // Check LOG_LEVEL
        if ($isProduction && config('logging.default') === 'stack' && config('logging.channels.stack.level', 'debug') === 'debug') {
             $findings->push(new Finding(
                title: 'LOG_LEVEL is set to debug in production',
                description: 'A verbose log level might log sensitive information accidentally in production.',
                severity: Severity::MEDIUM,
                category: 'configuration',
                affectedFile: '.env',
                recommendation: 'Set LOG_LEVEL to info, warning, or error in production.',
                safeAutoFixAllowed: true,
            ));
        }

        // Check Session Secure Cookie
        if ($isProduction && !config('session.secure')) {
            $findings->push(new Finding(
                title: 'SESSION_SECURE_COOKIE is not enabled',
                description: 'Session cookies without the secure flag can be intercepted over unencrypted HTTP connections.',
                severity: Severity::HIGH,
                category: 'configuration',
                affectedFile: 'config/session.php',
                recommendation: 'Set SESSION_SECURE_COOKIE=true in .env to ensure cookies are only sent over HTTPS.',
                safeAutoFixAllowed: true,
            ));
        }

        return $findings;
    }
}
