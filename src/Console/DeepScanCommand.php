<?php

namespace Abdalmolood\AiSecurityGuardian\Console;

use Illuminate\Console\Command;

class DeepScanCommand extends Command
{
    protected $signature = 'ai-security:scan:deep';
    protected $description = 'Alias for running an in-depth security scan (scan --deep)';

    public function handle()
    {
        $this->info('Running Deep Scan...');
        $this->call('ai-security:scan', ['--deep' => true]);
    }
}
