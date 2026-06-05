<?php

namespace Abdalmolood\AiSecurityGuardian\Contracts;

use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;

interface ReporterInterface
{
    /**
     * Generate a report based on the scan result.
     *
     * @param ScanResult $result
     * @return string The generated report path or content.
     */
    public function generate(ScanResult $result): string;
}
