@extends('ai-security-guardian::layout')

@section('title', $ui->t('patches.eyebrow'))

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('patches.eyebrow') }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('patches.title') }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">
                        {{ $ui->t('patches.subtitle') }}
                    </p>
                </div>

                <a href="{{ route('ai-security.findings.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('patches.back_to_findings') }}</a>
            </div>
        </div>

        <form method="GET" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('patches.patch_status') }}</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('reports.filters.all_categories') }}</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $ui->patchStatus($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('patches.test_status') }}</label>
                    <select name="tests_status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">{{ $ui->t('reports.filters.all_categories') }}</option>
                        @foreach ($testsStatusOptions as $status)
                            <option value="{{ $status }}" @selected(request('tests_status') === $status)>{{ $ui->testsStatus($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-950">{{ $ui->t('common.apply_filters') }}</button>
                <a href="{{ route('ai-security.patches.index') }}" class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('common.reset') }}</a>
            </div>
        </form>

        <div class="rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('patches.queue') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('patches.queue_desc') }}</p>
            </div>

            @if ($patches->isEmpty())
                <div class="p-6">
                    @include('ai-security-guardian::partials.empty-state', [
                        'icon' => '🧩',
                        'title' => $ui->t('patches.no_patches'),
                        'text' => $ui->t('patches.no_patches_text'),
                    ])
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th>{{ $ui->t('patches.finding') }}</th>
                                <th>{{ $ui->t('patches.patch_status') }}</th>
                                <th>{{ $ui->t('patches.tests') }}</th>
                                <th>{{ $ui->t('patches.pr_branch') }}</th>
                                <th>{{ $ui->t('patches.created') }}</th>
                                <th>{{ $ui->t('patches.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($patches as $patch)
                                <tr>
                                    <td>
                                        <div class="max-w-xl">
                                            <div class="text-sm font-black text-slate-950 dark:text-white">{{ $patch->finding?->title ?? $ui->t('common.unknown') }}</div>
                                            <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $patch->finding?->category ?? '—' }}</div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @include('ai-security-guardian::partials.badge', ['variant' => $patch->finding?->severity ?? 'neutral', 'label' => $ui->severity($patch->finding?->severity ?? 'neutral')])
                                                @include('ai-security-guardian::partials.badge', ['variant' => 'status', 'label' => $ui->findingStatus($patch->finding?->status ?? 'unknown')])
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @include('ai-security-guardian::partials.badge', ['variant' => $patch->status === 'applied_directly' ? 'warning' : 'neutral', 'label' => $ui->patchStatus($patch->status ?? 'pending')])
                                    </td>
                                    <td>
                                        @include('ai-security-guardian::partials.badge', ['variant' => $patch->tests_status === 'passed' ? 'success' : ($patch->tests_status === 'failed' ? 'critical' : 'neutral'), 'label' => $ui->testsStatus($patch->tests_status ?? 'not_run')])
                                    </td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">
                                        <div class="space-y-2">
                                            <div>{{ $ui->t('patches.branch', ['branch' => $patch->branch_name ?? '—']) }}</div>
                                            @if ($patch->pull_request_url)
                                                <a href="{{ $patch->pull_request_url }}" target="_blank" rel="noreferrer" class="break-all font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">{{ $patch->pull_request_url }}</a>
                                            @else
                                                <div>{{ $ui->t('patches.pr_url') }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-sm text-slate-600 dark:text-slate-300">{{ optional($patch->created_at)->format('M j, Y H:i') ?? '—' }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('ai-security.findings.show', $patch->finding) }}" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('patches.view_suggestion') }}</a>
                                            <a href="{{ route('ai-security.patches.download', $patch) }}" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:text-slate-200">{{ $ui->t('patches.download_patch') }}</a>
                                        </div>
                                    </td>
                                </tr>
                                @if ($patch->finding)
                                    <tr class="bg-slate-50/70 dark:bg-slate-950/60">
                                        <td colspan="6" class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ([
                                                    ['label' => $ui->t('patches.mark_in_review'), 'status' => 'in_review'],
                                                    ['label' => $ui->t('patches.accept_risk'), 'status' => 'accepted_risk'],
                                                    ['label' => $ui->t('patches.false_positive'), 'status' => 'false_positive'],
                                                ] as $action)
                                                    <form method="POST" action="{{ route('ai-security.findings.status', $patch->finding) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="{{ $action['status'] }}">
                                                        <button type="submit" class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">{{ $action['label'] }}</button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                    {{ $patches->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
