<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Manrope:wght@400;500;600&display=swap"
        rel="stylesheet" />
    <title>Login — Nivara Hotels</title>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>

<body class="bg-background text-foreground antialiased font-sans">
    <main class="grid min-h-svh bg-background text-foreground lg:grid-cols-2">
        <!-- Left Branded Column -->
        <section aria-label="Nivara Hotels" class="relative isolate hidden min-h-svh flex-col overflow-hidden bg-muted px-8 py-8 text-background lg:flex lg:px-12 lg:py-10 justify-start">
            <img alt="A Nivara hotel and resort" class="absolute inset-0 -z-20 size-full object-cover" fetchpriority="high" src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2000&q=80" />
            <div class="absolute inset-0 -z-10 bg-foreground/55"></div>

            <!-- Logo -->
            <x-auth-brand />

            <div class="relative z-10 mt-10 max-w-xl xl:mt-12">
                <h1 class="max-w-md font-heading text-4xl leading-[1.08] font-medium text-background xl:text-5xl">
                    <span class="block">Manage Your</span>
                    <span class="block">Hotel with Ease</span>
                </h1>
                <p class="mt-4 max-w-lg text-base leading-6 text-background/85">
                    Secure access to your hotel management dashboard.
                </p>
                <div aria-hidden="true" class="mt-7 h-px w-16 bg-brand-accent"></div>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-center gap-3 text-sm text-background/90">
                        <span class="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8" />
                                <path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4" />
                                <path d="M12 4v6" />
                                <path d="M2 18h20" />
                            </svg>
                        </span>
                        Manage bookings
                    </li>
                    <li class="flex items-center gap-3 text-sm text-background/90">
                        <span class="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 4h3a2 2 0 0 1 2 2v14" />
                                <path d="M2 20h20" />
                                <path d="M13 20V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v16" />
                            </svg>
                        </span>
                        Control rooms
                    </li>
                    <li class="flex items-center gap-3 text-sm text-background/90">
                        <span class="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 21a8 8 0 0 0-16 0" />
                                <circle cx="10" cy="8" r="5" />
                                <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
                            </svg>
                        </span>
                        Guest management
                    </li>
                    <li class="flex items-center gap-3 text-sm text-background/90">
                        <span class="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18" />
                                <path d="m19 9-5 5-4-4-3 3" />
                            </svg>
                        </span>
                        Reports &amp; analytics
                    </li>
                </ul>
            </div>
        </section>

        <!-- Right Form Column -->
        <section class="flex min-h-svh flex-col justify-between px-6 py-8 sm:px-10 lg:px-12 lg:py-10">
            <!-- Mobile Logo -->
            <div class="flex items-center justify-between lg:hidden mb-6">
                <x-auth-brand :compact="true" />
            </div>

            <div class="mx-auto w-full max-w-md my-auto">
                <div class="space-y-2 mb-6">
                    <h1 class="font-heading text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        Welcome back
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Sign in to your account.
                    </p>
                </div>

                @if (session('status'))
                <div role="status" class="mb-4 rounded-md border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                    {{ session('status') }}
                </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-2">
                        <label for="login-email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-foreground">
                            Email address <span aria-hidden="true" class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                            <input id="login-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required autofocus placeholder="Enter your email address" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @if ($errors->has('email')) aria-describedby="login-email-error" @endif class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                        @error('email')
                        <p class="text-sm text-destructive" id="login-email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <label for="login-password" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-foreground">
                                Password <span aria-hidden="true" class="text-destructive">*</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-foreground underline decoration-primary underline-offset-4 transition-colors hover:text-primary">
                                Forgot password?
                            </a>
                        </div>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <input id="login-password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if ($errors->has('password')) aria-describedby="login-password-error" @endif class="h-11 w-full rounded-md border border-input bg-background px-3 pr-10 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                        @error('password')
                        <p class="text-sm text-destructive" id="login-password-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center pt-1">
                        <label class="flex items-center gap-2 text-sm text-muted-foreground cursor-pointer">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded border-input text-primary accent-primary">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-semibold transition-all h-11 w-full bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring cursor-pointer">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" x2="3" y1="12" y2="12" />
                        </svg>
                        Sign in
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-muted-foreground">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-semibold text-foreground underline decoration-primary underline-offset-4 transition-colors hover:text-primary">
                        Create account
                    </a>
                </p>
            </div>

            <div class="text-center text-xs text-muted-foreground py-4">
                &copy; {{ date('Y') }} Nivara Hotels &amp; Resorts. All rights reserved.
            </div>
        </section>
    </main>
</body>

</html>