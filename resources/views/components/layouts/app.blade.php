@props(['title'])

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }}</title>

        {{-- Apply the theme before the page paints, so a dark page never flashes white (see resources/js/theme.js). --}}
        <script>
            (() => {
                let theme = null;
                try { theme = localStorage.getItem('theme'); } catch {}
                document.documentElement.dataset.theme = theme ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            })();
        </script>

        {{-- Hide amounts before the page paints if the user chose so (see resources/js/privacy.js). --}}
        <script>
            try { if (localStorage.getItem('hide-amounts') === '1') { document.documentElement.dataset.hideAmounts = ''; } } catch {}
        </script>

        @fonts('vazirmatn')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="group/body h-dvh overflow-hidden bg-slate-100 dark:bg-slate-950 font-vazir text-slate-800 dark:text-slate-100 antialiased">
        {{-- Set the drawer state before the page paints: open on desktop unless the user closed it, closed on mobile. --}}
        <script>
            (() => {
                let isClosedByUser = false;
                try { isClosedByUser = localStorage.getItem('drawer') === 'closed'; } catch {}
                document.body.dataset.drawer = window.matchMedia('(min-width: 1024px)').matches && ! isClosedByUser ? 'open' : 'closed';
            })();
        </script>

        {{-- Thin loading bar shown during soft navigations (resources/js/navigation.js). --}}
        <div data-navigation-progress class="pointer-events-none fixed inset-x-0 top-0 z-50 h-0.5 origin-right scale-x-0 bg-sky-500 opacity-0 transition-[transform,opacity] duration-300 data-[state=done]:scale-x-100 data-[state=done]:opacity-0 data-[state=loading]:scale-x-75 data-[state=loading]:opacity-100 data-[state=loading]:duration-[3s]"></div>

        <x-layouts.drawer />

        {{-- App shell: the window never scrolls; only the content area under the header does, so its scrollbar stays inside the layout. --}}
        <div class="flex h-dvh flex-col transition-[padding] duration-200 lg:group-data-[drawer=open]/body:ps-64">
            <header class="z-20 flex h-14 shrink-0 items-center gap-3 border-b border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900/90 px-4 backdrop-blur lg:px-6">
                <button type="button" data-drawer-toggle class="rounded-md p-1.5 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="منو">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
                <span data-page-title class="font-bold">{{ $title }}</span>

                <div class="ms-auto flex items-center gap-2">
                    <div data-page-actions class="flex items-center gap-2">{{ $actions ?? '' }}</div>
                    <x-theme-toggle />
                    <x-privacy-toggle />
                </div>
            </header>

            {{-- The scroll container is left-to-right only so that its scrollbar sits on the right, next to the drawer. --}}
            <div data-scroll-container dir="ltr" class="app-scrollbar min-h-0 flex-1 overflow-y-auto">
                {{-- Everything inside [data-page] is replaced on a soft navigation; the drawer and header stay. --}}
                <div data-page dir="rtl">
                    <main tabindex="-1" class="mx-auto max-w-[1600px] px-4 py-6 focus:outline-none lg:px-6">
                        {{ $slot }}
                    </main>

                    {{ $dialogs ?? '' }}
                </div>
            </div>
        </div>
    </body>
</html>
