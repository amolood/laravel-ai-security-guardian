@extends('ai-security-guardian::layout')

@section('title', $ui->t('findings.finding_detail'))

@section('content')
    @php
        $severityVariant = $finding->severity;
        $latestPatch = $finding->patches->first();
    @endphp

    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-3">
                        @include('ai-security-guardian::partials.badge', ['variant' => $severityVariant, 'label' => $ui->severity($finding->severity)])
                        @include('ai-security-guardian::partials.badge', ['variant' => 'status', 'label' => $ui->findingStatus($finding->status)])
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $finding->title }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">{{ $finding->description }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950 xl:w-[340px]">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.status_update') }}</div>
                    <form method="POST" action="{{ route('ai-security.findings.status', $finding) }}" class="mt-4 grid gap-2">
                        @csrf
                        <select name="status" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($finding->status === $status)>{{ $ui->findingStatus($status) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-950">{{ $ui->t('findings.update_status') }}</button>
                    </form>

                    <div class="mt-4 grid gap-2">
                        @foreach ($recommendedActions as $action)
                            <form method="POST" action="{{ route('ai-security.findings.status', $finding) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $action['status'] }}">
                                <button type="submit" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                                    {{ $ui->findingStatus($action['status']) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
            <div class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('findings.severity'), 'value' => $ui->severity($finding->severity), 'caption' => $ui->t('findings.risk_classification'), 'tone' => match ($finding->severity) {
                        'critical' => 'rose',
                        'high' => 'amber',
                        'medium' => 'sky',
                        'low' => 'accent',
                        default => 'neutral',
                    }])
                    @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('findings.category'), 'value' => $finding->category, 'caption' => $ui->t('findings.scanner_category'), 'tone' => 'neutral'])
                    @include('ai-security-guardian::partials.stat-card', ['label' => $ui->t('findings.scanner'), 'value' => $finding->scanner_name ?? $ui->t('common.unknown'), 'caption' => $ui->t('findings.origin_of_detection'), 'tone' => 'neutral'])
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.impact_analysis') }}</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.business_impact') }}</div>
                            <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $finding->business_impact ?? $findingDto->businessImpact ?? $ui->t('common.not_available') }}</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.technical_impact') }}</div>
                            <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $finding->technical_impact ?? $findingDto->technicalImpact ?? $ui->t('common.not_available') }}</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.recommended_fix') }}</div>
                            <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $finding->recommendation ?? $ui->t('common.not_available') }}</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.test_plan') }}</div>
                            <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $finding->test_plan ?? $findingDto->testPlan ?? $ui->t('common.not_available') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.references_and_location') }}</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.affected_file') }}</div>
                            <div class="mt-2 rounded-2xl bg-slate-950 px-4 py-3 font-mono text-sm text-slate-100 dark:bg-slate-950">{{ $finding->affected_file ?? $ui->t('common.not_specified') }}{{ $finding->affected_line ? ':' . $finding->affected_line : '' }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.cve_advisory') }}</div>
                            <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                <div>CVE: {{ $finding->cve ?? '—' }}</div>
                                @if ($finding->advisory_url)
                                    <a href="{{ $finding->advisory_url }}" target="_blank" rel="noreferrer" class="break-all font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">{{ $finding->advisory_url }}</a>
                                @else
                                    <div>{{ $ui->t('findings.cve_advisory') }}: —</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('findings.references') }}</div>
                        @if (!empty($finding->references ?? $findingDto->references ?? []))
                            <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                @foreach (($finding->references ?? $findingDto->references ?? []) as $reference)
                                    <li class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $reference }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('findings.no_reference_list') }}</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.safe_patch_suggestion') }}</h2>
                        @include('ai-security-guardian::partials.badge', ['variant' => $finding->safe_auto_fix_allowed ? 'success' : 'neutral', 'label' => $finding->safe_auto_fix_allowed ? $ui->t('findings.safe_fix_allowed') : $ui->t('findings.review_required')])
                    </div>

                    @if ($latestPatch && $latestPatch->patch_file)
                        <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-950 p-4 text-sm text-slate-100 dark:border-slate-800">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                @include('ai-security-guardian::partials.badge', ['variant' => 'provider', 'label' => $ui->patchStatus($latestPatch->status ?? 'pending')])
                                @if ($latestPatch->pull_request_url)
                                    <a href="{{ $latestPatch->pull_request_url }}" target="_blank" rel="noreferrer" class="rounded-full border border-slate-700 px-3 py-1 text-xs font-bold text-slate-200">{{ $ui->t('common.open_pr') }}</a>
                                @endif
                            </div>
                            <pre class="whitespace-pre-wrap font-mono text-xs leading-6">{{ $latestPatch->patch_file }}</pre>
                        </div>
                    @else
                        <div class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('findings.no_patch_suggestion') }}</div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.activity_timeline') }}</h2>
                    <ol class="mt-5 space-y-4">
                        @foreach ($timeline as $item)
                            <li class="flex gap-4">
                                <div class="mt-1 h-3 w-3 rounded-full bg-emerald-500 shadow-[0_0_0_6px_rgba(16,185,129,0.15)]"></div>
                                <div class="min-w-0 flex-1 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                                    <div class="text-sm font-black text-slate-950 dark:text-white">{{ $item['label'] }}</div>
                                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $item['value'] }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('findings.context') }}</h2>
                    <div class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('findings.package_label', ['package' => $finding->package_name ?? '—']) }}</div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('findings.created', ['value' => optional($finding->created_at)->format('M j, Y H:i') ?? '—']) }}</div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('findings.updated', ['value' => optional($finding->updated_at)->format('M j, Y H:i') ?? '—']) }}</div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('findings.human_review_label', ['value' => $finding->human_review_required ? $ui->t('common.required') : $ui->t('common.not_required')]) }}</div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('findings.safe_auto_fix_label', ['value' => $finding->safe_auto_fix_allowed ? $ui->t('common.enabled') : $ui->t('common.disabled')]) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
