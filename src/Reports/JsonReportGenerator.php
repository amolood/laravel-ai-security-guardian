<?php

namespace Abdalmolood\AiSecurityGuardian\Reports;

use Abdalmolood\AiSecurityGuardian\Contracts\ReporterInterface;
use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;

class JsonReportGenerator implements ReporterInterface
{
    public function generate(ScanResult $result): string
    {
        $data = [
            'scan_summary' => [
                'started_at' => $result->startedAt->toIso8601String(),
                'finished_at' => $result->finishedAt->toIso8601String(),
                'provider' => $result->provider,
                'model' => $result->model,
                'risk_score' => $result->riskScore,
                'total_findings' => $result->findings->count(),
            ],
            'findings' => $result->findings->map(function ($finding) {
                return [
                    'title' => $finding->title,
                    'severity' => $finding->severity->value,
                    'category' => $finding->category,
                    'affected_file' => $finding->affectedFile,
                    'affected_line' => $finding->affectedLine,
                    'description' => $finding->description,
                    'business_impact' => $finding->businessImpact,
                    'technical_impact' => $finding->technicalImpact,
                    'recommendation' => $finding->recommendation,
                    'test_plan' => $finding->testPlan,
                    'references' => $finding->references,
                ];
            })->toArray(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
