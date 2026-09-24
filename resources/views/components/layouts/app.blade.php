@props(['title'])

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }}</title>

        {{-- Hide amounts before the page paints if the user chose so (see resources/js/privacy.js). --}}
        <script>
            try { if (localStorage.getItem('hide-amounts') === '1') { document.documentElement.dataset.hideAmounts = ''; } } catch {}
        </script>

        @fonts('vazirmatn')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="group/body min-h-screen bg-slate-100 font-vazir text-slate-800 antialiased">
        {{-- Set the drawer state before the page paints: open on desktop unless the user closed it, closed on mobile. --}}
        <script>
            (() => {
                let isClosedByUser = false;
                try { isClosedByUser = localStorage.getItem('drawer') === 'closed'; } catch {}
                document.body.dataset.drawer = window.matchMedia('(min-width: 1024px)').matches && ! isClosedByUser ? 'open' : 'closed';
            })();
        </script>

        <x-layouts.drawer />

        <div class="transition-[padding] duration-200 lg:group-data-[drawer=open]/body:ps-64">
            <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-6">
                <button type="button" data-drawer-toggle class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100" aria-label="منو">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
                <span class="font-bold">{{ $title }}</span>

                <div class="ms-auto flex items-center gap-2">
                    {{ $actions ?? '' }}
                    <x-privacy-toggle />
                </div>
            </header>

            <main class="mx-auto max-w-[1600px] px-4 py-6 lg:px-6">
                {{ $slot }}
            </main>
        </div>

        {{ $dialogs ?? '' }}
    </body>
</html>
