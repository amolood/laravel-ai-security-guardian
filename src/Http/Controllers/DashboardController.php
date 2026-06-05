<?php

namespace Abdalmolood\AiSecurityGuardian\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Abdalmolood\AiSecurityGuardian\DTO\Finding as FindingDto;
use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;
use Abdalmolood\AiSecurityGuardian\Enums\FindingStatus;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;
use Abdalmolood\AiSecurityGuardian\Models\SecurityPatch;
use Abdalmolood\AiSecurityGuardian\Models\SecurityScan;
use Abdalmolood\AiSecurityGuardian\Reports\JsonReportGenerator;
use Abdalmolood\AiSecurityGuardian\Reports\MarkdownReportGenerator;
use Abdalmolood\AiSecurityGuardian\Support\Ui;

class DashboardController extends Controller
{
    private function ui(): Ui
    {
        return app(Ui::class);
    }

    public function index(): View
    {
        $latestScan = $this->latestScan();

        return view('ai-security-guardian::dashboard', [
            'latestScan' => $latestScan,
            'scanSummary' => $this->scanSummary($latestScan),
            'scanTrend' => $this->scanTrend(),
            'severityBreakdown' => $this->severityBreakdown(),
            'scannerBreakdown' => $this->scannerBreakdown(),
            'topCategories' => $this->topCategories(),
            'recentCriticalAlerts' => $this->recentCriticalAlerts(),
            'nextActions' => $this->recommendedActions($latestScan),
            'securityScore' => $this->securityScore($latestScan),
            'stats' => $this->headlineStats(),
        ]);
    }

    public function scan(): RedirectResponse
    {
        Artisan::call('ai-security:scan');

        return redirect()
            ->route('ai-security.dashboard')
            ->with('success', $this->ui()->t('messages.scan_completed'));
    }

    public function scans(Request $request): View
    {
        $query = SecurityScan::query()
            ->withCount([
                'findings',
                'findings as critical_findings_count' => fn (Builder $q) => $q->where('severity', Severity::CRITICAL->value),
                'findings as high_findings_count' => fn (Builder $q) => $q->where('severity', Severity::HIGH->value),
                'findings as medium_findings_count' => fn (Builder $q) => $q->where('severity', Severity::MEDIUM->value),
                'findings as low_findings_count' => fn (Builder $q) => $q->where('severity', Severity::LOW->value),
            ]);

        $this->applyScanFilters($query, $request);

        return view('ai-security-guardian::scans.index', [
            'latestScan' => $this->latestScan(),
            'scans' => $query->latest('started_at')->paginate(10)->withQueryString(),
            'filters' => $this->scanFilterState($request),
            'providerOptions' => array_keys(config('ai-security-guardian.providers', [])),
            'statusOptions' => ['completed', 'running', 'failed', 'queued'],
        ]);
    }

    public function showScan(SecurityScan $scan): View
    {
        $scan->load(['findings.patches']);

        return view('ai-security-guardian::scans.show', [
            'latestScan' => $this->latestScan(),
            'scan' => $scan,
            'report' => $this->buildScanResult($scan),
            'findings' => $scan->findings,
            'duration' => $this->durationLabel($scan->started_at, $scan->finished_at),
        ]);
    }

    public function downloadScanReport(SecurityScan $scan, string $format)
    {
        return $this->downloadReportForScan($scan, $format);
    }

    public function findings(Request $request): View
    {
        $query = SecurityFinding::query()->with(['scan', 'patches']);
        $this->applyFindingFilters($query, $request);

        return view('ai-security-guardian::findings.index', [
            'latestScan' => $this->latestScan(),
            'findings' => $query->latest('created_at')->paginate(15)->withQueryString(),
            'filters' => $this->findingFilterState($request),
            'severityOptions' => $this->severityOptions(),
            'statusOptions' => $this->findingStatusOptions(),
            'scannerOptions' => $this->scannerNameOptions(),
        ]);
    }

    public function showFinding(SecurityFinding $finding): View
    {
        $finding->load(['scan', 'patches']);

        return view('ai-security-guardian::findings.show', [
            'latestScan' => $this->latestScan(),
            'finding' => $finding,
            'findingDto' => $this->findingToDto($finding),
            'timeline' => $this->findingTimeline($finding),
            'statusOptions' => $this->findingStatusOptions(),
            'recommendedActions' => $this->findingActionSuggestions($finding),
        ]);
    }

    public function updateFindingStatus(Request $request, SecurityFinding $finding): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', $this->findingStatusOptions())],
        ]);

        $finding->update(['status' => $data['status']]);

        return back()->with('success', $this->ui()->t('messages.finding_status_updated'));
    }

    public function reports(Request $request): View
    {
        $latestScan = $this->latestScan();
        $report = $latestScan ? $this->buildScanResult($latestScan) : null;
        $filteredFindings = $report ? $this->filterReportFindings($report->findings, $request) : collect();

        return view('ai-security-guardian::reports.index', [
            'latestScan' => $latestScan,
            'report' => $report,
            'filteredFindings' => $filteredFindings,
            'filters' => [
                'severity' => $request->string('severity')->toString(),
                'category' => $request->string('category')->toString(),
            ],
            'categories' => $this->topCategories()->pluck('category')->all(),
            'latestReportPaths' => $this->latestGeneratedReportPaths(),
        ]);
    }

    public function generateReport(Request $request): RedirectResponse
    {
        $scan = $this->latestScan();

        if (! $scan) {
            return back()->with('error', $this->ui()->t('messages.no_scan_for_report'));
        }

        $this->storeGeneratedReports($scan);

        return back()->with('success', $this->ui()->t('messages.report_regenerated'));
    }

    public function downloadLatestReport(string $format)
    {
        $scan = $this->latestScan();

        if (! $scan) {
            abort(404, $this->ui()->t('messages.no_scan_available'));
        }

        return $this->downloadReportForScan($scan, $format);
    }

    public function patches(Request $request): View
    {
        $query = SecurityPatch::query()->with(['finding.scan']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('tests_status')) {
            $query->where('tests_status', $request->string('tests_status'));
        }

        return view('ai-security-guardian::patches.index', [
            'latestScan' => $this->latestScan(),
            'patches' => $query->latest()->paginate(12)->withQueryString(),
            'statusOptions' => ['pending', 'applied_directly', 'reviewed', 'rolled_back'],
            'testsStatusOptions' => ['passed', 'failed', 'skipped', 'not_run'],
        ]);
    }

    public function downloadPatch(SecurityPatch $patch)
    {
        $content = $patch->patch_file ?: "# Safe patch suggestion\n\nNo patch body was generated for this record.\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="security-patch-' . $patch->id . '.patch"',
        ]);
    }

    public function providers(): View
    {
        return view('ai-security-guardian::settings.providers', [
            'latestScan' => $this->latestScan(),
            'providers' => $this->providerCards(),
        ]);
    }

    public function scanners(): View
    {
        return view('ai-security-guardian::settings.scanners', [
            'latestScan' => $this->latestScan(),
            'scanners' => $this->scannerCards(),
        ]);
    }

    public function notifications(): View
    {
        return view('ai-security-guardian::settings.notifications', [
            'latestScan' => $this->latestScan(),
            'channels' => $this->notificationCards(),
        ]);
    }

    public function health(): View
    {
        return view('ai-security-guardian::health', [
            'latestScan' => $this->latestScan(),
            'health' => $this->healthChecks(),
        ]);
    }

    public function help(): View
    {
        return view('ai-security-guardian::help', [
            'latestScan' => $this->latestScan(),
            'commands' => [
                'php artisan ai-security:scan',
                'php artisan ai-security:scan --deep',
                'php artisan ai-security:report',
                'php artisan ai-security:report --format=json',
                'php artisan ai-security:fix --direct',
                'php artisan ai-security:rollback {patch_id}',
            ],
        ]);
    }

    public function fixFinding($id): RedirectResponse
    {
        return back()->with('error', $this->ui()->t('messages.direct_fix_disabled'));
    }

    public function rollbackPatch($id): RedirectResponse
    {
        return back()->with('error', $this->ui()->t('messages.direct_rollback_disabled'));
    }

    private function latestScan(): ?SecurityScan
    {
        return SecurityScan::with(['findings.patches'])->latest('started_at')->first();
    }

    private function scanSummary(?SecurityScan $scan): array
    {
        return [
            'lastScanStatus' => $scan?->status ?? 'not_run',
            'lastScanTime' => $this->formatDate($scan?->started_at),
            'provider' => $scan?->provider ?? config('ai-security-guardian.provider', 'openai'),
            'model' => $scan?->model ?? data_get(config('ai-security-guardian.providers'), config('ai-security-guardian.provider', 'openai').'.model', 'unknown'),
            'duration' => $this->durationLabel($scan?->started_at, $scan?->finished_at),
            'autoFix' => $this->statusBadge(config('ai-security-guardian.auto_fix.enabled', false)),
            'safeMode' => $this->statusBadge(config('ai-security-guardian.auto_fix.safe_fixes_only', true)),
            'privacyMode' => $this->statusBadge(true, $this->ui()->t('dashboard.context_redaction')),
            'scheduler' => $this->statusBadge(config('ai-security-guardian.scan.daily', true)),
        ];
    }

    private function headlineStats(): array
    {
        return [
            'totalFindings' => SecurityFinding::count(),
            'openFindings' => SecurityFinding::where('status', FindingStatus::OPEN->value)->count(),
            'resolvedFindings' => SecurityFinding::whereIn('status', [FindingStatus::FIXED->value, FindingStatus::RESOLVED->value])->count(),
            'acceptedRiskFindings' => SecurityFinding::where('status', FindingStatus::ACCEPTED_RISK->value)->count(),
        ];
    }

    private function scanTrend(): Collection
    {
        return SecurityScan::query()
            ->latest('started_at')
            ->limit(6)
            ->get()
            ->sortBy('started_at')
            ->values()
            ->map(fn (SecurityScan $scan) => [
                'label' => $scan->started_at?->format('M j') ?? $this->ui()->t('common.unknown'),
                'risk' => (int) $scan->risk_score,
                'status' => $scan->status,
            ]);
    }

    private function severityBreakdown(): Collection
    {
        return SecurityFinding::query()
            ->select('severity', DB::raw('COUNT(*) as total'))
            ->groupBy('severity')
            ->get()
            ->map(fn ($row) => [
                'severity' => $row->severity,
                'total' => (int) $row->total,
            ]);
    }

    private function scannerBreakdown(): Collection
    {
        return SecurityFinding::query()
            ->selectRaw("COALESCE(scanner_name, 'Unknown Scanner') as scanner_name, COUNT(*) as total")
            ->groupBy('scanner_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'scanner_name' => $row->scanner_name === 'Unknown Scanner' ? $this->ui()->t('common.unknown') : $row->scanner_name,
                'total' => (int) $row->total,
            ]);
    }

    private function topCategories(): Collection
    {
        return SecurityFinding::query()
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'total' => (int) $row->total,
            ]);
    }

    private function recentCriticalAlerts(): Collection
    {
        return SecurityFinding::query()
            ->with('scan')
            ->where('severity', Severity::CRITICAL->value)
            ->latest('created_at')
            ->limit(5)
            ->get();
    }

    private function recommendedActions(?SecurityScan $scan): array
    {
        $actions = [];

        if (! $scan) {
            return [
                ['label' => $this->ui()->t('dashboard.actions.run_first_scan.label'), 'detail' => $this->ui()->t('dashboard.actions.run_first_scan.detail')],
                ['label' => $this->ui()->t('dashboard.actions.review_provider_settings.label'), 'detail' => $this->ui()->t('dashboard.actions.review_provider_settings.detail')],
                ['label' => $this->ui()->t('dashboard.actions.enable_notifications.label'), 'detail' => $this->ui()->t('dashboard.actions.enable_notifications.detail')],
            ];
        }

        if ($this->headlineStats()['openFindings'] > 0) {
            $actions[] = ['label' => $this->ui()->t('dashboard.actions.triage_open_findings.label'), 'detail' => $this->ui()->t('dashboard.actions.triage_open_findings.detail')];
        }

        if ($scan->risk_score > 10) {
            $actions[] = ['label' => $this->ui()->t('dashboard.actions.schedule_deep_scan.label'), 'detail' => $this->ui()->t('dashboard.actions.schedule_deep_scan.detail')];
        }

        if (config('ai-security-guardian.auto_fix.enabled', false)) {
            $actions[] = ['label' => $this->ui()->t('dashboard.actions.review_safe_fix_queue.label'), 'detail' => $this->ui()->t('dashboard.actions.review_safe_fix_queue.detail')];
        }

        return $actions ?: [
            ['label' => $this->ui()->t('dashboard.actions.monitor_next_scan.label'), 'detail' => $this->ui()->t('dashboard.actions.monitor_next_scan.detail')],
        ];
    }

    private function securityScore(?SecurityScan $scan): int
    {
        if (! $scan) {
            return 100;
        }

        $riskPenalty = min(100, (int) $scan->risk_score * 4);

        return max(0, 100 - $riskPenalty);
    }

    private function applyScanFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->string('provider'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('started_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('started_at', '<=', $request->date('date_to'));
        }

        if ($request->filled('risk_min')) {
            $query->where('risk_score', '>=', (int) $request->input('risk_min'));
        }

        if ($request->filled('risk_max')) {
            $query->where('risk_score', '<=', (int) $request->input('risk_max'));
        }

        if ($request->filled('severity')) {
            $severity = $request->string('severity')->toString();
            $query->whereHas('findings', fn (Builder $findingQuery) => $findingQuery->where('severity', $severity));
        }
    }

    private function scanFilterState(Request $request): array
    {
        return [
            'status' => $request->string('status')->toString(),
            'severity' => $request->string('severity')->toString(),
            'provider' => $request->string('provider')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'risk_min' => $request->string('risk_min')->toString(),
            'risk_max' => $request->string('risk_max')->toString(),
        ];
    }

    private function applyFindingFilters(Builder $query, Request $request): void
    {
        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('scanner')) {
            $query->where('scanner_name', $request->string('scanner'));
        }

        if ($request->filled('package')) {
            $query->where('package_name', 'like', '%' . $request->string('package') . '%');
        }

        if ($request->filled('cve')) {
            $query->where('cve', 'like', '%' . $request->string('cve') . '%');
        }

        if ($request->filled('auto_fix')) {
            $query->where('safe_auto_fix_allowed', $request->boolean('auto_fix'));
        }

        if ($request->filled('human_review')) {
            $query->where('human_review_required', $request->boolean('human_review'));
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function (Builder $subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('affected_file', 'like', "%{$search}%")
                    ->orWhere('package_name', 'like', "%{$search}%")
                    ->orWhere('cve', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }
    }

    private function findingFilterState(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'severity' => $request->string('severity')->toString(),
            'status' => $request->string('status')->toString(),
            'category' => $request->string('category')->toString(),
            'scanner' => $request->string('scanner')->toString(),
            'package' => $request->string('package')->toString(),
            'cve' => $request->string('cve')->toString(),
            'auto_fix' => $request->string('auto_fix')->toString(),
            'human_review' => $request->string('human_review')->toString(),
        ];
    }

    private function severityOptions(): array
    {
        return array_map(fn (Severity $severity) => $severity->value, Severity::cases());
    }

    private function findingStatusOptions(): array
    {
        return [
            FindingStatus::OPEN->value,
            FindingStatus::IN_REVIEW->value,
            FindingStatus::FIXED->value,
            FindingStatus::RESOLVED->value,
            FindingStatus::ACCEPTED_RISK->value,
            FindingStatus::FALSE_POSITIVE->value,
            FindingStatus::IGNORED->value,
        ];
    }

    private function scannerNameOptions(): array
    {
        return $this->scannerCards()->pluck('name')->all();
    }

    private function findingTimeline(SecurityFinding $finding): array
    {
        $timeline = [
            [
                'label' => $this->ui()->t('findings.timeline_created'),
                'value' => $this->formatDate($finding->created_at),
                'type' => 'created',
            ],
            [
                'label' => $this->ui()->t('findings.timeline_updated'),
                'value' => $this->formatDate($finding->updated_at),
                'type' => 'updated',
            ],
        ];

        foreach ($finding->patches as $patch) {
            $timeline[] = [
                'label' => $this->ui()->t('patches.patch_status') . ': ' . $this->ui()->patchStatus($patch->status ?: 'pending'),
                'value' => $this->formatDate($patch->created_at),
                'type' => 'patch',
            ];
        }

        return $timeline;
    }

    private function findingActionSuggestions(SecurityFinding $finding): array
    {
        return [
            [
                'label' => 'Mark in review',
                'status' => FindingStatus::IN_REVIEW->value,
            ],
            [
                'label' => 'Mark fixed',
                'status' => FindingStatus::FIXED->value,
            ],
            [
                'label' => 'Accept risk',
                'status' => FindingStatus::ACCEPTED_RISK->value,
            ],
            [
                'label' => 'Mark false positive',
                'status' => FindingStatus::FALSE_POSITIVE->value,
            ],
            [
                'label' => 'Ignore',
                'status' => FindingStatus::IGNORED->value,
            ],
        ];
    }

    private function filterReportFindings(Collection $findings, Request $request): Collection
    {
        return $findings
            ->when($request->filled('severity'), fn (Collection $collection) => $collection->filter(fn (FindingDto $finding) => $finding->severity->value === $request->string('severity')->toString()))
            ->when($request->filled('category'), fn (Collection $collection) => $collection->filter(fn (FindingDto $finding) => $finding->category === $request->string('category')->toString()))
            ->values();
    }

    private function buildScanResult(SecurityScan $scan): ScanResult
    {
        return new ScanResult(
            $scan->started_at,
            $scan->finished_at ?? $scan->started_at,
            $scan->findings->map(fn (SecurityFinding $finding) => $this->findingToDto($finding)),
            $scan->risk_score,
            $scan->summary ?? [],
            $scan->provider,
            $scan->model
        );
    }

    private function findingToDto(SecurityFinding $finding): FindingDto
    {
        return new FindingDto(
            title: $finding->title,
            description: $finding->description,
            severity: Severity::tryFrom($finding->severity) ?? Severity::INFO,
            category: $finding->category,
            affectedFile: $finding->affected_file,
            affectedLine: $finding->affected_line,
            packageName: $finding->package_name,
            cve: $finding->cve,
            advisoryUrl: $finding->advisory_url,
            recommendation: $finding->recommendation,
            safeAutoFixAllowed: (bool) $finding->safe_auto_fix_allowed,
            humanReviewRequired: (bool) $finding->human_review_required,
            status: FindingStatus::tryFrom($finding->status) ?? FindingStatus::OPEN,
            businessImpact: $finding->business_impact,
            technicalImpact: $finding->technical_impact,
            testPlan: $finding->test_plan,
            references: $finding->references ?? []
        );
    }

    private function downloadReportForScan(SecurityScan $scan, string $format)
    {
        $report = $this->buildScanResult($scan);
        $format = strtolower($format);

        if ($format === 'json') {
            $content = (new JsonReportGenerator())->generate($report);
            $filename = 'ai-security-report-' . $scan->id . '.json';
            $mime = 'application/json';
        } else {
            $content = (new MarkdownReportGenerator())->generate($report);
            $filename = 'ai-security-report-' . $scan->id . '.md';
            $mime = 'text/markdown; charset=UTF-8';
        }

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function storeGeneratedReports(SecurityScan $scan): void
    {
        $report = $this->buildScanResult($scan);
        $directory = storage_path('app/ai-security-reports');
        File::ensureDirectoryExists($directory);

        File::put($directory . '/latest.json', (new JsonReportGenerator())->generate($report));
        File::put($directory . '/latest.md', (new MarkdownReportGenerator())->generate($report));
    }

    private function latestGeneratedReportPaths(): array
    {
        $directory = storage_path('app/ai-security-reports');

        return [
            'json' => File::exists($directory . '/latest.json') ? $directory . '/latest.json' : null,
            'markdown' => File::exists($directory . '/latest.md') ? $directory . '/latest.md' : null,
        ];
    }

    private function providerCards(): Collection
    {
        $providers = config('ai-security-guardian.providers', []);

        return collect($providers)->map(function (array $settings, string $name) {
            $apiKey = (string) ($settings['api_key'] ?? '');

            return [
                'name' => $name,
                'label' => Str::headline($name),
                'enabled' => ! empty($apiKey) || $name === 'custom',
                'configured' => ! empty($apiKey) || ! empty($settings['base_url'] ?? null),
                'apiKey' => $this->maskSecret($apiKey),
                'model' => $settings['model'] ?? 'n/a',
                'baseUrl' => $settings['base_url'] ?? null,
                'timeout' => $settings['timeout'] ?? 120,
                'retries' => $settings['retries'] ?? 3,
                'privacyMode' => true,
                'lastTestStatus' => $this->ui()->t('settings.providers.not_supported'),
            ];
        });
    }

    private function scannerCards(): Collection
    {
        $catalog = [
            ['name' => 'Composer Audit Scanner', 'status' => 'active', 'mvp' => 'v1', 'category' => 'dependency risks', 'description' => 'Runs composer audit and surfaces known vulnerabilities.'],
            ['name' => 'Composer Outdated Scanner', 'status' => 'planned', 'mvp' => 'v2', 'category' => 'dependency hygiene', 'description' => 'Would flag outdated packages with upgrade pressure.'],
            ['name' => 'Environment Configuration Scanner', 'status' => 'active', 'mvp' => 'v1', 'category' => 'configuration', 'description' => 'Finds risky .env and runtime configuration values.'],
            ['name' => 'Blade Security Scanner', 'status' => 'active', 'mvp' => 'v1', 'category' => 'xss', 'description' => 'Detects unescaped Blade output.'],
            ['name' => 'Code Analysis Scanner', 'status' => 'active', 'mvp' => 'v1', 'category' => 'code safety', 'description' => 'Finds dangerous PHP functions, raw SQL, and mass assignment risks.'],
            ['name' => 'Route Security Scanner', 'status' => 'active', 'mvp' => 'v1', 'category' => 'access control', 'description' => 'Checks sensitive routes for missing auth and throttling.'],
            ['name' => 'File Upload Security Scanner', 'status' => 'active', 'mvp' => 'v1', 'category' => 'upload safety', 'description' => 'Flags unvalidated file upload flows.'],
            ['name' => 'AST Tenant Isolation Scanner', 'status' => 'active', 'mvp' => 'v2', 'category' => 'multi-tenancy', 'description' => 'Heuristic tenant isolation checks for SaaS ERP apps.'],
            ['name' => 'AST Race Condition Scanner', 'status' => 'active', 'mvp' => 'v2', 'category' => 'concurrency', 'description' => 'Looks for state-changing controller paths without guardrails.'],
            ['name' => 'AST Webhook Security Scanner', 'status' => 'active', 'mvp' => 'v2', 'category' => 'integration', 'description' => 'Heuristic checks for webhook verification and signing.'],
            ['name' => 'AST Queue Security Scanner', 'status' => 'active', 'mvp' => 'v2', 'category' => 'asynchronous jobs', 'description' => 'Inspects jobs for security-sensitive queue usage.'],
            ['name' => 'AST Log Security Scanner', 'status' => 'active', 'mvp' => 'v2', 'category' => 'observability', 'description' => 'Looks for sensitive data in logs.'],
            ['name' => 'AST ERP Business Logic Scanner', 'status' => 'active', 'mvp' => 'v2', 'category' => 'erp logic', 'description' => 'Targets invoice, payment, order, and journal controllers.'],
            ['name' => 'Laravel Config Scanner', 'status' => 'planned', 'mvp' => 'v2', 'category' => 'configuration', 'description' => 'Could inspect config files for insecure defaults.'],
        ];

        $findingCounts = SecurityFinding::query()
            ->selectRaw("COALESCE(scanner_name, 'Unknown Scanner') as scanner_name, COUNT(*) as total")
            ->groupBy('scanner_name')
            ->pluck('total', 'scanner_name');

        return collect($catalog)->map(function (array $scanner) use ($findingCounts) {
            $scanner['findings'] = (int) ($findingCounts[$scanner['name']] ?? 0);
            $scanner['lastRun'] = $scanner['status'] === 'active' ? $this->formatDate($this->latestScan()?->started_at) : null;
            return $scanner;
        });
    }

    private function notificationCards(): Collection
    {
        $mail = config('ai-security-guardian.notifications.mail', []);
        $telegram = config('ai-security-guardian.notifications.telegram', []);
        $slack = config('ai-security-guardian.notifications.slack', []);

        return collect([
            [
                'channel' => 'Email',
                'enabled' => (bool) ($mail['enabled'] ?? false),
                'destination' => $this->maskEmail($mail['to'] ?? null),
                'critical' => true,
                'daily' => true,
                'weekly' => false,
                'lastStatus' => $this->ui()->t('settings.notifications.not_tracked'),
            ],
            [
                'channel' => 'Telegram',
                'enabled' => (bool) ($telegram['enabled'] ?? false),
                'destination' => $this->maskToken($telegram['chat_id'] ?? null),
                'critical' => true,
                'daily' => false,
                'weekly' => false,
                'lastStatus' => $this->ui()->t('settings.notifications.not_tracked'),
            ],
            [
                'channel' => 'Slack',
                'enabled' => (bool) ($slack['enabled'] ?? false),
                'destination' => $this->maskUrl($slack['webhook_url'] ?? null),
                'critical' => true,
                'daily' => false,
                'weekly' => false,
                'lastStatus' => $this->ui()->t('settings.notifications.not_tracked'),
            ],
        ]);
    }

    private function healthChecks(): Collection
    {
        $connection = config('ai-security-guardian.database.connection');

        try {
            DB::connection($connection)->getPdo();
            $databaseStatus = 'connected';
        } catch (\Throwable $e) {
            $databaseStatus = 'unavailable';
        }

        return collect([
            ['label' => $this->ui()->t('health.package_version'), 'value' => 'dev'],
            ['label' => $this->ui()->t('health.laravel_version'), 'value' => app()->version()],
            ['label' => $this->ui()->t('health.php_version'), 'value' => PHP_VERSION],
            ['label' => $this->ui()->t('health.database_connection'), 'value' => $databaseStatus === 'connected' ? $this->ui()->t('health.connected') : $this->ui()->t('health.unavailable')],
            ['label' => $this->ui()->t('health.queue_status'), 'value' => config('queue.default', 'sync')],
            ['label' => $this->ui()->t('health.scheduler_status'), 'value' => config('ai-security-guardian.scan.daily', true) ? $this->ui()->t('common.enabled') : $this->ui()->t('common.disabled')],
            ['label' => $this->ui()->t('health.last_scan'), 'value' => $this->formatDate($this->latestScan()?->started_at) ?? $this->ui()->t('health.no_scans_yet')],
            ['label' => $this->ui()->t('health.storage_writable'), 'value' => is_writable(storage_path('app')) ? $this->ui()->t('common.yes') : $this->ui()->t('common.no')],
            ['label' => $this->ui()->t('health.config_published'), 'value' => file_exists(config_path('ai-security-guardian.php')) ? $this->ui()->t('common.yes') : $this->ui()->t('common.no')],
            [
                'label' => $this->ui()->t('health.migrations_status'),
                'value' => $this->migrationsReady() ? $this->ui()->t('health.ready') : $this->ui()->t('health.missing_tables'),
            ],
            ['label' => $this->ui()->t('health.ai_provider_configured'), 'value' => $this->providerConfigured() ? $this->ui()->t('common.yes') : $this->ui()->t('common.no')],
            ['label' => $this->ui()->t('health.notification_configured'), 'value' => $this->notificationsConfigured() ? $this->ui()->t('common.yes') : $this->ui()->t('common.no')],
        ]);
    }

    private function migrationsReady(): bool
    {
        $connection = config('ai-security-guardian.database.connection');

        return Schema::connection($connection)->hasTable('security_scans')
            && Schema::connection($connection)->hasTable('security_findings')
            && Schema::connection($connection)->hasTable('security_patches');
    }

    private function providerConfigured(): bool
    {
        $provider = config('ai-security-guardian.provider', 'openai');
        $settings = config("ai-security-guardian.providers.{$provider}", []);

        return ! empty($settings['api_key']) || ! empty($settings['base_url']);
    }

    private function notificationsConfigured(): bool
    {
        $notifications = config('ai-security-guardian.notifications', []);

        return (bool) data_get($notifications, 'mail.enabled')
            || (bool) data_get($notifications, 'telegram.enabled')
            || (bool) data_get($notifications, 'slack.enabled');
    }

    private function scanTrendSeries(): Collection
    {
        return $this->scanTrend();
    }

    private function durationLabel(?Carbon $start, ?Carbon $end): string
    {
        if (! $start || ! $end) {
            return $this->ui()->t('health.pending');
        }

        $seconds = max(0, $start->diffInSeconds($end));

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return $remaining ? "{$minutes}m {$remaining}s" : "{$minutes}m";
    }

    private function formatDate(?Carbon $date): ?string
    {
        return $date?->format('M j, Y H:i');
    }

    private function statusBadge(bool $enabled, ?string $label = null): array
    {
        return [
            'enabled' => $enabled,
            'label' => $enabled ? ($label ?? $this->ui()->t('common.enabled')) : $this->ui()->t('common.disabled'),
        ];
    }

    private function maskSecret(?string $value): string
    {
        if (! $value) {
            return $this->ui()->t('common.not_available');
        }

        if (strlen($value) <= 8) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 4) . '****' . substr($value, -4);
    }

    private function maskEmail(?string $value): string
    {
        if (! $value) {
            return $this->ui()->t('common.not_available');
        }

        [$user, $domain] = array_pad(explode('@', $value, 2), 2, null);

        if (! $domain) {
            return $this->maskSecret($value);
        }

        return substr($user, 0, 2) . '***@' . $domain;
    }

    private function maskToken(?string $value): string
    {
        return $value ? $this->maskSecret((string) $value) : $this->ui()->t('common.not_available');
    }

    private function maskUrl(?string $value): string
    {
        if (! $value) {
            return $this->ui()->t('common.not_available');
        }

        $parts = parse_url($value);

        if (! $parts || empty($parts['host'])) {
            return $this->maskSecret($value);
        }

        return ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . '/***';
    }
}
