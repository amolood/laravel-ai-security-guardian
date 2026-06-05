@extends('ai-security-guardian::layout')

@section('title', $ui->t('findings.eyebrow'))

@section('content')
    @php
        $currentFilters = $filters ?? [];
    @endphp

    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('findings.eyebrow') }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.title') }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">
                        {{ $ui->t('findings.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('ai-security.patches.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('findings.patch_suggestions') }}</a>
                    <a href="{{ route('ai-security.scan') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-500" data-confirm="{{ $ui->t('common.run_scan_now') }}">{{ $ui->t('common.run_scan') }}</a>
                </div>
            </div>
        </div>

        <form method="GET" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-3 xl:grid-cols-3">
                <div class="xl:col-span-2">
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('common.search') ?? 'Search' }}</label>
                    <input type="search" name="search" value="{{ $currentFilters['search'] ?? '' }}" placeholder="{{ $ui->t('findings.search_placeholder') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.severity') }}</label>
                    <select name="severity" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('reports.filters.all_severities') }}</option>
                        @foreach ($severityOptions as $severity)
                            <option value="{{ $severity }}" @selected(($currentFilters['severity'] ?? '') === $severity)>{{ $ui->severity($severity) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('scans.filters.status') }}</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('scans.filters.any') }}</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected(($currentFilters['status'] ?? '') === $status)>{{ $ui->findingStatus($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.category') }}</label>
                    <input type="text" name="category" value="{{ $currentFilters['category'] ?? '' }}" placeholder="{{ $ui->t('findings.category') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.scanner') }}</label>
                    <select name="scanner" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('scans.filters.any') }}</option>
                        @foreach ($scannerOptions as $scanner)
                            <option value="{{ $scanner }}" @selected(($currentFilters['scanner'] ?? '') === $scanner)>{{ $scanner }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.package') }}</label>
                    <input type="text" name="package" value="{{ $currentFilters['package'] ?? '' }}" placeholder="vendor/package" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.cve') }}</label>
                    <input type="text" name="cve" value="{{ $currentFilters['cve'] ?? '' }}" placeholder="CVE-2024-..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.safe_auto_fix') }}</label>
                    <select name="auto_fix" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('scans.filters.any') }}</option>
                        <option value="1" @selected(($currentFilters['auto_fix'] ?? '') === '1')>{{ $ui->t('findings.allowed') }}</option>
                        <option value="0" @selected(($currentFilters['auto_fix'] ?? '') === '0')>{{ $ui->t('findings.not_allowed') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.human_review') }}</label>
                    <select name="human_review" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('scans.filters.any') }}</option>
                        <option value="1" @selected(($currentFilters['human_review'] ?? '') === '1')>{{ $ui->t('common.required') }}</option>
                        <option value="0" @selected(($currentFilters['human_review'] ?? '') === '0')>{{ $ui->t('findings.automatable') }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-950">{{ $ui->t('common.apply_filters') }}</button>
                <a href="{{ route('ai-security.findings.index') }}" class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.reset') }}</a>
            </div>
        </form>

        <div class="rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.finding_queue') }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('findings.finding_queue_desc') }}</p>
                    </div>
                    @include('ai-security-guardian::partials.badge', ['variant' => 'provider', 'label' => $ui->t('findings.queue')])
                </div>
            </div>

            @if ($findings->isEmpty())
                <div class="p-6">
                    @include('ai-security-guardian::partials.empty-state', [
                        'icon' => '⚠',
                        'title' => $ui->t('findings.no_findings'),
                        'text' => $ui->t('findings.no_findings_text'),
                        'actionLabel' => $ui->t('common.run_scan'),
                        'actionHref' => route('ai-security.scan'),
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
                                <th>{{ $ui->t('findings.scanner') }}</th>
                                <th>{{ $ui->t('findings.package') }}</th>
                                <th>{{ $ui->t('findings.cve') }}</th>
                                <th>{{ $ui->t('findings.status_update') }}</th>
                                <th>{{ $ui->t('findings.human_review') }}</th>
                                <th>{{ $ui->t('scans.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($findings as $finding)
                                <tr>
                                    <td>
                                        @include('ai-security-guardian::partials.badge', [
                                            'variant' => $finding->severity,
                                            'label' => $ui->severity($finding->severity),
                                        ])
                                    </td>
                                    <td>
                                        <div class="max-w-lg">
                                            <a href="{{ route('ai-security.findings.show', $finding) }}" class="text-sm font-black text-slate-950 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">{{ $finding->title }}</a>
                                            <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($finding->description, 120) }}</div>
                                        </div>
                                    </td>
                                    <td class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $finding->category }}</td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">{{ $finding->scanner_name ?? $ui->t('common.unknown') }}</td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">{{ $finding->package_name ?? '—' }}</td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">{{ $finding->cve ?? '—' }}</td>
                                    <td>
                                        @include('ai-security-guardian::partials.badge', ['variant' => 'status', 'label' => $ui->findingStatus($finding->status)])
                                    </td>
                                    <td>
                                        <div class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                                            <div>{{ $finding->safe_auto_fix_allowed ? $ui->t('findings.safe_fix_allowed') : $ui->t('findings.review_required') }}</div>
                                            <div>{{ $finding->human_review_required ? $ui->t('common.required') : $ui->t('findings.automatable') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('ai-security.findings.show', $finding) }}" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.view') }}</a>
                                            <a href="{{ route('ai-security.patches.index', ['finding' => $finding->id]) }}" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('findings.patch') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                    {{ $findings->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
