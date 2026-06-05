<?php

namespace Abdalmolood\AiSecurityGuardian\Console;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Abdalmolood\AiSecurityGuardian\Scanners\ScannerManager;
use Abdalmolood\AiSecurityGuardian\Scanners\ComposerAuditScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\EnvScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\BladeScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\AstCodeScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\AstTenantScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\AstRaceConditionScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\AstWebhookScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\AstQueueScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\AstLogScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\AstErpScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\RouteScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\UploadScanner;
use Abdalmolood\AiSecurityGuardian\AI\AiManager;
use Abdalmolood\AiSecurityGuardian\Models\SecurityScan;
use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;
use Abdalmolood\AiSecurityGuardian\Notifications\MailNotifier;
use Abdalmolood\AiSecurityGuardian\Notifications\TelegramNotifier;

class ScanCommand extends Command
{
    protected $signature = 'ai-security:scan {--deep : Run an in-depth analysis}';
    protected $description = 'Scan the application for security vulnerabilities and send to AI for review.';

    public function handle(AiManager $aiManager)
    {
        if (!config('ai-security-guardian.enabled')) {
            $this->warn('AI Security Guardian is disabled in config.');
            return;
        }

        $this->info('Starting AI Security Guardian Scan...');
        $startedAt = Carbon::now();

        $scannerManager = new ScannerManager();
        $scannerManager->registerScanner(new ComposerAuditScanner());
        $scannerManager->registerScanner(new EnvScanner());
        $scannerManager->registerScanner(new BladeScanner());
        $scannerManager->registerScanner(new AstCodeScanner());
        $scannerManager->registerScanner(new AstTenantScanner());
        $scannerManager->registerScanner(new AstRaceConditionScanner());
        $scannerManager->registerScanner(new AstWebhookScanner());
        $scannerManager->registerScanner(new AstQueueScanner());
        $scannerManager->registerScanner(new AstLogScanner());
        $scannerManager->registerScanner(new AstErpScanner());
        $scannerManager->registerScanner(new RouteScanner());
        $scannerManager->registerScanner(new UploadScanner());

        $this->info('Running local scanners...');
        $localFindings = collect();
        $scannerFindings = collect();

        foreach ($scannerManager->getScanners() as $scanner) {
            $results = $scanner->scan();
            foreach ($results as $finding) {
                $scannerFindings->push([
                    'scanner_name' => $scanner->getName(),
                    'finding' => $finding,
                ]);
                $localFindings->push($finding);
            }
        }
        $this->info("Found {$localFindings->count()} issues locally.");

        $scanModel = SecurityScan::create([
            'started_at' => $startedAt,
            'provider' => config('ai-security-guardian.provider'),
            'model' => config('ai-security-guardian.providers.' . config('ai-security-guardian.provider') . '.model', 'unknown'),
        ]);

        $findingsToSave = $scannerFindings;
        $findingsForReport = $localFindings;

        // If we want to send context to AI for deep scan analysis
        $shouldUseAi = $this->option('deep')
            && config('ai-security-guardian.ai.enabled', true)
            && config('ai-security-guardian.ai.deep_scan_enabled', true)
            && $localFindings->isNotEmpty()
            && filled(config('ai-security-guardian.providers.' . config('ai-security-guardian.provider') . '.api_key'));

        if ($this->option('deep') && ! $shouldUseAi) {
            $this->info('AI analysis skipped because it is disabled, unconfigured, or there was no local finding context to review.');
        }

        if ($shouldUseAi) {
            $this->info('Sending findings to AI for deep analysis and recommendations...');
            
            try {
                $context = $localFindings->toArray();
                $prompt = "Review the following security findings from a Laravel application. Provide severity classification, remediation steps, and check for false positives.";
                
                $aiResponse = $aiManager->provider()->analyze($prompt, $context);
                
                // Merge AI insights with local findings or replace them
                if (count($aiResponse->findings) > 0) {
                    $findingsForReport = collect($aiResponse->findings);
                    $findingsToSave = collect($aiResponse->findings)->map(function ($finding) {
                        return [
                            'scanner_name' => 'AI Deep Scan',
                            'finding' => $finding,
                        ];
                    });
                }
                
                $scanModel->summary = $aiResponse->metadata;
            } catch (\Exception $e) {
                $this->error("AI Analysis failed: " . $e->getMessage());
                $scanModel->summary = ['error' => $e->getMessage()];
            }
        }

        $riskScore = 0;
        foreach ($findingsToSave as $entry) {
            $finding = $entry['finding'];
            $scanModel->findings()->create([
                'scanner_name' => $entry['scanner_name'] ?? 'Unknown Scanner',
                'severity' => $finding->severity->value,
                'category' => $finding->category,
                'package_name' => $finding->packageName,
                'cve' => $finding->cve,
                'advisory_url' => $finding->advisoryUrl,
                'affected_file' => $finding->affectedFile,
                'affected_line' => $finding->affectedLine,
                'title' => $finding->title,
                'description' => $finding->description,
                'business_impact' => $finding->businessImpact,
                'technical_impact' => $finding->technicalImpact,
                'recommendation' => $finding->recommendation,
                'test_plan' => $finding->testPlan,
                'references' => $finding->references,
                'safe_auto_fix_allowed' => $finding->safeAutoFixAllowed,
                'human_review_required' => $finding->humanReviewRequired,
            ]);

            $riskScore += match ($finding->severity->value) {
                'critical' => 10,
                'high' => 5,
                'medium' => 3,
                'low' => 1,
                default => 0,
            };

            // Notify critical findings immediately
            if ($finding->severity->value === 'critical') {
                (new MailNotifier())->notifyCriticalFinding($finding);
                (new TelegramNotifier())->notifyCriticalFinding($finding);
            }
        }

        $finishedAt = Carbon::now();
        $scanModel->update([
            'finished_at' => $finishedAt,
            'status' => 'completed',
            'risk_score' => $riskScore,
        ]);

        $scanResult = new ScanResult(
            $startedAt, $finishedAt, $findingsForReport, $riskScore, $scanModel->summary ?? [], $scanModel->provider, $scanModel->model
        );

        (new MailNotifier())->notifyScanCompleted($scanResult);
        (new TelegramNotifier())->notifyScanCompleted($scanResult);

        $this->info('Scan completed successfully.');
    }
}
