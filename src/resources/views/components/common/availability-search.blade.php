@props([
    "action" => "/rooms",
    "roomTypeOptions" => [],
    "submitLabel" => "Check availability",
    "checkInDate" => null,
    "checkOutDate" => null,
    "adults" => 2,
    "children" => 0,
    "rooms" => 1,
])

@php
    $defaultCheckIn = $checkInDate ?? request('checkInDate', request('checkIn', date('Y-m-d')));
    $defaultCheckOut = $checkOutDate ?? request('checkOutDate', request('checkOut', date('Y-m-d', strtotime('+1 day'))));
    $defaultAdults = (int) request('adults', $adults);
    $defaultChildren = (int) request('children', $children);
    $defaultRooms = (int) request('rooms', $rooms);
@endphp

<form
    method="GET"
    action="{{ $action }}"
    x-data="{
        checkIn: @js($defaultCheckIn),
        checkOut: @js($defaultCheckOut),
        adults: @js($defaultAdults),
        children: @js($defaultChildren),
        rooms: @js($defaultRooms),
        guestsOpen: false,
        get guestsSummary() {
            const parts = [];
            parts.push(`${this.adults} ${this.adults > 1 ? 'Adults' : 'Adult'}`);
            if (this.children > 0) {
                parts.push(`${this.children} ${this.children > 1 ? 'Children' : 'Child'}`);
            }
            parts.push(`${this.rooms} ${this.rooms > 1 ? 'Rooms' : 'Room'}`);
            return parts.join(' · ');
        }
    }"
    {{ $attributes->class([
        "relative mx-auto w-full max-w-5xl rounded-2xl border border-border/80 bg-background/95 p-4 shadow-xl backdrop-blur-md md:p-6"
    ]) }}
>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
        <!-- Check-in Date -->
        <div class="space-y-1.5">
            <x-ui.label for="checkInDate" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Check-in
            </x-ui.label>
            <div class="relative">
                <input
                    type="date"
                    id="checkInDate"
                    name="checkInDate"
                    x-model="checkIn"
                    min="{{ date('Y-m-d') }}"
                    class="h-11 w-full rounded-lg border border-input bg-background px-3 text-sm font-medium text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    required
                />
            </div>
        </div>

        <!-- Check-out Date -->
        <div class="space-y-1.5">
            <x-ui.label for="checkOutDate" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Check-out
            </x-ui.label>
            <div class="relative">
                <input
                    type="date"
                    id="checkOutDate"
                    name="checkOutDate"
                    x-model="checkOut"
                    :min="checkIn"
                    class="h-11 w-full rounded-lg border border-input bg-background px-3 text-sm font-medium text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    required
                />
            </div>
        </div>

        <!-- Guests & Rooms Popover -->
        <div class="space-y-1.5 relative">
            <x-ui.label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Guests & Rooms
            </x-ui.label>

            <input type="hidden" name="adults" :value="adults" />
            <input type="hidden" name="children" :value="children" />
            <input type="hidden" name="rooms" :value="rooms" />

            <x-ui.popover>
                <x-ui.popover.trigger class="w-full">
                    <x-ui.button
                        type="button"
                        variant="outline"
                        class="h-11 w-full justify-between font-normal text-left px-3 border-input"
                    >
                        <span class="inline-flex items-center gap-2 truncate">
                            <x-ui.icon name="users" class="size-4 text-primary shrink-0" />
                            <span class="truncate text-xs font-medium" x-text="guestsSummary"></span>
                        </span>
                        <x-ui.icon name="chevron-down" class="size-4 opacity-50 shrink-0" />
                    </x-ui.button>
                </x-ui.popover.trigger>

                <x-ui.popover.content align="center" class="w-80 p-4 space-y-4">
                    <!-- Adults -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium">Adults</p>
                            <p class="text-2xs text-muted-foreground">Ages 13+</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="if (adults > 1) adults--"
                                :disabled="adults <= 1"
                                class="inline-flex size-7 items-center justify-center rounded-md border disabled:opacity-40"
                            >
                                <x-ui.icon name="minus" class="size-3" />
                            </button>
                            <span class="w-6 text-center text-sm font-semibold tabular-nums" x-text="adults"></span>
                            <button
                                type="button"
                                @click="if (adults < 10) adults++"
                                :disabled="adults >= 10"
                                class="inline-flex size-7 items-center justify-center rounded-md border disabled:opacity-40"
                            >
                                <x-ui.icon name="plus" class="size-3" />
                            </button>
                        </div>
                    </div>

                    <!-- Children -->
                    <div class="flex items-center justify-between border-t pt-3">
                        <div>
                            <p class="text-sm font-medium">Children</p>
                            <p class="text-2xs text-muted-foreground">Ages 0–12</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="if (children > 0) children--"
                                :disabled="children <= 0"
                                class="inline-flex size-7 items-center justify-center rounded-md border disabled:opacity-40"
                            >
                                <x-ui.icon name="minus" class="size-3" />
                            </button>
                            <span class="w-6 text-center text-sm font-semibold tabular-nums" x-text="children"></span>
                            <button
                                type="button"
                                @click="if (children < 6) children++"
                                :disabled="children >= 6"
                                class="inline-flex size-7 items-center justify-center rounded-md border disabled:opacity-40"
                            >
                                <x-ui.icon name="plus" class="size-3" />
                            </button>
                        </div>
                    </div>

                    <!-- Rooms -->
                    <div class="flex items-center justify-between border-t pt-3">
                        <div>
                            <p class="text-sm font-medium">Rooms</p>
                            <p class="text-2xs text-muted-foreground">Total rooms needed</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="if (rooms > 1) rooms--"
                                :disabled="rooms <= 1"
                                class="inline-flex size-7 items-center justify-center rounded-md border disabled:opacity-40"
                            >
                                <x-ui.icon name="minus" class="size-3" />
                            </button>
                            <span class="w-6 text-center text-sm font-semibold tabular-nums" x-text="rooms"></span>
                            <button
                                type="button"
                                @click="if (rooms < 8) rooms++"
                                :disabled="rooms >= 8"
                                class="inline-flex size-7 items-center justify-center rounded-md border disabled:opacity-40"
                            >
                                <x-ui.icon name="plus" class="size-3" />
                            </button>
                        </div>
                    </div>
                </x-ui.popover.content>
            </x-ui.popover>
        </div>

        <!-- Submit Button -->
        <div>
            <x-ui.button
                type="submit"
                size="lg"
                class="h-11 w-full gap-2 font-medium"
            >
                <span>{{ $submitLabel }}</span>
                <x-ui.icon name="arrow-right" class="size-4" />
            </x-ui.button>
        </div>
    </div>
</form>
