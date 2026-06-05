@php
    $tone = $tone ?? 'neutral';
    $tones = [
        'neutral' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'accent' => 'border-emerald-500/20 bg-emerald-500/10 dark:border-emerald-500/20 dark:bg-emerald-500/10',
        'rose' => 'border-rose-500/20 bg-rose-500/10 dark:border-rose-500/20 dark:bg-rose-500/10',
        'amber' => 'border-amber-500/20 bg-amber-500/10 dark:border-amber-500/20 dark:bg-amber-500/10',
        'sky' => 'border-sky-500/20 bg-sky-500/10 dark:border-sky-500/20 dark:bg-sky-500/10',
    ];
@endphp

<div class="rounded-3xl border p-5 shadow-sm {{ $tones[$tone] ?? $tones['neutral'] }}">
    <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $label ?? $ui->t('common.unknown') }}</div>
    <div class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $value ?? '—' }}</div>
    @if (! empty($caption ?? null))
        <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $caption }}</div>
    @endif
    @if (! empty($meta ?? null))
        <div class="mt-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $meta }}</div>
    @endif
</div>
