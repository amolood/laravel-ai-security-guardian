<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;
use Abdalmolood\AiSecurityGuardian\Enums\FindingStatus;

class ComposerAuditScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Composer Audit Scanner';
    }

    public function scan(): Collection
    {
        $findings = collect();

        if (!config('ai-security-guardian.sources.composer_audit', true)) {
            return $findings;
        }

        $result = Process::path(base_path())->run('composer audit --format=json --abandoned=report');

        $output = $result->output() ?: $result->errorOutput();
        
        $data = json_decode($output, true);

        if (!$data || !isset($data['advisories'])) {
            return $findings;
        }

        foreach ($data['advisories'] as $packageName => $advisories) {
            foreach ($advisories as $advisory) {
                $severity = $this->mapSeverity($advisory['severity'] ?? 'unknown');
                
                $findings->push(new Finding(
                    title: $advisory['title'] ?? "Vulnerability in $packageName",
                    description: "Package: $packageName\nVersion: " . ($advisory['reportedVersion'] ?? 'unknown') . "\nDetails: " . ($advisory['cve'] ?? 'No CVE'),
                    severity: $severity,
                    category: 'composer_dependency',
                    packageName: $packageName,
                    cve: $advisory['cve'] ?? null,
                    advisoryUrl: $advisory['link'] ?? null,
                    recommendation: "Update the $packageName package. Run composer update $packageName.",
                    safeAutoFixAllowed: false,
                    humanReviewRequired: true,
                ));
            }
        }

        if (isset($data['abandoned'])) {
            foreach ($data['abandoned'] as $packageName => $replacement) {
                $findings->push(new Finding(
                    title: "Abandoned Package: $packageName",
                    description: "The package $packageName is abandoned and no longer maintained.",
                    severity: Severity::MEDIUM,
                    category: 'composer_dependency',
                    packageName: $packageName,
                    recommendation: $replacement ? "Switch to the suggested replacement: $replacement" : "Find an alternative package.",
                    safeAutoFixAllowed: false,
                    humanReviewRequired: true,
                ));
            }
        }

        return $findings;
    }

    protected function mapSeverity(string $severity): Severity
    {
        return match (strtolower($severity)) {
            'critical' => Severity::CRITICAL,
            'high' => Severity::HIGH,
            'medium' => Severity::MEDIUM,
            'low' => Severity::LOW,
            default => Severity::INFO,
        };
    }
}
