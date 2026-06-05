@php
    $variant = $variant ?? 'neutral';
    $classes = [
        'critical' => 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300',
        'high' => 'border-orange-500/20 bg-orange-500/10 text-orange-700 dark:text-orange-300',
        'medium' => 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        'low' => 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300',
        'success' => 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        'warning' => 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        'info' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'neutral' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'provider' => 'border-cyan-500/20 bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
        'status' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
    ];
    $label = $label ?? ucfirst($variant);
@endphp

<span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $classes[$variant] ?? $classes['neutral'] }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $label }}
</span>
