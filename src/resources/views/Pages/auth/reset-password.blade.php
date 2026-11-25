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
    <title>Set New Password — Nivara Hotels</title>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>

<body class="bg-background text-foreground antialiased font-sans">
    <main class="grid min-h-svh bg-background text-foreground lg:grid-cols-2">
        <!-- Left Branded Column -->
        <section aria-label="Nivara Hotels" class="relative isolate hidden min-h-svh flex-col overflow-hidden bg-muted px-8 py-8 text-background lg:flex lg:px-12 lg:py-10 justify-start">
            <img alt="A Nivara hotel and resort" class="absolute inset-0 -z-20 size-full object-cover" fetchpriority="high" src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2000&q=80" />
            <div class="absolute inset-0 -z-10 bg-foreground/55"></div>

            <x-auth-brand />

            <div class="relative z-10 mt-10 max-w-xl xl:mt-12">
                <h1 class="max-w-md font-heading text-4xl leading-[1.08] font-medium text-background xl:text-5xl">
                    <span class="block">Secure Your</span>
                    <span class="block">Account</span>
                </h1>
                <p class="mt-4 max-w-lg text-base leading-6 text-background/85">
                    Set a strong, new password to access your hotel reservation and management dashboard.
                </p>
                <div aria-hidden="true" class="mt-7 h-px w-16 bg-brand-accent"></div>
            </div>
        </section>

        <!-- Right Form Column -->
        <section class="flex min-h-svh flex-col justify-between px-6 py-8 sm:px-10 lg:px-12 lg:py-10">
            <div class="flex items-center justify-between lg:hidden mb-6">
                <x-auth-brand :compact="true" />
            </div>

            <div class="mx-auto w-full max-w-md my-auto">
                <div class="space-y-2 mb-6">
                    <h1 class="font-heading text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        Create new password
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Please choose a strong password with at least 8 characters.
                    </p>
                </div>

                @if (session('status'))
                <div role="status" class="mb-4 rounded-md border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                    {{ session('status') }}
                </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium leading-none text-foreground">
                            Email address <span aria-hidden="true" class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" autocomplete="email" required autofocus placeholder="Enter your email address" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                        @error('email')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium leading-none text-foreground">
                            New password <span aria-hidden="true" class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="At least 8 characters" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                        @error('password')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password_confirmation" class="text-sm font-medium leading-none text-foreground">
                            Confirm new password <span aria-hidden="true" class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Confirm your new password" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-semibold transition-all h-11 w-full bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring cursor-pointer">
                        Reset password
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-muted-foreground">
                    <a href="{{ route('login') }}" class="font-semibold text-foreground underline decoration-primary underline-offset-4 transition-colors hover:text-primary">
                        Back to login
                    </a>
                </p>
            </div>

            <footer class="mt-8 text-center text-xs text-muted-foreground">
                &copy; {{ date('Y') }} Nivara Hotels &amp; Resorts. All rights reserved.
            </footer>
        </section>
    </main>
</body>

</html>