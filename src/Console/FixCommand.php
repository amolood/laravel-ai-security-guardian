<?php

namespace Abdalmolood\AiSecurityGuardian\Console;

use Illuminate\Console\Command;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;
use Abdalmolood\AiSecurityGuardian\Fixers\SafeFixManager;

class FixCommand extends Command
{
    protected $signature = 'ai-security:fix {--direct : Apply fixes directly to files}';
    protected $description = 'Apply safe auto-fixes for detected security findings.';

    public function handle()
    {
        if (!$this->option('direct')) {
            $this->warn('Direct file modification is disabled by default.');
            $this->info('Run with `--direct` flag to enable direct modifications. Also ensure `auto_fix.production_direct_fix` is true in config.');
            return;
        }

        if (!config('ai-security-guardian.auto_fix.production_direct_fix')) {
            $this->error('The `auto_fix.production_direct_fix` configuration must be set to true in ai-security-guardian.php or .env (AI_SECURITY_AUTO_FIX_PRODUCTION_DIRECT_FIX) to use this command.');
            return;
        }

        $this->info('Starting Safe Auto-Fix Process...');

        $findings = SecurityFinding::where('status', 'open')
            ->where('safe_auto_fix_allowed', true)
            ->get();

        if ($findings->isEmpty()) {
            $this->info('No open findings are eligible for safe auto-fix.');
            return;
        }

        $manager = new SafeFixManager();
        $fixedCount = 0;

        foreach ($findings as $finding) {
            $this->info("Attempting to fix: [{$finding->category}] {$finding->title}");
            $success = $manager->process($finding);
            
            if ($success) {
                $this->info("✅ Fixed successfully.");
                $fixedCount++;
            } else {
                $this->warn("⚠️ Fix could not be applied automatically.");
            }
        }

        $this->info("Auto-fix process completed. Fixed {$fixedCount} out of {$findings->count()} eligible findings.");
    }
}
