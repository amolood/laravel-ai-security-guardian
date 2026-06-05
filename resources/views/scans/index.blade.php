@extends('ai-security-guardian::layout')

@section('title', $ui->t('scans.eyebrow'))

@section('content')
    @php
        $currentFilters = $filters ?? [];
    @endphp

    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('scans.eyebrow') }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('scans.title') }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">
                        {{ $ui->t('scans.subtitle') }}
                    </p>
                </div>

                <a href="{{ route('ai-security.scan') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-500" data-confirm="{{ $ui->t('common.run_scan_now') }}">{{ $ui->t('common.run_scan') }}</a>
            </div>
        </div>

        <form method="GET" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('scans.filters.status') }}</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('scans.filters.all') }}</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected(($currentFilters['status'] ?? '') === $status)>{{ $ui->scanStatus($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('scans.filters.severity') }}</label>
                    <select name="severity" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('scans.filters.any') }}</option>
                        @foreach (['critical', 'high', 'medium', 'low', 'info'] as $severity)
                            <option value="{{ $severity }}" @selected(($currentFilters['severity'] ?? '') === $severity)>{{ $ui->severity($severity) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('scans.filters.provider') }}</label>
                    <select name="provider" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('scans.filters.all_providers') }}</option>
                        @foreach ($providerOptions as $provider)
                            <option value="{{ $provider }}" @selected(($currentFilters['provider'] ?? '') === $provider)>{{ $provider }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('scans.filters.from') }}</label>
                    <input type="date" name="date_from" value="{{ $currentFilters['date_from'] ?? '' }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('scans.filters.to') }}</label>
                    <input type="date" name="date_to" value="{{ $currentFilters['date_to'] ?? '' }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('scans.filters.risk_range') }}</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" min="0" name="risk_min" placeholder="{{ $ui->t('scans.filters.min') }}" value="{{ $currentFilters['risk_min'] ?? '' }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <input type="number" min="0" name="risk_max" placeholder="{{ $ui->t('scans.filters.max') }}" value="{{ $currentFilters['risk_max'] ?? '' }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white shadow-sm dark:bg-white dark:text-slate-950">{{ $ui->t('common.apply_filters') }}</button>
                <a href="{{ route('ai-security.scans.index') }}" class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.reset') }}</a>
            </div>
        </form>

        <div class="rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('scans.stored_scan_runs') }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('scans.stored_scan_runs_desc') }}</p>
                    </div>
                    @include('ai-security-guardian::partials.badge', ['variant' => 'provider', 'label' => $ui->t('scans.history_badge')])
                </div>
            </div>

            @if ($scans->isEmpty())
                <div class="p-6">
                    @include('ai-security-guardian::partials.empty-state', [
                        'icon' => '⌁',
                        'title' => $ui->t('scans.no_scans'),
                        'text' => $ui->t('scans.no_scans_text'),
                        'actionLabel' => $ui->t('scans.first_scan'),
                        'actionHref' => route('ai-security.scan'),
                    ])
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.scan') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.started') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.finished') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.duration') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.status') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.risk') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.provider') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.findings') }}</th>
                                <th class="whitespace-nowrap">{{ $ui->t('scans.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scans as $scan)
                                <tr class="align-top">
                                    <td>
                                        <div class="text-sm font-black text-slate-950 dark:text-white">#{{ $scan->id }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $scan->model }}</div>
                                    </td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">{{ optional($scan->started_at)->format('M j, Y H:i') ?? '—' }}</td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">{{ optional($scan->finished_at)->format('M j, Y H:i') ?? $ui->scanStatus('running') }}</td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">{{ $scan->finished_at ? $scan->started_at->diffForHumans($scan->finished_at, true) : '—' }}</td>
                                    <td>
                                        @include('ai-security-guardian::partials.badge', [
                                            'variant' => match ($scan->status) {
                                                'completed' => 'success',
                                                'running', 'queued' => 'warning',
                                                'failed' => 'critical',
                                                default => 'neutral',
                                            },
                                            'label' => $ui->scanStatus($scan->status),
                                        ])
                                    </td>
                                    <td class="text-sm font-black text-slate-950 dark:text-white">{{ $scan->risk_score }}</td>
                                    <td>
                                        <div class="space-y-2">
                                            @include('ai-security-guardian::partials.badge', ['variant' => 'provider', 'label' => $scan->provider])
                                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $scan->model }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            @include('ai-security-guardian::partials.badge', ['variant' => 'critical', 'label' => 'C: ' . $scan->critical_findings_count])
                                            @include('ai-security-guardian::partials.badge', ['variant' => 'high', 'label' => 'H: ' . $scan->high_findings_count])
                                            @include('ai-security-guardian::partials.badge', ['variant' => 'medium', 'label' => 'M: ' . $scan->medium_findings_count])
                                            @include('ai-security-guardian::partials.badge', ['variant' => 'low', 'label' => 'L: ' . $scan->low_findings_count])
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('ai-security.scans.show', $scan) }}" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.view') }}</a>
                                            <a href="{{ route('ai-security.scans.report', ['scan' => $scan, 'format' => 'json']) }}" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.download_json') }}</a>
                                            <a href="{{ route('ai-security.scans.report', ['scan' => $scan, 'format' => 'md']) }}" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">MD</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                    {{ $scans->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
