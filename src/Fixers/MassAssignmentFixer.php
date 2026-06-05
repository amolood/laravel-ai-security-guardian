<?php

namespace Abdalmolood\AiSecurityGuardian\Fixers;

use Illuminate\Support\Facades\File;
use Abdalmolood\AiSecurityGuardian\Contracts\FixerInterface;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;

class MassAssignmentFixer implements FixerInterface
{
    public function apply(SecurityFinding $finding): bool
    {
        if ($finding->category !== 'mass_assignment' || !$finding->affected_file) {
            return false;
        }

        $filePath = base_path($finding->affected_file);
        
        if (!File::exists($filePath)) {
            return false;
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $lineNumber = $finding->affected_line;

        if (!$lineNumber || !isset($lines[$lineNumber - 1])) {
            return false;
        }

        $lineContent = $lines[$lineNumber - 1];

        if (str_contains($lineContent, '->all()')) {
            $newLineContent = str_replace('->all()', '->validated()', $lineContent);
            $lines[$lineNumber - 1] = $newLineContent;
            
            File::put($filePath, implode("\n", $lines));
            return true;
        }

        return false;
    }
}
