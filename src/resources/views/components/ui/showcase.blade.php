{{-- Comprehensive Showcase Template for Converted Laravel Blade Components --}}
<!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blade Components Showcase</title>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-background text-foreground min-h-screen p-8 space-y-12">

    <!-- Toast Notification Container -->
    <x-sonner />

    <header class="border-b pb-4">
        <h1 class="text-3xl font-bold tracking-tight">Blade UI Components</h1>
        <p class="text-muted-foreground mt-1">Production-ready Laravel Blade components converted from React/Tailwind.</p>
    </header>

    <!-- 1. Buttons & Badges -->
    <section class="space-y-4">
        <h2 class="text-xl font-semibold">1. Buttons & Badges</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-button variant="default">Default Button</x-button>
            <x-button variant="secondary">Secondary</x-button>
            <x-button variant="outline">Outline</x-button>
            <x-button variant="ghost">Ghost</x-button>
            <x-button variant="destructive">Destructive</x-button>
            <x-button variant="link" href="#">Link Button</x-button>
            <x-button size="sm">Small</x-button>
            <x-button size="lg">Large</x-button>
            <x-button size="icon" aria-label="Settings">
                <x-icon name="more-horizontal" />
            </x-button>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-badge variant="default">Default</x-badge>
            <x-badge variant="secondary">Secondary</x-badge>
            <x-badge variant="outline">Outline</x-badge>
            <x-badge variant="destructive">Destructive</x-badge>
            <x-badge variant="ghost">Ghost</x-badge>
        </div>
    </section>

    <!-- 2. Form Inputs -->
    <section class="space-y-4 max-w-md">
        <h2 class="text-xl font-semibold">2. Form Controls</h2>
        <div class="space-y-2">
            <x-label for="example-input">Standard Input</x-label>
            <x-input id="example-input" name="example" placeholder="Type here..." />
        </div>
        <div class="space-y-2">
            <x-label for="example-textarea">Textarea</x-label>
            <x-textarea id="example-textarea" name="notes" placeholder="Enter notes..." />
        </div>
        <div class="space-y-2">
            <x-label>Input Group with Addon & Button</x-label>
            <x-input-group>
                <x-input-group.addon align="inline-start">https://</x-input-group.addon>
                <x-input-group.input placeholder="example.com" />
                <x-input-group.button variant="ghost" size="xs">Copy</x-input-group.button>
            </x-input-group>
        </div>
        <div>
            <x-checkbox name="terms" id="terms">I accept the terms and conditions</x-checkbox>
        </div>
    </section>

    <!-- 3. Cards & Avatars -->
    <section class="space-y-4">
        <h2 class="text-xl font-semibold">3. Cards & Avatars</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-card>
                <x-card.header>
                    <x-card.title>Hotel Suite Booking</x-card.title>
                    <x-card.description>Deluxe King Room with ocean view</x-card.description>
                    <x-card.action>
                        <x-badge variant="secondary">Available</x-badge>
                    </x-card.action>
                </x-card.header>
                <x-card.content>
                    <p class="text-sm text-muted-foreground">Includes complimentary breakfast, high-speed Wi-Fi, and spa access.</p>
                </x-card.content>
                <x-card.footer class="justify-between">
                    <span class="font-semibold text-base">$250 / night</span>
                    <x-button size="sm">Book Now</x-button>
                </x-card.footer>
            </x-card>

            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <x-avatar size="sm">
                        <x-avatar.fallback>SM</x-avatar.fallback>
                    </x-avatar>
                    <x-avatar size="default">
                        <x-avatar.fallback>JD</x-avatar.fallback>
                        <x-avatar.badge />
                    </x-avatar>
                    <x-avatar size="lg">
                        <x-avatar.fallback>LG</x-avatar.fallback>
                    </x-avatar>
                </div>
                <x-avatar.group>
                    <x-avatar><x-avatar.fallback>U1</x-avatar.fallback></x-avatar>
                    <x-avatar><x-avatar.fallback>U2</x-avatar.fallback></x-avatar>
                    <x-avatar><x-avatar.fallback>U3</x-avatar.fallback></x-avatar>
                    <x-avatar.group-count>+5</x-avatar.group-count>
                </x-avatar.group>
            </div>
        </div>
    </section>

    <!-- 4. Interactive Components (Alpine.js) -->
    <section class="space-y-6">
        <h2 class="text-xl font-semibold">4. Modals, Sheets & Dropdowns</h2>
        <div class="flex flex-wrap items-center gap-4">
            <!-- Dialog -->
            <x-dialog>
                <x-dialog.trigger>
                    <x-button>Open Dialog</x-button>
                </x-dialog.trigger>
                <x-dialog.content>
                    <x-dialog.header>
                        <x-dialog.title>Confirm Action</x-dialog.title>
                        <x-dialog.description>Are you sure you want to proceed with this booking?</x-dialog.description>
                    </x-dialog.header>
                    <x-dialog.footer>
                        <x-dialog.close class="px-3 py-1.5 text-sm rounded-md border hover:bg-muted">Cancel</x-dialog.close>
                        <x-button>Confirm</x-button>
                    </x-dialog.footer>
                </x-dialog.content>
            </x-dialog>

            <!-- Sheet -->
            <x-sheet>
                <x-sheet.trigger>
                    <x-button variant="outline">Open Sheet Drawer</x-button>
                </x-sheet.trigger>
                <x-sheet.content side="right">
                    <x-sheet.header>
                        <x-sheet.title>Edit Profile</x-sheet.title>
                        <x-sheet.description>Make changes to your profile settings here.</x-sheet.description>
                    </x-sheet.header>
                    <div class="py-4 space-y-3">
                        <x-label for="name">Name</x-label>
                        <x-input id="name" value="Jane Doe" />
                    </div>
                    <x-sheet.footer>
                        <x-button>Save changes</x-button>
                    </x-sheet.footer>
                </x-sheet.content>
            </x-sheet>

            <!-- Dropdown Menu -->
            <x-dropdown-menu>
                <x-dropdown-menu.trigger>
                    <x-button variant="outline">Actions Menu <x-icon name="chevron-down" class="ml-1 size-3.5" /></x-button>
                </x-dropdown-menu.trigger>
                <x-dropdown-menu.content>
                    <x-dropdown-menu.label>My Account</x-dropdown-menu.label>
                    <x-dropdown-menu.separator />
                    <x-dropdown-menu.item href="#profile">Profile <x-dropdown-menu.shortcut>⇧⌘P</x-dropdown-menu.shortcut></x-dropdown-menu.item>
                    <x-dropdown-menu.item href="#billing">Billing</x-dropdown-menu.item>
                    <x-dropdown-menu.separator />
                    <x-dropdown-menu.item variant="destructive">Log out</x-dropdown-menu.item>
                </x-dropdown-menu.content>
            </x-dropdown-menu>

            <!-- Popover -->
            <x-popover>
                <x-popover.trigger>
                    <x-button variant="secondary">Open Popover</x-button>
                </x-popover.trigger>
                <x-popover.content>
                    <x-popover.header>
                        <x-popover.title>Quick Details</x-popover.title>
                        <x-popover.description>Additional contextual information shown inside a popover.</x-popover.description>
                    </x-popover.header>
                </x-popover.content>
            </x-popover>

            <!-- Tooltip -->
            <x-tooltip>
                <x-tooltip.trigger>
                    <span class="underline cursor-help">Hover for Tooltip</span>
                </x-tooltip.trigger>
                <x-tooltip.content>Helpful tooltip description</x-tooltip.content>
            </x-tooltip>
        </div>
    </section>

    <!-- 5. Tabs & Accordion -->
    <section class="space-y-6 max-w-xl">
        <h2 class="text-xl font-semibold">5. Tabs & Accordion</h2>
        <x-tabs default="overview">
            <x-tabs.list>
                <x-tabs.trigger value="overview">Overview</x-tabs.trigger>
                <x-tabs.trigger value="details">Details</x-tabs.trigger>
                <x-tabs.trigger value="reviews">Reviews</x-tabs.trigger>
            </x-tabs.list>
            <x-tabs.content value="overview" class="p-4 border rounded-lg mt-2">
                Overview tab content rendered smoothly.
            </x-tabs.content>
            <x-tabs.content value="details" class="p-4 border rounded-lg mt-2">
                Detailed hotel suite specifications and policies.
            </x-tabs.content>
            <x-tabs.content value="reviews" class="p-4 border rounded-lg mt-2">
                Guest ratings and reviews.
            </x-tabs.content>
        </x-tabs>

        <x-accordion type="single" collapsible defaultValue="item-1">
            <x-accordion.item value="item-1">
                <x-accordion.trigger value="item-1">What is the check-in time?</x-accordion.trigger>
                <x-accordion.content value="item-1">
                    Standard check-in starts at 2:00 PM and check-out is until 11:00 AM.
                </x-accordion.content>
            </x-accordion.item>
            <x-accordion.item value="item-2">
                <x-accordion.trigger value="item-2">Is breakfast included?</x-accordion.trigger>
                <x-accordion.content value="item-2">
                    Complimentary buffet breakfast is included for all deluxe room reservations.
                </x-accordion.content>
            </x-accordion.item>
        </x-accordion>
    </section>

    <!-- 6. Calendar & Table -->
    <section class="space-y-6">
        <h2 class="text-xl font-semibold">6. Calendar & Data Table</h2>
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            <x-calendar name="booking_date" value="2026-11-20" />

            <div class="flex-1 w-full">
                <x-table>
                    <x-table.header>
                        <x-table.row>
                            <x-table.head>Room No</x-table.head>
                            <x-table.head>Type</x-table.head>
                            <x-table.head>Status</x-table.head>
                            <x-table.head class="text-right">Price</x-table.head>
                        </x-table.row>
                    </x-table.header>
                    <x-table.body>
                        <x-table.row>
                            <x-table.cell class="font-medium">101</x-table.cell>
                            <x-table.cell>Deluxe King</x-table.cell>
                            <x-table.cell><x-badge variant="default">Available</x-badge></x-table.cell>
                            <x-table.cell class="text-right font-mono">$180</x-table.cell>
                        </x-table.row>
                        <x-table.row>
                            <x-table.cell class="font-medium">102</x-table.cell>
                            <x-table.cell>Executive Suite</x-table.cell>
                            <x-table.cell><x-badge variant="secondary">Occupied</x-badge></x-table.cell>
                            <x-table.cell class="text-right font-mono">$320</x-table.cell>
                        </x-table.row>
                    </x-table.body>
                    <x-table.footer>
                        <x-table.row>
                            <x-table.cell colspan="3">Total Active Rooms</x-table.cell>
                            <x-table.cell class="text-right font-mono">2</x-table.cell>
                        </x-table.row>
                    </x-table.footer>
                </x-table>
            </div>
        </div>
    </section>

</body>
</html>
