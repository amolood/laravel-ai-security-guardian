<?php

namespace Abdalmolood\AiSecurityGuardian\Console;

use Abdalmolood\AiSecurityGuardian\Models\SecurityPatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RollbackCommand extends Command
{
    protected $signature = 'ai-security:rollback {patchId? : ID of the security patch to rollback (optional, defaults to latest)}';
    protected $description = 'Rollback a previously applied auto‑fix by restoring the original file from backup';

    public function handle(): int
    {
        $patchId = $this->argument('patchId');
        $query = SecurityPatch::whereNotNull('backup_path')->orderByDesc('created_at');
        $patch = $patchId ? $query->find($patchId) : $query->first();

        if (! $patch) {
            $this->error('No applicable patch found to rollback.');
            return self::FAILURE;
        }

        $backupPath = $patch->backup_path;
        $originalPath = base_path($patch->original_file_path ?? $patch->affected_file);

        if (! File::exists($backupPath)) {
            $this->error('Backup file missing: '.$backupPath);
            return self::FAILURE;
        }

        try {
            File::copy($backupPath, $originalPath);
            $patch->update(['status' => 'rolled_back']);
            // optionally delete backup
            File::delete($backupPath);
            $this->info('Rollback successful. Restored '. $patch->original_file_path);
            Log::info('AI Security Guardian rollback executed', ['patch_id' => $patch->id]);
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Rollback failed: '.$e->getMessage());
            Log::error('Rollback failed', ['patch_id' => $patch->id, 'error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
?>
