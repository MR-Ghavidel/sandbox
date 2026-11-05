<x-layouts.app title="ورود">
    <section class="mx-auto mt-6 max-w-md rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-6 shadow-sm sm:mt-12 sm:p-8" aria-labelledby="login-title">
        <div class="mb-6">
            <h1 id="login-title" class="text-2xl font-bold">ورود به حساب</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">برای دسترسی به بخش‌های شخصی وارد شوید. ابزارها بدون ورود هم در دسترس هستند.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-medium">ایمیل</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" inputmode="email" dir="ltr" maxlength="255" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">
                @error('email')
                    <p id="email-error" role="alert" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium">رمز عبور</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" dir="ltr" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">
                @error('password')
                    <p id="password-error" role="alert" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 accent-sky-600">
                مرا به خاطر بسپار
            </label>

            <button type="submit" class="w-full rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-500">ورود</button>
        </form>

        <a href="{{ route('tools.index') }}" class="mt-5 block text-center text-sm text-sky-700 dark:text-sky-300 hover:underline">استفاده از ابزارها بدون ورود</a>
    </section>
</x-layouts.app>
