@extends('ai-security-guardian::layout')

@section('title', $ui->t('navigation.health'))

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-400">{{ $ui->t('health.eyebrow') }}</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('health.title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
                {{ $ui->t('health.subtitle') }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($health as $item)
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $item['label'] }}</p>
                    <div class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ $item['value'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
