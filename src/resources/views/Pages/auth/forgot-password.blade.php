<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Manrope:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <title>Forgot Password — Nivara Hotels</title>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>
<body class="bg-background text-foreground antialiased font-sans">
<main class="grid min-h-svh bg-background text-foreground lg:grid-cols-2">
    <!-- Left Branded Column -->
    <section aria-label="Nivara Hotels" class="relative isolate hidden min-h-svh flex-col overflow-hidden bg-muted px-8 py-8 text-background lg:flex lg:px-12 lg:py-10 justify-start">
        <img alt="A Nivara hotel and resort" class="absolute inset-0 -z-20 size-full object-cover" fetchpriority="high" src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2000&q=80" />
        <div class="absolute inset-0 -z-10 bg-foreground/55"></div>
        
        <a href="{{ url('/') }}" class="relative z-10 flex items-center gap-3">
            <div class="relative flex items-end justify-center overflow-hidden h-12 w-10 rounded-t-full rounded-b-sm bg-secondary pb-1">
                <svg class="absolute -bottom-1 h-7 w-7 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 8c0-2.76-2.46-5-5.5-5S2 5.24 2 8h2l1-1 1 1h4"/>
                    <path d="M13 7.14A5.82 5.82 0 0 1 16.5 6c3.04 0 5.5 2.24 5.5 5h-3l-1-1-1 1h-3"/>
                    <path d="M5.89 9.71c-2.15 1.5-2.3 4.47-.39 6.29h2.8"/>
                    <path d="M17.5 15.71c2.15-1.5 2.3-4.47.39-6.29h-2.8"/>
                    <path d="m9 16 1 6"/>
                    <path d="m15 16-1 6"/>
                </svg>
            </div>
            <div class="flex min-w-0 flex-col justify-center">
                <span class="font-heading text-[26px] leading-none tracking-wide text-background">Nivara</span>
                <span class="mt-1 font-sans text-[9px] font-medium tracking-[0.25em] uppercase text-background/80">HOTELS &amp; RESORTS</span>
            </div>
        </a>

        <div class="relative z-10 mt-10 max-w-xl xl:mt-12">
            <h1 class="max-w-md font-heading text-4xl leading-[1.08] font-medium text-background xl:text-5xl">
                <span class="block">Reset Your</span>
                <span class="block">Password</span>
            </h1>
            <p class="mt-4 max-w-lg text-base leading-6 text-background/85">
                We will email you a secure link to reset your account password.
            </p>
            <div aria-hidden="true" class="mt-7 h-px w-16 bg-brand-accent"></div>
        </div>
    </section>

    <!-- Right Form Column -->
    <section class="flex min-h-svh flex-col justify-between px-6 py-8 sm:px-10 lg:px-12 lg:py-10">
        <div class="flex items-center justify-between lg:hidden mb-6">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <span class="font-heading text-2xl text-primary">Nivara</span>
            </a>
        </div>

        <div class="mx-auto w-full max-w-md my-auto">
            <div class="space-y-2 mb-6">
                <h1 class="font-heading text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                    Forgot password?
                </h1>
                <p class="text-sm text-muted-foreground">
                    Enter your email to receive a password recovery link.
                </p>
            </div>

            @if (session('status'))
            <div role="status" class="mb-4 rounded-md border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium leading-none text-foreground">
                        Email address <span aria-hidden="true" class="text-destructive">*</span>
                    </label>
                    <div class="relative">
                        <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="Enter your email address" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                    </div>
                    @error('email')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-semibold transition-all h-11 w-full bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring cursor-pointer">
                    Send password reset link
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-muted-foreground">
                Remembered your password?
                <a href="{{ route('login') }}" class="font-semibold text-foreground underline decoration-primary underline-offset-4 transition-colors hover:text-primary">
                    Back to login
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
