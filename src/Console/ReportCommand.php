<?php

namespace Abdalmolood\AiSecurityGuardian\Console;

use Illuminate\Console\Command;
use Abdalmolood\AiSecurityGuardian\Models\SecurityScan;
use Abdalmolood\AiSecurityGuardian\Reports\MarkdownReportGenerator;
use Abdalmolood\AiSecurityGuardian\Reports\JsonReportGenerator;
use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class ReportCommand extends Command
{
    protected $signature = 'ai-security:report {--format=markdown : Report format (markdown, json)}';
    protected $description = 'Generate a report from the latest security scan.';

    public function handle()
    {
        $latestScan = SecurityScan::with('findings')->latest('started_at')->first();

        if (!$latestScan) {
            $this->warn('No security scans found. Run `php artisan ai-security:scan` first.');
            return;
        }

        $findingsCollection = collect();
        foreach ($latestScan->findings as $dbFinding) {
            $findingsCollection->push(new Finding(
                title: $dbFinding->title,
                description: $dbFinding->description,
                severity: Severity::tryFrom($dbFinding->severity) ?? Severity::INFO,
                category: $dbFinding->category,
                affectedFile: $dbFinding->affected_file,
                affectedLine: $dbFinding->affected_line,
                packageName: $dbFinding->package_name,
                cve: $dbFinding->cve,
                advisoryUrl: $dbFinding->advisory_url,
                recommendation: $dbFinding->recommendation,
                safeAutoFixAllowed: $dbFinding->safe_auto_fix_allowed,
                humanReviewRequired: $dbFinding->human_review_required,
            ));
        }

        $scanResult = new ScanResult(
            $latestScan->started_at,
            $latestScan->finished_at,
            $findingsCollection,
            $latestScan->risk_score,
            $latestScan->summary ?? [],
            $latestScan->provider,
            $latestScan->model
        );

        $format = $this->option('format');

        if ($format === 'json') {
            $report = (new JsonReportGenerator())->generate($scanResult);
            $fileName = 'security-report.json';
        } else {
            $report = (new MarkdownReportGenerator())->generate($scanResult);
            $fileName = 'security-report.md';
        }

        $path = storage_path('app/' . $fileName);
        file_put_contents($path, $report);

        $this->info("Report generated and saved to: $path");
        
        if ($format === 'markdown') {
            $this->line($report);
        }
    }
}
