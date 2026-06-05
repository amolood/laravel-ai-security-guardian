@extends('ai-security-guardian::layout')

@section('title', $ui->t('dashboard.eyebrow'))

@section('content')
    @php
        $statusVariant = match ($scanSummary['lastScanStatus'] ?? 'not_run') {
            'completed', 'fixed' => 'success',
            'running', 'queued' => 'warning',
            'failed' => 'critical',
            default => 'neutral',
        };
    @endphp

    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-4xl">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('dashboard.eyebrow') }}</p>
                    <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">{{ $ui->t('dashboard.title') }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
                        {{ $ui->t('dashboard.subtitle') }}
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[420px]">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.latest_scan') }}</div>
                        <div class="mt-2 flex items-center gap-2">
                            @include('ai-security-guardian::partials.badge', ['variant' => $statusVariant, 'label' => $ui->scanStatus($scanSummary['lastScanStatus'] ?? 'not_run')])
                        </div>
                        <div class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ $scanSummary['lastScanTime'] ?? $ui->t('common.no_data') }}</div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.security_score') }}</div>
                        <div class="mt-2 text-4xl font-black tracking-tight text-slate-950 dark:text-white">{{ $securityScore }}</div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-emerald-500 w-[{{ $securityScore }}%]"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('dashboard.total_findings'), 'value' => $stats['totalFindings'], 'caption' => $ui->t('dashboard.all_stored_findings'), 'tone' => 'neutral'])
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('dashboard.open_findings'), 'value' => $stats['openFindings'], 'caption' => $ui->t('dashboard.needs_triage'), 'tone' => 'rose'])
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('dashboard.critical_findings'), 'value' => data_get($severityBreakdown->firstWhere('severity', 'critical'), 'total', 0), 'caption' => $ui->t('dashboard.highest_urgency'), 'tone' => 'rose'])
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('dashboard.accepted_risk'), 'value' => $stats['acceptedRiskFindings'], 'caption' => $ui->t('dashboard.explicitly_reviewed'), 'tone' => 'amber'])
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
            <div class="space-y-6">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.operational_snapshot') }}</p>
                            <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('dashboard.current_scan_settings') }}</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @include('ai-security-guardian::partials.badge', ['variant' => $scanSummary['autoFix']['enabled'] ? 'success' : 'neutral', 'label' => $scanSummary['autoFix']['label'] ?? $ui->t('common.enabled')])
                            @include('ai-security-guardian::partials.badge', ['variant' => $scanSummary['safeMode']['enabled'] ? 'success' : 'warning', 'label' => $scanSummary['safeMode']['label'] ?? $ui->t('layout.safe_mode')])
                            @include('ai-security-guardian::partials.badge', ['variant' => $scanSummary['privacyMode']['enabled'] ? 'success' : 'neutral', 'label' => $scanSummary['privacyMode']['label'] ?? $ui->t('common.enabled')])
                            @include('ai-security-guardian::partials.badge', ['variant' => $scanSummary['scheduler']['enabled'] ? 'success' : 'neutral', 'label' => $scanSummary['scheduler']['label'] ?? 'Scheduler'])
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.provider') }}</div>
                            <div class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $scanSummary['provider'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.model') }}</div>
                            <div class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $scanSummary['model'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.duration') }}</div>
                            <div class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $scanSummary['duration'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.risk_score') }}</div>
                            <div class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $latestScan->risk_score ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.trend') }}</p>
                            <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('dashboard.risk_trend') }}</h2>
                        </div>
                        <a href="{{ route('ai-security.scans.index') }}" class="text-sm font-bold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">{{ $ui->t('dashboard.view_history') }}</a>
                    </div>

                    <div class="mt-6 space-y-4">
                        @forelse ($scanTrend as $point)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $point['label'] }}</span>
                                    <span class="text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.risk_score') }} {{ $point['risk'] }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-cyan-500 to-sky-500 w-[{{ min(100, max(8, $point['risk'] * 8)) }}%]"></div>
                                </div>
                            </div>
                        @empty
                            @include('ai-security-guardian::partials.empty-state', [
                                'icon' => '⇣',
                                'title' => $ui->t('dashboard.no_trend_data'),
                                'text' => $ui->t('dashboard.run_scan_prompt'),
                            ])
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.top_categories') }}</p>
                    <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('dashboard.risk_concentration') }}</h2>

                    <div class="mt-6 space-y-4">
                        @forelse ($topCategories as $category)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $category['category'] }}</span>
                                    <span class="text-slate-500 dark:text-slate-400">{{ $category['total'] }}</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                    <div class="h-full rounded-full bg-amber-500 w-[{{ min(100, $category['total'] * 20) }}%]"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.no_categories') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.recent_critical_alerts') }}</p>
                    <div class="mt-4 space-y-4">
                        @forelse ($recentCriticalAlerts as $finding)
                            <a href="{{ route('ai-security.findings.show', $finding) }}" class="block rounded-3xl border border-rose-500/20 bg-rose-500/10 p-4 transition hover:-translate-y-0.5 hover:bg-rose-500/15">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-black text-rose-700 dark:text-rose-300">{{ $finding->title }}</div>
                                        <div class="mt-1 text-xs text-rose-700/80 dark:text-rose-300/80">{{ $finding->scanner_name ?? $ui->t('common.unknown') }}</div>
                                    </div>
                                    @include('ai-security-guardian::partials.badge', ['variant' => 'critical', 'label' => $ui->severity('critical')])
                                </div>
                            </a>
                        @empty
                            @include('ai-security-guardian::partials.empty-state', [
                                'icon' => '✓',
                                'title' => $ui->t('dashboard.no_critical_alerts'),
                                'text' => $ui->t('dashboard.no_critical_alerts_text'),
                            ])
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.recommended_next_actions') }}</p>
                    <div class="mt-4 space-y-3">
                        @foreach ($nextActions as $action)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                                <div class="text-sm font-black text-slate-950 dark:text-white">{{ $action['label'] }}</div>
                                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $action['detail'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($severityBreakdown as $item)
                @include('ai-security-guardian::partials.stat-card', [
                    'label' => $ui->t('dashboard.severity_findings', ['severity' => $ui->severity($item['severity'])]),
                    'value' => $item['total'],
                    'caption' => $ui->t('dashboard.stored_across_scans'),
                    'tone' => match ($item['severity']) {
                        'critical' => 'rose',
                        'high' => 'amber',
                        'medium' => 'sky',
                        'low' => 'accent',
                        default => 'neutral',
                    },
                ])
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($scannerBreakdown as $scanner)
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-black text-slate-950 dark:text-white">{{ $scanner['scanner_name'] }}</div>
                            <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('dashboard.findings_recorded', ['count' => $scanner['total']]) }}</div>
                        </div>
                        @include('ai-security-guardian::partials.badge', ['variant' => 'provider', 'label' => $ui->t('navigation.scanners')])
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
