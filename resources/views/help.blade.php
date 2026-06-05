@extends('ai-security-guardian::layout')

@section('title', $ui->t('navigation.help'))

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('help.eyebrow') }}</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('help.title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
                {{ $ui->t('help.subtitle') }}
            </p>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('help.what_it_does') }}</h2>
                <ul class="mt-4 space-y-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    <li>{{ $ui->t('help.scan_bullets.dependency_risks') }}</li>
                    <li>{{ $ui->t('help.scan_bullets.stores_history') }}</li>
                    <li>{{ $ui->t('help.scan_bullets.ai_summary') }}</li>
                    <li>{{ $ui->t('help.scan_bullets.reports') }}</li>
                </ul>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('help.what_it_does_not_do') }}</h2>
                <ul class="mt-4 space-y-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    <li>{{ $ui->t('help.non_features.offensive') }}</li>
                    <li>{{ $ui->t('help.non_features.secrets') }}</li>
                    <li>{{ $ui->t('help.non_features.direct_changes') }}</li>
                    <li>{{ $ui->t('help.non_features.external_targets') }}</li>
                </ul>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('help.how_scanning_works') }}</h2>
                <div class="mt-4 space-y-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    <p>{{ $ui->t('help.scanning_paragraph_1') }}</p>
                    <p>{{ $ui->t('help.scanning_paragraph_2') }}</p>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('help.privacy_and_redaction') }}</h2>
                <div class="mt-4 space-y-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    <p>{{ $ui->t('help.privacy_paragraph_1') }}</p>
                    <p>{{ $ui->t('help.privacy_paragraph_2') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('help.artisan_commands') }}</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($commands as $command)
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300">{{ $command }}</div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
