<div class="rounded-3xl border border-dashed border-slate-300 bg-white/80 p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-2xl font-black text-slate-500 dark:bg-slate-800 dark:text-slate-300">
        {{ $icon ?? '∅' }}
    </div>
    <h3 class="mt-4 text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $title ?? $ui->t('common.no_data') }}</h3>
    <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $text ?? $ui->t('common.no_data') }}</p>
    @if (! empty($actionLabel ?? null) && ! empty($actionHref ?? null))
        <div class="mt-6">
            <a href="{{ $actionHref }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-500">{{ $actionLabel }}</a>
        </div>
    @endif
</div>
