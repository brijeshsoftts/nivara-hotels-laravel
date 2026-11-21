@props([
    "user" => null,
    "logoutRoute" => null,
    "profileRoute" => null,
])

@php
    $u = $user ?? (auth()->check() ? auth()->user() : null);
    $name = $u?->fullName ?? $u?->name ?? "Guest";
    $email = $u?->email ?? "";
    $avatarUrl = $u?->avatar?->url ?? $u?->avatar ?? null;
    $initials = "";
    if ($name) {
        $parts = array_filter(explode(" ", trim((string)$name)));
        $first = $parts[0] ?? "";
        $second = $parts[1] ?? "";
        $initials = strtoupper(substr($first, 0, 1) . substr($second, 0, 1));
    }
@endphp

<x-ui.dropdown-menu>
    <x-ui.dropdown-menu.trigger aria-label="Open account menu" class="cursor-pointer text-muted-foreground transition-colors hover:text-foreground">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            <x-ui.avatar>
                @if($avatarUrl)
                    <x-ui.avatar.image src="{{ $avatarUrl }}" alt="{{ $name }}" />
                @else
                    <x-ui.avatar.fallback class="bg-primary text-background font-semibold">
                        {{ $initials ?: "U" }}
                    </x-ui.avatar.fallback>
                @endif
            </x-ui.avatar>
        @endif
    </x-ui.dropdown-menu.trigger>

    <x-ui.dropdown-menu.content align="end" class="w-56">
        <x-ui.dropdown-menu.group>
            <x-ui.dropdown-menu.label class="flex items-center gap-2">
                <x-ui.avatar>
                    @if($avatarUrl)
                        <x-ui.avatar.image src="{{ $avatarUrl }}" alt="{{ $name }}" />
                    @else
                        <x-ui.avatar.fallback class="bg-primary text-background font-semibold">
                            {{ $initials ?: "U" }}
                        </x-ui.avatar.fallback>
                    @endif
                </x-ui.avatar>
                <div class="flex flex-1 flex-col truncate">
                    <span class="text-sm font-medium text-popover-foreground truncate">{{ $name }}</span>
                    <span class="text-xs text-muted-foreground truncate">{{ $email }}</span>
                </div>
            </x-ui.dropdown-menu.label>
        </x-ui.dropdown-menu.group>

        <x-ui.dropdown-menu.separator />

        <x-ui.dropdown-menu.group>
            <x-ui.dropdown-menu.item href="{{ $profileRoute ?? url('/dashboard/profile') }}">
                Profile
            </x-ui.dropdown-menu.item>

            <form method="POST" action="{{ $logoutRoute ?? route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full text-left">
                    <x-ui.dropdown-menu.item class="cursor-pointer text-destructive focus:text-destructive">
                        Logout
                    </x-ui.dropdown-menu.item>
                </button>
            </form>

            <x-ui.dropdown-menu.separator />

            <x-ui.dropdown-menu.item href="{{ url('/') }}">
                Back to Home
            </x-ui.dropdown-menu.item>
        </x-ui.dropdown-menu.group>
    </x-ui.dropdown-menu.content>
</x-ui.dropdown-menu>
