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
    <title>Register — Nivara Hotels</title>
    @vite('resources/css/app.css')
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
                    <span class="block">Create Your</span>
                    <span class="block">Account</span>
                </h1>
                <p class="mt-4 max-w-lg text-base leading-6 text-background/85">
                    Register to manage your stays and make your next visit easier.
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
                        Book your stay
                    </li>
                    <li class="flex items-center gap-3 text-sm text-background/90">
                        <span class="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z" />
                                <path d="M7 7h.01" />
                            </svg>
                        </span>
                        Explore room offers
                    </li>
                    <li class="flex items-center gap-3 text-sm text-background/90">
                        <span class="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                                <line x1="16" x2="16" y1="2" y2="6" />
                                <line x1="8" x2="8" y1="2" y2="6" />
                                <line x1="3" x2="21" y1="10" y2="10" />
                            </svg>
                        </span>
                        Manage bookings
                    </li>
                    <li class="flex items-center gap-3 text-sm text-background/90">
                        <span class="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                            </svg>
                        </span>
                        Save favorite rooms
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
                        Create your account
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Register as a customer to get started.
                    </p>
                </div>

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-2">
                        <label for="register-name" class="text-sm font-medium leading-none text-foreground">
                            Full name <span aria-hidden="true" class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <input id="register-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus placeholder="Enter your full name" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                        @error('name')
                        <p class="text-sm text-destructive" id="register-name-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="register-email" class="text-sm font-medium leading-none text-foreground">
                            Email address <span aria-hidden="true" class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                            <input id="register-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required placeholder="Enter your email address" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                        @error('email')
                        <p class="text-sm text-destructive" id="register-email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="register-phone" class="text-sm font-medium leading-none text-foreground">
                            Phone number <span aria-hidden="true" class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                            <input id="register-phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" required placeholder="Enter your phone number" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                        @error('phone')
                        <p class="text-sm text-destructive" id="register-phone-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="register-password" class="text-sm font-medium leading-none text-foreground">
                                Password <span aria-hidden="true" class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <input id="register-password" name="password" type="password" autocomplete="new-password" required placeholder="Create a password" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                            </div>
                            @error('password')
                            <p class="text-sm text-destructive" id="register-password-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="register-password-confirm" class="text-sm font-medium leading-none text-foreground">
                                Confirm password <span aria-hidden="true" class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <svg class="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <input id="register-password-confirm" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Repeat password" class="h-11 w-full rounded-md border border-input bg-background px-3 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-semibold transition-all h-11 w-full bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring cursor-pointer">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <line x1="19" x2="19" y1="8" y2="14" />
                            <line x1="22" x2="16" y1="11" y2="11" />
                        </svg>
                        Create account
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-muted-foreground">
                    Already registered?
                    <a href="{{ route('login') }}" class="font-semibold text-foreground underline decoration-primary underline-offset-4 transition-colors hover:text-primary">
                        Sign in
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