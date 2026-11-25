<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | SMART+ - School Management Attendance & Reporting Technologies</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' :
                        'light';
                    this.theme = savedTheme || systemTheme;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.theme === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark', 'bg-gray-900');
            } else {
                document.documentElement.classList.remove('dark');
                document.body.classList.remove('dark', 'bg-gray-900');
            }
        })();
    </script>
    
</head>

<body
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @php $menuGroups = \App\Helpers\MenuHelper::getMenuGroups(); @endphp
        @include('layouts.sidebar', ['menuGroups' => $menuGroups])

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                @if(auth()->check() && auth()->user()->isAkunDemo())
                    <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3.5 text-amber-900 shadow-xs sm:flex-row sm:items-center sm:justify-between dark:border-amber-700/60 dark:bg-amber-950/30 dark:text-amber-200">
                        <div class="flex items-start gap-3 sm:items-center">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-700 sm:mt-0 dark:text-amber-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold">Mode Preview (Akun Demo — Read-Only)</span>
                                    <span class="inline-flex items-center rounded-full bg-amber-200/80 px-2 py-0.5 text-[11px] font-semibold text-amber-900 dark:bg-amber-900/60 dark:text-amber-200">Preview Only</span>
                                </div>
                                <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300">
                                    Anda dapat melihat seluruh fitur SMART+ tanpa dapat menambah, mengubah, atau menghapus data.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 text-xs font-medium text-amber-900 dark:text-amber-200">
                            @if(auth()->user()->expired_at)
                                <span class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-white/80 px-3 py-1.5 dark:border-amber-700 dark:bg-gray-900/60">
                                    <svg class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Berlaku s/d {{ auth()->user()->expired_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB ({{ auth()->user()->expired_at->diffForHumans() }})
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                <div id="demo-content-root">
                    @yield('content')
                </div>
            </div>
        </div>

    </div>

    @if(auth()->check() && auth()->user()->isAkunDemo())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const root = document.getElementById('demo-content-root');
                if (!root) return;

                root.querySelectorAll('a[href]').forEach(function (link) {
                    try {
                        const url = new URL(link.href, window.location.origin);
                        if (url.origin !== window.location.origin) return;
                        const pathname = url.pathname.replace(/\/+$/, '');
                        if (pathname.endsWith('/pengaturan-sekolah') || pathname.endsWith('/profil')) {
                            return;
                        }
                        if (pathname.endsWith('/create') || pathname.endsWith('/edit') || pathname.endsWith('/kenaikan-kelas')) {
                            link.style.display = 'none';
                        }
                    } catch (_) {}
                });

                root.querySelectorAll('form').forEach(function (form) {
                    const methodInput = form.querySelector('input[name="_method"]');
                    const effectiveMethod = (methodInput ? methodInput.value : (form.getAttribute('method') || 'GET')).toUpperCase();
                    if (effectiveMethod === 'GET') return;

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                    });

                    const editableFields = form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]), select, textarea');
                    if (editableFields.length === 0) {
                        form.style.display = 'none';
                    } else {
                        editableFields.forEach(function (field) {
                            field.disabled = true;
                            field.readOnly = true;
                            field.classList.add('opacity-75', 'cursor-not-allowed');
                        });
                        form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])').forEach(function (btn) {
                            btn.style.display = 'none';
                        });
                    }
                });
            });
        </script>
    @endif

</body>

@stack('scripts')

</html>
