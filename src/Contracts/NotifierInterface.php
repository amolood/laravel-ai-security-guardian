<?php

namespace Abdalmolood\AiSecurityGuardian\Contracts;

use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;

interface NotifierInterface
{
    /**
     * Send notification when a scan is completed.
     *
     * @param ScanResult $result
     * @return void
     */
    public function notifyScanCompleted(ScanResult $result): void;

    /**
     * Send immediate notification for a critical finding.
     *
     * @param Finding $finding
     * @return void
     */
    public function notifyCriticalFinding(Finding $finding): void;
}
