@php
    $isRtl = $ui->isRtl();
    $sidebarHiddenClass = $isRtl ? 'translate-x-full' : '-translate-x-full';
    $availableLocales = $ui->availableLocales();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $ui->direction() }}" class="{{ config('ai-security-guardian.ui.theme', 'auto') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - @yield('title', $ui->t('layout.brand_name'))</title>
    <script>tailwind = { config: { darkMode: 'class' } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        (function () {
            const root = document.documentElement;
            const key = 'ai-security-guardian-theme';
            const configured = @json(config('ai-security-guardian.ui.theme', 'auto'));
            const hiddenClass = @json($sidebarHiddenClass);

            const resolveTheme = (value) => {
                if (value === 'auto') {
                    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }

                return value;
            };

            const applyTheme = (value) => {
                const resolved = resolveTheme(value);
                root.classList.toggle('dark', resolved === 'dark');
                root.dataset.theme = value;
                window.localStorage.setItem(key, value);
            };

            const setSidebarState = (open) => {
                const panel = document.querySelector('[data-sidebar-panel]');
                const backdrop = document.querySelector('[data-sidebar-backdrop]');
                if (!panel || !backdrop) {
                    return;
                }

                if (open) {
                    panel.classList.remove(hiddenClass);
                    panel.classList.add('translate-x-0');
                    backdrop.classList.remove('hidden');
                    return;
                }

                panel.classList.add(hiddenClass);
                panel.classList.remove('translate-x-0');
                backdrop.classList.add('hidden');
            };

            const stored = window.localStorage.getItem(key) || configured;
            applyTheme(stored);

            window.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-theme-label]').forEach(function (element) {
                    element.textContent = stored;
                });
            });

            window.addEventListener('click', function (event) {
                const themeButton = event.target.closest('[data-theme-toggle]');
                if (themeButton) {
                    event.preventDefault();
                    const current = window.localStorage.getItem(key) || configured;
                    const next = current === 'auto' ? 'dark' : current === 'dark' ? 'light' : 'auto';
                    applyTheme(next);
                    document.querySelectorAll('[data-theme-label]').forEach(function (element) {
                        element.textContent = next;
                    });
                }

                const confirmButton = event.target.closest('[data-confirm]');
                if (confirmButton && !window.confirm(confirmButton.dataset.confirm)) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                const sidebarButton = event.target.closest('[data-sidebar-toggle]');
                if (sidebarButton) {
                    event.preventDefault();
                    const panel = document.querySelector('[data-sidebar-panel]');
                    if (!panel) {
                        return;
                    }

                    setSidebarState(!panel.classList.contains('translate-x-0'));
                }

                const backdropButton = event.target.closest('[data-sidebar-backdrop]');
                if (backdropButton) {
                    setSidebarState(false);
                }
            });

            if (configured === 'auto') {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                    if ((window.localStorage.getItem(key) || configured) === 'auto') {
                        applyTheme('auto');
                    }
                });
            }
        })();
    </script>
    <script src="{{ route('ai-security.assets.js') }}" defer></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside data-sidebar-panel class="fixed inset-y-0 left-0 z-40 w-[280px] {{ $sidebarHiddenClass }} border-r border-slate-200 bg-white/95 px-4 py-5 shadow-2xl backdrop-blur transition-transform duration-200 dark:border-slate-800 dark:bg-slate-950/95 lg:sticky lg:top-0 lg:block lg:h-screen lg:translate-x-0 lg:shadow-none rtl:left-auto rtl:right-0 rtl:border-l rtl:border-r-0">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-sm font-black text-white shadow-lg shadow-emerald-600/20">A</div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-black tracking-tight text-slate-950 dark:text-white">{{ $ui->t('layout.brand_name') }}</div>
                    <div class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $ui->t('layout.brand_tagline') }}</div>
                </div>
            </div>

            <nav class="mt-6 space-y-6">
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('layout.navigation') }}</p>
                    <div class="space-y-2">
                        @php
                            $navItems = [
                                ['label' => $ui->t('navigation.dashboard'), 'route' => 'ai-security.dashboard', 'icon' => '⌂'],
                                ['label' => $ui->t('navigation.scans'), 'route' => 'ai-security.scans.index', 'icon' => '⌁'],
                                ['label' => $ui->t('navigation.findings'), 'route' => 'ai-security.findings.index', 'icon' => '⚠'],
                                ['label' => $ui->t('navigation.reports'), 'route' => 'ai-security.reports.index', 'icon' => '▤'],
                                ['label' => $ui->t('navigation.patches'), 'route' => 'ai-security.patches.index', 'icon' => '🧩'],
                                ['label' => $ui->t('navigation.providers'), 'route' => 'ai-security.settings.providers', 'icon' => '◫'],
                                ['label' => $ui->t('navigation.scanners'), 'route' => 'ai-security.settings.scanners', 'icon' => '◎'],
                                ['label' => $ui->t('navigation.notifications'), 'route' => 'ai-security.settings.notifications', 'icon' => '✉'],
                                ['label' => $ui->t('navigation.health'), 'route' => 'ai-security.health', 'icon' => '⬚'],
                                ['label' => $ui->t('navigation.help'), 'route' => 'ai-security.help', 'icon' => '?'],
                            ];
                        @endphp

                        @foreach ($navItems as $item)
                            <a href="{{ route($item['route']) }}" class="@class([
                                'group flex items-center justify-between rounded-2xl border px-4 py-3 text-sm font-semibold transition',
                                'border-slate-200 bg-slate-50 text-slate-700 hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-900/90' => ! request()->routeIs($item['route'] . '*'),
                                'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 shadow-sm dark:text-emerald-300' => request()->routeIs($item['route'] . '*'),
                            ])>
                                <span class="flex items-center gap-3">
                                    <span class="@class([
                                        'flex h-8 w-8 items-center justify-center rounded-xl text-xs font-black',
                                        'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => ! request()->routeIs($item['route'] . '*'),
                                        'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => request()->routeIs($item['route'] . '*'),
                                    ])">{{ $item['icon'] }}</span>
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                <span class="text-xs text-slate-400 group-hover:text-slate-500 rtl:rotate-180 dark:text-slate-500">→</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                    <div class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $ui->t('layout.safety_posture') }}</div>
                    <p class="mb-3 leading-6">{{ $ui->t('layout.safety_description') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">{{ $ui->t('layout.safe_mode') }}</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $ui->t('layout.no_direct_fix') }}</span>
                    </div>
                </div>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-col">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
                <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" data-sidebar-toggle class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 lg:hidden">
                            <span class="text-lg">☰</span>
                            <span class="sr-only">{{ $ui->t('common.toggle_sidebar') }}</span>
                        </button>
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ $ui->t('layout.brand_name') }}</p>
                            <div class="mt-1 flex min-w-0 items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                @if (isset($latestScan) && $latestScan)
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        {{ $ui->t('layout.latest_scan', ['status' => $ui->scanStatus($latestScan->status)]) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                        {{ $ui->t('layout.no_scan_stored') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                        <div class="inline-flex items-center gap-1 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            @foreach ($availableLocales as $locale)
                                <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}" class="@class([
                                    'rounded-xl px-3 py-2 text-xs font-black uppercase tracking-[0.16em] transition',
                                    'bg-emerald-600 text-white shadow-sm' => app()->getLocale() === $locale,
                                    'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => app()->getLocale() !== $locale,
                                ])" lang="{{ $locale }}" hreflang="{{ $locale }}">
                                    {{ $ui->localeName($locale) }}
                                </a>
                            @endforeach
                        </div>

                        <button type="button" data-theme-toggle class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                            <span>{{ $ui->t('common.theme') }}</span>
                            <span data-theme-label class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500 dark:bg-slate-800 dark:text-slate-300">auto</span>
                        </button>

                        <a href="{{ route('ai-security.scan') }}" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-500" data-confirm="{{ $ui->t('common.run_scan_now') }}">
                            <span>{{ $ui->t('common.run_scan') }}</span>
                        </a>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div data-sidebar-backdrop class="fixed inset-0 z-30 hidden bg-slate-950/60 lg:hidden"></div>

                @if (session('success'))
                    <div class="mb-6 rounded-3xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-4 text-emerald-700 shadow-sm dark:text-emerald-300">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/20 font-black">✓</div>
                            <div>
                                <p class="font-bold">{{ $ui->t('common.success') }}</p>
                                <p class="text-sm text-emerald-700/80 dark:text-emerald-300/80">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-3xl border border-rose-500/20 bg-rose-500/10 px-5 py-4 text-rose-700 shadow-sm dark:text-rose-300">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500/20 font-black">!</div>
                            <div>
                                <p class="font-bold">{{ $ui->t('common.action_blocked') }}</p>
                                <p class="text-sm text-rose-700/80 dark:text-rose-300/80">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
