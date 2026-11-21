@php
$user = auth()->user();
$role = strtoupper((string) ($user?->role ?? ''));
$category = strtoupper((string) ($user?->category ?? data_get($user, 'staff.category', '')));

$pages = [
'ADMIN' => [
['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => '/dashboard'],
['title' => 'Users', 'icon' => 'users', 'href' => '/dashboard/users'],
['title' => 'Staff', 'icon' => 'user-round-cog', 'href' => '/dashboard/staff'],
['title' => 'Customers', 'icon' => 'user-check', 'href' => '/dashboard/customers'],
['title' => 'Rooms', 'icon' => 'bed-double', 'href' => '/dashboard/rooms'],
['title' => 'New Booking', 'icon' => 'calendar-plus', 'href' => '/dashboard/bookings/new'],
['title' => 'Bookings', 'icon' => 'calendar-check', 'href' => '/dashboard/bookings'],
['title' => 'Issues', 'icon' => 'wrench', 'href' => '/dashboard/issues'],
['title' => 'Tasks', 'icon' => 'clipboard-check', 'href' => '/dashboard/tasks'],
['title' => 'Payments', 'icon' => 'circle-dollar-sign', 'href' => '/dashboard/payments'],
['title' => 'Invoices', 'icon' => 'file-text', 'href' => '/dashboard/invoices'],
['title' => 'Inquiries', 'icon' => 'mail', 'href' => '/dashboard/inquiries'],
['title' => 'Audit Logs', 'icon' => 'clipboard-list', 'href' => '/dashboard/audit-logs'],
['title' => 'Profile', 'icon' => 'user-round', 'href' => '/dashboard/profile'],
],
'MANAGER' => [
['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => '/dashboard'],
['title' => 'Staff', 'icon' => 'user-round-cog', 'href' => '/dashboard/staff'],
['title' => 'Customers', 'icon' => 'user-check', 'href' => '/dashboard/customers'],
['title' => 'Rooms', 'icon' => 'bed-double', 'href' => '/dashboard/rooms'],
['title' => 'New Booking', 'icon' => 'calendar-plus', 'href' => '/dashboard/bookings/new'],
['title' => 'Bookings', 'icon' => 'calendar-check', 'href' => '/dashboard/bookings'],
['title' => 'Issues', 'icon' => 'wrench', 'href' => '/dashboard/issues'],
['title' => 'Tasks', 'icon' => 'clipboard-check', 'href' => '/dashboard/tasks'],
['title' => 'Payments', 'icon' => 'circle-dollar-sign', 'href' => '/dashboard/payments'],
['title' => 'Invoices', 'icon' => 'file-text', 'href' => '/dashboard/invoices'],
['title' => 'Inquiries', 'icon' => 'mail', 'href' => '/dashboard/inquiries'],
['title' => 'Profile', 'icon' => 'user-round', 'href' => '/dashboard/profile'],
],
'RECEPTIONIST' => [
['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => '/dashboard'],
['title' => 'Customers', 'icon' => 'user-check', 'href' => '/dashboard/customers'],
['title' => 'New Booking', 'icon' => 'calendar-plus', 'href' => '/dashboard/bookings/new'],
['title' => 'Bookings', 'icon' => 'calendar-check', 'href' => '/dashboard/bookings'],
['title' => 'Payments', 'icon' => 'circle-dollar-sign', 'href' => '/dashboard/payments'],
['title' => 'Invoices', 'icon' => 'file-text', 'href' => '/dashboard/invoices'],
['title' => 'Profile', 'icon' => 'user-round', 'href' => '/dashboard/profile'],
],
'HOUSEKEEPER' => [
['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => '/dashboard'],
['title' => 'My Tasks', 'icon' => 'clipboard-check', 'href' => '/dashboard/tasks'],
['title' => 'Rooms', 'icon' => 'bed-double', 'href' => '/dashboard/rooms'],
['title' => 'Profile', 'icon' => 'user-round', 'href' => '/dashboard/profile'],
],
'CUSTOMER' => [
['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => '/dashboard'],
['title' => 'My Bookings', 'icon' => 'calendar-check', 'href' => '/dashboard/bookings'],
['title' => 'Payments', 'icon' => 'circle-dollar-sign', 'href' => '/dashboard/payments'],
['title' => 'Invoices', 'icon' => 'file-text', 'href' => '/dashboard/invoices'],
['title' => 'Issues', 'icon' => 'wrench', 'href' => '/dashboard/issues'],
['title' => 'Profile', 'icon' => 'user-round', 'href' => '/dashboard/profile'],
],
];

if ($role === 'STAFF') {
$role = $category === 'RECEPTIONIST' ? 'RECEPTIONIST' : 'HOUSEKEEPER';
}

$items = $pages[$role] ?? [];
$sections = [
'Overview' => ['Dashboard'],
'Management' => ['Users', 'Staff', 'Customers', 'Rooms', 'New Booking', 'Bookings', 'Tasks', 'Browse Rooms', 'My Bookings', 'My Tasks'],
'Operations' => ['Issues', 'Inquiries', 'Audit Logs'],
'Reports' => ['Payments', 'Invoices'],
'Settings' => ['Profile'],
];
$groupedItems = [];
$groupedTitles = [];

foreach ($sections as $section => $titles) {
$sectionItems = array_values(array_filter($items, fn ($item) => in_array($item['title'], $titles, true)));

if ($sectionItems !== []) {
$groupedItems[$section] = $sectionItems;
$groupedTitles = array_merge($groupedTitles, $titles);
}
}

$otherItems = array_values(array_filter($items, fn ($item) => ! in_array($item['title'], $groupedTitles, true)));

if ($otherItems !== []) {
$groupedItems['Other'] = $otherItems;
}

$userName = trim((string) ($user?->full_name ?? $user?->name ?? 'Account'));
$initials = collect(explode(' ', $userName))
->filter()
->take(2)
->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
->implode('');
$roleLabel = ucwords(strtolower(str_replace('_', ' ', $role)));
$currentPath = '/'.trim(request()->path(), '/');
@endphp

<div
    x-cloak
    x-show="sidebarOpen"
    x-transition.opacity
    class="fixed inset-0 z-30 bg-black/50 md:hidden"
    aria-hidden="true"
    @click="sidebarOpen = false"></div>

<aside
    aria-label="Dashboard navigation"
    :class="[
		sidebarOpen ? 'translate-x-0' : '-translate-x-full',
		sidebarCollapsed ? 'md:w-20' : 'md:w-72'
	]"
    class="fixed inset-y-0 left-0 z-40 flex h-screen w-72 shrink-0 flex-col overflow-hidden border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-[width,transform] duration-200 md:sticky md:top-0 md:translate-x-0">
    <div class="pointer-events-none absolute -bottom-7 -left-8 z-0 text-sidebar-accent opacity-50" aria-hidden="true">
        <i data-lucide="palmtree" class="size-36" stroke-width="1"></i>
    </div>

    <div class="relative z-10 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border px-3">
        <a href="{{ url('/dashboard') }}" class="flex min-w-0 flex-1 items-center gap-2 overflow-hidden text-sidebar-foreground" aria-label="Nivara dashboard">
            <span class="flex size-10 shrink-0 items-end justify-center overflow-hidden rounded-md bg-sidebar-accent">
                <i data-lucide="palmtree" class="mb-0.5 size-7 text-sidebar-primary" stroke-width="1.5"></i>
            </span>
            <span x-show="!sidebarCollapsed" x-transition.opacity class="min-w-0 font-heading text-lg leading-none">Nivara</span>
        </a>

        <button
            type="button"
            class="hidden size-8 shrink-0 items-center justify-center rounded-md text-sidebar-foreground/60 transition-colors hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring md:flex"
            aria-label="Collapse or expand navigation"
            :aria-expanded="!sidebarCollapsed"
            @click="sidebarCollapsed = !sidebarCollapsed">
            <i data-lucide="chevron-left" class="size-4 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''"></i>
        </button>
    </div>

    <div class="relative z-10 min-h-0 flex-1 overflow-hidden px-2 py-3">
        <nav aria-label="Dashboard pages" class="h-full space-y-1 overflow-y-auto overscroll-contain pr-1">
            @foreach ($groupedItems as $section => $sectionItems)
            <section x-data="{ expanded: true }" class="py-1 first:pt-0">
                <button
                    type="button"
                    class="flex h-8 w-full items-center justify-between rounded-md px-3 text-left text-[10px] font-semibold uppercase text-sidebar-foreground/60 transition-colors hover:text-sidebar-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring"
                    @click="expanded = !expanded"
                    :aria-expanded="expanded">
                    <span x-show="!sidebarCollapsed">{{ $section }}</span>
                    <i data-lucide="chevron-right" class="size-3.5 transition-transform duration-200" :class="expanded ? 'rotate-90 text-sidebar-foreground' : ''"></i>
                </button>

                <div x-show="expanded || sidebarCollapsed" x-collapse class="space-y-1">
                    @foreach ($sectionItems as $item)
                    @php($isActive = $currentPath === $item['href'])
                    <a
                        href="{{ url($item['href']) }}"
                        title="{{ $item['title'] }}"
                        @if ($isActive) aria-current="page" @endif
                        @click="sidebarOpen = false"
                        @class([ 'flex h-11 items-center gap-3 rounded-lg border-l-2 text-sm transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring' , 'border-sidebar-ring bg-sidebar-primary px-3 font-semibold text-sidebar-primary-foreground'=> $isActive,
                        'border-transparent px-3 text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-foreground' => ! $isActive,
                        ])
                        :class="sidebarCollapsed ? 'justify-center px-0' : ''"
                        >
                        <i data-lucide="{{ $item['icon'] }}" class="size-4 shrink-0 {{ $isActive ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">{{ $item['title'] }}</span>
                    </a>
                    @endforeach
                </div>
            </section>
            @endforeach
        </nav>
    </div>

    <div class="relative z-10 border-t border-sidebar-border p-3" :class="sidebarCollapsed ? 'p-1' : ''">
        <div class="flex min-w-0 items-center gap-1" :class="sidebarCollapsed ? 'justify-center' : ''">
            <a
                href="{{ url('/dashboard/profile') }}"
                title="{{ $userName }}"
                class="flex min-h-14 min-w-0 flex-1 items-center gap-3 rounded-lg p-2 transition-colors hover:bg-sidebar-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring"
                :class="sidebarCollapsed ? 'hidden' : ''">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-sidebar-border bg-sidebar-primary text-xs font-bold text-sidebar-primary-foreground">
                    {{ $initials ?: 'A' }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold leading-tight">{{ $userName }}</span>
                    <span class="mt-1.5 flex items-center gap-1.5">
                        <span class="rounded border border-sidebar-border bg-sidebar-primary px-1.5 py-0.5 text-[10px] font-semibold uppercase text-sidebar-primary-foreground">{{ $roleLabel ?: 'Staff' }}</span>
                        <span x-data="{ online: navigator.onLine }" x-init="window.addEventListener('online', () => online = true); window.addEventListener('offline', () => online = false)" class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full" :class="online ? 'bg-success' : 'bg-sidebar-foreground/50'"></span>
                            <span class="text-[10px] text-sidebar-foreground/60" x-text="online ? 'Online' : 'Offline'"></span>
                        </span>
                    </span>
                </span>
            </a>

            <div x-data="{ accountOpen: false }" class="relative shrink-0">
                <button
                    type="button"
                    class="flex size-10 items-center justify-center rounded-lg text-sidebar-foreground/60 transition-colors hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring"
                    aria-label="Account options"
                    :aria-expanded="accountOpen"
                    @click="accountOpen = !accountOpen"
                    @keydown.escape="accountOpen = false">
                    <i data-lucide="more-horizontal" class="size-4" x-show="!sidebarCollapsed"></i>
                    <span class="hidden size-9 items-center justify-center rounded-full border border-sidebar-border bg-sidebar-primary text-xs font-bold text-sidebar-primary-foreground" :class="sidebarCollapsed ? 'flex' : ''">{{ $initials ?: 'A' }}</span>
                </button>

                <div
                    x-cloak
                    x-show="accountOpen"
                    x-transition.opacity
                    @click.outside="accountOpen = false"
                    class="absolute bottom-12 right-0 z-50 w-56 rounded-md border border-sidebar-border bg-sidebar-accent p-1 text-sidebar-foreground shadow-lg"
                    role="menu"
                    aria-label="Account options">
                    <a href="{{ url('/dashboard/profile') }}" class="flex items-center gap-2 rounded-sm px-3 py-2 text-sm text-sidebar-foreground/80 transition-colors hover:bg-sidebar hover:text-sidebar-foreground" role="menuitem">
                        <i data-lucide="user-round" class="size-4 text-sidebar-foreground/60"></i>
                        My Profile
                    </a>
                    <a href="{{ url('/dashboard/profile') }}" class="flex items-center gap-2 rounded-sm px-3 py-2 text-sm text-sidebar-foreground/80 transition-colors hover:bg-sidebar hover:text-sidebar-foreground" role="menuitem">
                        <i data-lucide="settings" class="size-4 text-sidebar-foreground/60"></i>
                        Account Settings
                    </a>
                    <a href="{{ url('/dashboard/profile') }}" class="flex items-center gap-2 rounded-sm px-3 py-2 text-sm text-sidebar-foreground/80 transition-colors hover:bg-sidebar hover:text-sidebar-foreground" role="menuitem">
                        <i data-lucide="key-round" class="size-4 text-sidebar-foreground/60"></i>
                        Change Password
                    </a>
                    <div class="my-1 border-t border-sidebar-border"></div>
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-left text-sm text-sidebar-foreground/80 transition-colors hover:bg-sidebar hover:text-sidebar-foreground" role="menuitem">
                            <i data-lucide="log-out" class="size-4 text-sidebar-foreground/60"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</aside>