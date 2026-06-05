<?php

namespace Abdalmolood\AiSecurityGuardian\Contracts;

use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;

interface FixerInterface
{
    /**
     * Apply a fix for the given security finding.
     *
     * @param SecurityFinding $finding
     * @return bool True if the fix was applied successfully.
     */
    public function apply(SecurityFinding $finding): bool;
}
