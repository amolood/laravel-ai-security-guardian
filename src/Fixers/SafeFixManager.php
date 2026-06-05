<?php

namespace Abdalmolood\AiSecurityGuardian\Fixers;

use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SafeFixManager
{
    protected array $fixerMap = [
        'configuration' => EnvHardeningFixer::class,
        'mass_assignment' => MassAssignmentFixer::class,
    ];

    public function process(SecurityFinding $finding): bool
    {
        if (!$finding->safe_auto_fix_allowed) {
            return false;
        }

        $fixerClass = $this->fixerMap[$finding->category] ?? null;

        if (!$fixerClass) {
            Log::info("No direct fixer available for category: {$finding->category}");
            return false;
        }

        $filePath = $finding->affected_file === '.env' ? base_path('.env') : base_path($finding->affected_file);
        $backupPath = null;
        $originalFilePath = null;

        if ($finding->affected_file && File::exists($filePath)) {
            $originalFilePath = $finding->affected_file;
            $backupDir = storage_path('app/ai-security-backups');
            File::ensureDirectoryExists($backupDir);
            
            $filename = basename($filePath);
            $backupPath = $backupDir . '/' . time() . '_' . $filename;
            File::copy($filePath, $backupPath);
        }

        $fixer = new $fixerClass();
        
        try {
            $applied = $fixer->apply($finding);
            if ($applied) {
                $finding->update(['status' => 'fixed']);
                $finding->patches()->create([
                    'original_file_path' => $originalFilePath,
                    'backup_path' => $backupPath,
                    'status' => 'applied_directly',
                    'tests_status' => 'skipped', // we rely on the host app tests
                ]);
            } else {
                // If the fix failed, remove the backup
                if ($backupPath && File::exists($backupPath)) {
                    File::delete($backupPath);
                }
            }
            return $applied;
        } catch (\Exception $e) {
            Log::error("Failed to apply fix for finding {$finding->id}: " . $e->getMessage());
            if (isset($backupPath) && File::exists($backupPath)) {
                File::delete($backupPath);
            }
            return false;
        }
    }
}
