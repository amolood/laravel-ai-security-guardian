@extends('ai-security-guardian::layout')

@section('title', $ui->t('reports.eyebrow'))

@section('content')
    @php
        $summaryText = $report
            ? $ui->t('reports.summary_text', [
                'started' => optional($report->startedAt)->format('M j, Y H:i'),
                'finished' => optional($report->finishedAt)->format('M j, Y H:i'),
                'risk' => $report->riskScore,
                'count' => $report->findings->count(),
            ])
            : $ui->t('reports.no_report_available');
    @endphp

    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('reports.eyebrow') }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('reports.title') }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">
                        {{ $ui->t('reports.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('ai-security.reports.generate') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-500" {{ $latestScan ? '' : 'disabled' }} data-confirm="{{ $ui->t('reports.generate_confirm') }}">{{ $ui->t('reports.generate') }}</button>
                    </form>
                    <a href="{{ route('ai-security.reports.download', ['format' => 'json']) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.download_json') }}</a>
                    <a href="{{ route('ai-security.reports.download', ['format' => 'md']) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.download_markdown') }}</a>
                    <button type="button" data-copy="#report-summary" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.copy_summary') }}</button>
                </div>
            </div>
        </div>

        <form method="GET" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('reports.filters.severity') }}</label>
                    <select name="severity" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('reports.filters.all_severities') }}</option>
                        @foreach (['critical', 'high', 'medium', 'low', 'info'] as $severity)
                            <option value="{{ $severity }}" @selected(($filters['severity'] ?? '') === $severity)>{{ $ui->severity($severity) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('reports.filters.category') }}</label>
                    <select name="category" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('reports.filters.all_categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-950">{{ $ui->t('common.apply_filters') }}</button>
                <a href="{{ route('ai-security.reports.index') }}" class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.reset') }}</a>
            </div>
        </form>

        @if ($report)
            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-6">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('reports.latest_report') }}</p>
                                <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('reports.security_scan_summary') }}</h2>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @include('ai-security-guardian::partials.badge', ['variant' => 'provider', 'label' => $report->provider])
                                @include('ai-security-guardian::partials.badge', ['variant' => 'status', 'label' => $report->model])
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-4">
                            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('dashboard.risk_score'), 'value' => $report->riskScore, 'caption' => $ui->t('common.generated'), 'tone' => 'amber'])
                            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('reports.security_scan_summary'), 'value' => $report->findings->count(), 'caption' => $ui->t('reports.filtered_findings'), 'tone' => 'neutral'])
                            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('scans.started'), 'value' => optional($report->startedAt)->format('M j'), 'caption' => optional($report->startedAt)->format('H:i') ?? '—', 'tone' => 'sky'])
                            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('scans.finished'), 'value' => optional($report->finishedAt)->format('M j'), 'caption' => optional($report->finishedAt)->format('H:i') ?? '—', 'tone' => 'accent'])
                        </div>

                        <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <label class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('reports.summary_preview') }}</label>
                            <textarea id="report-summary" readonly rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">{{ $summaryText }}</textarea>
                        </div>

                        <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('reports.html_report') }}</div>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $ui->t('reports.html_not_supported') }}</p>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                            <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('reports.filtered_findings') }}</h2>
                        </div>
                        @if ($filteredFindings->isEmpty())
                            <div class="p-6">
                                @include('ai-security-guardian::partials.empty-state', [
                                    'icon' => '∅',
                                    'title' => $ui->t('reports.no_filtered_findings'),
                                    'text' => $ui->t('reports.no_filtered_findings_text'),
                                ])
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr>
                                            <th>{{ $ui->t('findings.severity') }}</th>
                                            <th>{{ $ui->t('findings.title') }}</th>
                                            <th>{{ $ui->t('findings.category') }}</th>
                                            <th>{{ $ui->t('findings.affected_file') }}</th>
                                            <th>{{ $ui->t('findings.recommended_fix') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($filteredFindings as $finding)
                                            <tr>
                                                <td>@include('ai-security-guardian::partials.badge', ['variant' => $finding->severity->value, 'label' => $ui->severity($finding->severity->value)])</td>
                                                <td class="text-sm font-black text-slate-950 dark:text-white">{{ $finding->title }}</td>
                                                <td class="text-sm text-slate-600 dark:text-slate-300">{{ $finding->category }}</td>
                                                <td class="text-sm text-slate-600 dark:text-slate-300">{{ $finding->affectedFile ?? '—' }}</td>
                                                <td class="text-sm text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($finding->recommendation ?? '—', 120) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('reports.report_availability') }}</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-400">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('reports.json_file') }}: {{ $latestReportPaths['json'] ? $ui->t('common.generated') : $ui->t('common.not_generated_yet') }}</div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('reports.markdown_file') }}: {{ $latestReportPaths['markdown'] ? $ui->t('common.generated') : $ui->t('common.not_generated_yet') }}</div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('common.html_export') }}: {{ $ui->t('common.not_available') }}</div>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('reports.safe_export_policy') }}</h2>
                        <div class="mt-4 space-y-3 text-sm leading-7 text-slate-600 dark:text-slate-400">
                            <p>{{ $ui->t('reports.safe_export_policy_text_1') }}</p>
                            <p>{{ $ui->t('reports.safe_export_policy_text_2') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @include('ai-security-guardian::partials.empty-state', [
                'icon' => '⌁',
                'title' => $ui->t('reports.no_report_available'),
                'text' => $ui->t('reports.no_report_available_text'),
                'actionLabel' => $ui->t('reports.run_first_scan'),
                'actionHref' => route('ai-security.scan'),
            ])
        @endif
    </div>
@endsection
