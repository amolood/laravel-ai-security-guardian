<?php

namespace Abdalmolood\AiSecurityGuardian\Reports;

use Abdalmolood\AiSecurityGuardian\Contracts\ReporterInterface;
use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class MarkdownReportGenerator implements ReporterInterface
{
    public function generate(ScanResult $result): string
    {
        $markdown = "# Security Scan Report\n\n";
        $markdown .= "## Summary\n";
        $markdown .= "- **Started At**: " . $result->startedAt->toDateTimeString() . "\n";
        $markdown .= "- **Finished At**: " . $result->finishedAt->toDateTimeString() . "\n";
        $markdown .= "- **Total Findings**: " . $result->findings->count() . "\n";
        $markdown .= "- **Risk Score**: " . $result->riskScore . "\n\n";

        $markdown .= "## Findings\n\n";

        if ($result->findings->isEmpty()) {
            $markdown .= "No security findings detected. Great job!\n";
            return $markdown;
        }

        foreach ($result->findings as $index => $finding) {
            $num = $index + 1;
            $markdown .= "### $num. {$finding->title}\n";
            $markdown .= "- **Severity**: {$this->formatSeverity($finding->severity)}\n";
            $markdown .= "- **Category**: {$finding->category}\n";
            
            if ($finding->affectedFile) {
                $line = $finding->affectedLine ? ":{$finding->affectedLine}" : "";
                $markdown .= "- **Affected File**: `{$finding->affectedFile}{$line}`\n";
            }

            $markdown .= "\n**Description**\n{$finding->description}\n\n";

            if ($finding->businessImpact) {
                $markdown .= "**Business Impact**\n{$finding->businessImpact}\n\n";
            }

            if ($finding->technicalImpact) {
                $markdown .= "**Technical Impact**\n{$finding->technicalImpact}\n\n";
            }
            
            if ($finding->recommendation) {
                $markdown .= "**Recommendation**\n{$finding->recommendation}\n\n";
            }

            if ($finding->testPlan) {
                $markdown .= "**Test Plan**\n{$finding->testPlan}\n\n";
            }

            if (!empty($finding->references)) {
                $markdown .= "**References**\n";
                foreach ($finding->references as $reference) {
                    $markdown .= "- {$reference}\n";
                }
                $markdown .= "\n";
            }
            
            $markdown .= "---\n\n";
        }

        return $markdown;
    }

    protected function formatSeverity(Severity $severity): string
    {
        return match ($severity) {
            Severity::CRITICAL => '🔴 CRITICAL',
            Severity::HIGH => '🟠 HIGH',
            Severity::MEDIUM => '🟡 MEDIUM',
            Severity::LOW => '🟢 LOW',
            Severity::INFO => '🔵 INFO',
        };
    }
}
