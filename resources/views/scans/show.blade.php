@extends('ai-security-guardian::layout')

@section('title', $ui->t('scans.detail'))

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('scans.detail') }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('scans.detail') }} #{{ $scan->id }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">
                        {{ $ui->t('scans.completed_in', ['duration' => $duration, 'score' => $scan->risk_score, 'count' => $findings->count()]) }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('ai-security.scans.report', ['scan' => $scan, 'format' => 'json']) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.download_json') }}</a>
                    <a href="{{ route('ai-security.scans.report', ['scan' => $scan, 'format' => 'md']) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.download_markdown') }}</a>
                    <a href="{{ route('ai-security.findings.index', ['scan' => $scan->id]) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-500">{{ $ui->t('findings.finding_queue') }}</a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('dashboard.risk_score'), 'value' => $scan->risk_score, 'caption' => $ui->t('common.available'), 'tone' => 'amber'])
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('dashboard.provider'), 'value' => $scan->provider, 'caption' => $scan->model, 'tone' => 'neutral'])
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('scans.started'), 'value' => optional($scan->started_at)->format('M j'), 'caption' => optional($scan->started_at)->format('H:i') ?? '—', 'tone' => 'sky'])
            @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('scans.finished'), 'value' => optional($scan->finished_at)->format('M j'), 'caption' => optional($scan->finished_at)->format('H:i') ?? '—', 'tone' => 'accent'])
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="space-y-6">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('scans.scan') }} {{ $ui->t('reports.security_scan_summary') }}</h2>
                    <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('scans.status') }}: {{ $ui->scanStatus($scan->status) }}</div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('reports.summary_preview') }}: {{ ! empty($scan->summary) ? $ui->t('common.available') : $ui->t('common.not_available') }}</div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('reports.latest_report') }}</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">
                        {{ $ui->t('reports.safe_export_policy_text_1') }}
                    </p>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.finding_queue') }}</h2>
                </div>

                @if ($findings->isEmpty())
                    <div class="p-6">
                        @include('ai-security-guardian::partials.empty-state', [
                            'icon' => '✓',
                            'title' => $ui->t('scans.no_scans'),
                            'text' => $ui->t('scans.no_scans_text'),
                        ])
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>{{ $ui->t('findings.severity') }}</th>
                                    <th>{{ $ui->t('findings.title') }}</th>
                                    <th>{{ $ui->t('findings.status_update') }}</th>
                                    <th>{{ $ui->t('findings.package') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($findings as $finding)
                                    <tr>
                                        <td>@include('ai-security-guardian::partials.badge', ['variant' => $finding->severity, 'label' => $ui->severity($finding->severity)])</td>
                                        <td>
                                            <a href="{{ route('ai-security.findings.show', $finding) }}" class="text-sm font-black text-slate-950 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">{{ $finding->title }}</a>
                                        </td>
                                        <td>@include('ai-security-guardian::partials.badge', ['variant' => 'status', 'label' => $ui->findingStatus($finding->status)])</td>
                                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $finding->package_name ?? $ui->t('common.not_specified') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
