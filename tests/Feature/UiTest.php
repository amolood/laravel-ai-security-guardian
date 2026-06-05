<?php

use Carbon\Carbon;
use Illuminate\Auth\GenericUser;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;
use Abdalmolood\AiSecurityGuardian\Models\SecurityPatch;
use Abdalmolood\AiSecurityGuardian\Models\SecurityScan;

function seedScanWithFinding(): array
{
    $scan = SecurityScan::create([
        'started_at' => Carbon::parse('2026-06-05 10:00:00'),
        'finished_at' => Carbon::parse('2026-06-05 10:08:00'),
        'status' => 'completed',
        'risk_score' => 18,
        'summary' => ['usage' => ['prompt_tokens' => 120]],
        'provider' => 'openai',
        'model' => 'gpt-4.1',
    ]);

    $finding = SecurityFinding::create([
        'scan_id' => $scan->id,
        'scanner_name' => 'Environment Configuration Scanner',
        'severity' => 'critical',
        'category' => 'configuration',
        'package_name' => null,
        'cve' => 'CVE-2026-0001',
        'advisory_url' => 'https://example.com/advisory',
        'affected_file' => '.env',
        'affected_line' => 3,
        'title' => 'APP_DEBUG is enabled in production',
        'description' => 'Sensitive debug output is exposed.',
        'business_impact' => 'Attackers may see secrets and stack traces.',
        'technical_impact' => 'Verbose debug output may expose credentials.',
        'recommendation' => 'Set APP_DEBUG=false.',
        'test_plan' => 'Confirm production pages no longer show debug traces.',
        'references' => ['https://example.com/reference'],
        'status' => 'open',
        'safe_auto_fix_allowed' => true,
        'human_review_required' => false,
    ]);

    SecurityPatch::create([
        'finding_id' => $finding->id,
        'branch_name' => 'ai-security/fix-app-debug',
        'pull_request_url' => 'https://example.com/pr/1',
        'patch_file' => "--- a/.env\n+++ b/.env\n-APP_DEBUG=true\n+APP_DEBUG=false\n",
        'original_file_path' => '.env',
        'backup_path' => storage_path('app/.env.backup'),
        'tests_status' => 'skipped',
        'status' => 'pending',
    ]);

    return [$scan, $finding];
}

it('renders the core ui pages', function () {
    $this->actingAs(new GenericUser([
        'id' => 1,
        'name' => 'Security Admin',
        'email' => 'security@example.com',
    ]));
    seedScanWithFinding();

    $this->get(route('ai-security.dashboard'))->assertOk()->assertSee('Security dashboard');
    $this->get(route('ai-security.scans.index'))->assertOk()->assertSee('Scan history');
    $this->get(route('ai-security.findings.index'))->assertOk()->assertSee('Findings');
    $this->get(route('ai-security.reports.index'))->assertOk()->assertSee('Reports');
    $this->get(route('ai-security.patches.index'))->assertOk()->assertSee('Patch suggestions');
    $this->get(route('ai-security.settings.providers'))->assertOk()->assertSee('AI providers');
    $this->get(route('ai-security.settings.scanners'))->assertOk()->assertSee('Scanner settings');
    $this->get(route('ai-security.settings.notifications'))->assertOk()->assertSee('Notifications');
    $this->get(route('ai-security.health'))->assertOk()->assertSee('Package health');
    $this->get(route('ai-security.help'))->assertOk()->assertSee('How the package works');
});

it('renders the dashboard in arabic with rtl direction', function () {
    $this->actingAs(new GenericUser([
        'id' => 1,
        'name' => 'Security Admin',
        'email' => 'security@example.com',
    ]));
    seedScanWithFinding();

    $this->get(route('ai-security.dashboard', ['lang' => 'ar']))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('لوحة الأمان');
});

it('renders scan and finding detail pages', function () {
    $this->actingAs(new GenericUser([
        'id' => 1,
        'name' => 'Security Admin',
        'email' => 'security@example.com',
    ]));
    [, $finding] = seedScanWithFinding();

    $this->get(route('ai-security.scans.show', $finding->scan_id))->assertOk()->assertSee('Scan detail');
    $this->get(route('ai-security.findings.show', $finding))->assertOk()->assertSee('Finding Detail');
});

it('updates finding status safely', function () {
    $this->actingAs(new GenericUser([
        'id' => 1,
        'name' => 'Security Admin',
        'email' => 'security@example.com',
    ]));
    [, $finding] = seedScanWithFinding();

    $this->post(route('ai-security.findings.status', $finding), [
        'status' => 'in_review',
    ])->assertRedirect();

    expect($finding->fresh()->status)->toBe('in_review');
});
