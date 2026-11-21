<x-layouts.auth-layout title="Register">
    <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-6 py-12">
        <section class="w-full space-y-8">
            <header class="space-y-2">
                <a href="{{ url('/') }}" class="font-heading text-2xl text-primary">Nivara</a>
                <h1 class="text-3xl font-semibold text-foreground">Create your account</h1>
                <p class="text-sm text-muted-foreground">Register as a customer to get started.</p>
            </header>

            <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                @csrf
                <div class="space-y-2">
                    <label for="name" class="text-sm font-medium">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    @error('name') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    @error('email') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="phone" class="text-sm font-medium">Phone number</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" required class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    @error('phone') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    @error('password') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring">
                </div>

                <button type="submit" class="h-11 w-full rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring">Create account</button>
            </form>

            <p class="text-center text-sm text-muted-foreground">
                Already registered?
                <a href="{{ route('login') }}" class="font-medium text-primary underline-offset-4 hover:underline">Sign in</a>
            </p>
        </section>
    </main>
</x-layouts.auth-layout>