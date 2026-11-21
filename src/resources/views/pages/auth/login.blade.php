<x-layouts.auth-layout title="Login">
    <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-6 py-12">
        <section class="w-full space-y-8">
            <header class="space-y-2">
                <a href="{{ url('/') }}" class="font-heading text-2xl text-primary">Nivara</a>
                <h1 class="text-3xl font-semibold text-foreground">Welcome back</h1>
                <p class="text-sm text-muted-foreground">Sign in to your account.</p>
            </header>

            @if (session('status'))
            <p role="status" class="rounded-md border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    @error('email') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    @error('password') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-input accent-primary">
                    Remember me
                </label>

                <button type="submit" class="h-11 w-full rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring">Sign in</button>
            </form>

            <p class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-primary underline-offset-4 hover:underline">Create account</a>
            </p>
        </section>
    </main>
</x-layouts.auth-layout>