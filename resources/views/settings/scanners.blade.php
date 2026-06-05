@extends('ai-security-guardian::layout')

@section('title', $ui->t('navigation.scanners'))

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('settings.scanners.eyebrow') }}</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('settings.scanners.title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
                {{ $ui->t('settings.scanners.subtitle') }}
            </p>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($scanners as $scanner)
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $scanner['name'] }}</h2>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $scanner['description'] }}</p>
                        </div>
                        @include('ai-security-guardian::partials.badge', ['variant' => $scanner['status'] === 'active' ? 'success' : 'neutral', 'label' => $ui->label('scanner_status', $scanner['status'], $scanner['status'])])
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.scanners.category') }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $scanner['category'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.scanners.mvp_v2') }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $scanner['mvp'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.scanners.findings') }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $scanner['findings'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.scanners.last_run') }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $scanner['lastRun'] ?? $ui->t('settings.scanners.not_tracked') }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
