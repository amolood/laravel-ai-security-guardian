<?php

namespace Abdalmolood\AiSecurityGuardian\Fixers;

use Illuminate\Support\Facades\File;
use Abdalmolood\AiSecurityGuardian\Contracts\FixerInterface;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;

class EnvHardeningFixer implements FixerInterface
{
    public function apply(SecurityFinding $finding): bool
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return false;
        }

        $content = File::get($envPath);
        $modified = false;

        if ($finding->title === 'APP_DEBUG is enabled in production') {
            if (preg_match('/^APP_DEBUG=true/m', $content)) {
                $content = preg_replace('/^APP_DEBUG=true/m', 'APP_DEBUG=false', $content);
                $modified = true;
            }
        }

        if ($finding->title === 'SESSION_SECURE_COOKIE is not enabled') {
            if (!preg_match('/^SESSION_SECURE_COOKIE=/m', $content)) {
                $content .= "\nSESSION_SECURE_COOKIE=true\n";
                $modified = true;
            } else {
                $content = preg_replace('/^SESSION_SECURE_COOKIE=false/m', 'SESSION_SECURE_COOKIE=true', $content);
                $modified = true;
            }
        }

        if ($modified) {
            File::put($envPath, $content);
            return true;
        }

        return false;
    }
}
