@extends('ai-security-guardian::layout')

@section('title', $ui->t('navigation.providers'))

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('settings.providers.eyebrow') }}</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('settings.providers.title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
                {{ $ui->t('settings.providers.subtitle') }}
            </p>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($providers as $provider)
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $provider['label'] }}</h2>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $ui->t('settings.providers.base_provider_key', ['name' => $provider['name']]) }}</p>
                        </div>
                        @include('ai-security-guardian::partials.badge', ['variant' => $provider['enabled'] ? 'success' : 'neutral', 'label' => $provider['enabled'] ? $ui->t('common.enabled') : $ui->t('common.disabled')])
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.providers.api_key') }}</div>
                            <div class="mt-2 font-mono text-sm text-slate-700 dark:text-slate-300">{{ $provider['apiKey'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.providers.model') }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $provider['model'] }}</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.providers.timeout') }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $provider['timeout'] }}s</div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.providers.retries') }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $provider['retries'] }}</div>
                        </div>
                    </div>

                    @if ($provider['baseUrl'])
                        <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.providers.base_url') }}</div>
                            <div class="mt-2 break-all text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $provider['baseUrl'] }}</div>
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @include('ai-security-guardian::partials.badge', ['variant' => 'provider', 'label' => $ui->t('settings.providers.privacy_mode')])
                        @include('ai-security-guardian::partials.badge', ['variant' => $provider['configured'] ? 'success' : 'warning', 'label' => $provider['configured'] ? $ui->t('settings.providers.configured') : $ui->t('settings.providers.missing_credentials')])
                    </div>

                    <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $ui->t('settings.providers.connection_test') }}</div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $provider['lastTestStatus'] }}</p>
                        <button type="button" class="mt-4 inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-400 dark:border-slate-800 dark:text-slate-500" disabled>{{ $ui->t('settings.providers.test_connection') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
