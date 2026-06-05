@extends('ai-security-guardian::layout')

@section('title', $ui->t('navigation.notifications'))

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('settings.notifications.eyebrow') }}</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('settings.notifications.title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
                {{ $ui->t('settings.notifications.subtitle') }}
            </p>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            @foreach ($channels as $channel)
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $channel['channel'] }}</h2>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('settings.notifications.destination', ['destination' => $channel['destination']]) }}</p>
                        </div>
                        @include('ai-security-guardian::partials.badge', ['variant' => $channel['enabled'] ? 'success' : 'neutral', 'label' => $channel['enabled'] ? $ui->t('common.enabled') : $ui->t('common.disabled')])
                    </div>

                    <div class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('settings.notifications.critical_alerts') }}: {{ $ui->boolean((bool) $channel['critical']) }}</div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('settings.notifications.daily_summary') }}: {{ $ui->boolean((bool) $channel['daily']) }}</div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('settings.notifications.weekly_summary') }}: {{ $ui->boolean((bool) $channel['weekly']) }}</div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">{{ $ui->t('settings.notifications.last_status', ['status' => $channel['lastStatus']]) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
