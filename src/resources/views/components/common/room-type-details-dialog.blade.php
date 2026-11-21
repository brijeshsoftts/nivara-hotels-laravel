@props([
    "room" => null,
    "id" => "room-details-dialog",
    "open" => false,
])

<x-ui.dialog :id="$id" :open="$open">
    <x-ui.dialog.content class="max-w-4xl max-h-[90vh] overflow-y-auto p-0 sm:rounded-2xl">
        @if(!$room)
            <div class="p-8 text-center">
                <x-ui.spinner class="size-8 mx-auto text-primary" />
                <p class="mt-2 text-sm text-muted-foreground">Loading room details...</p>
            </div>
        @else
            @php
                $roomId = $room['id'] ?? ($room->id ?? '');
                $name = $room['name'] ?? ($room->name ?? "Room Details");
                $description = $room['description'] ?? ($room->description ?? "");
                $basePrice = $room['basePrice'] ?? ($room->basePrice ?? 0);
                $capacity = $room['capacity'] ?? ($room->capacity ?? 2);
                $areaSqft = $room['areaSqft'] ?? ($room->areaSqft ?? null);
                $bedType = $room['bedType'] ?? ($room->bedType ?? "KING");
                $bedCount = $room['bedCount'] ?? ($room->bedCount ?? 1);
                $amenities = $room['amenities'] ?? ($room->amenities ?? []);
                $images = $room['images'] ?? ($room->images ?? []);
                $isAvailable = $room['isAvailable'] ?? ($room->isAvailable ?? true);
                $smokingAllowed = $room['smokingAllowed'] ?? ($room->smokingAllowed ?? false);
                $petsAllowed = $room['petsAllowed'] ?? ($room->petsAllowed ?? false);
            @endphp

            <div class="grid lg:grid-cols-[1.1fr_1fr]">
                <!-- Left: Gallery -->
                <div class="bg-muted/30">
                    <x-common.room-gallery :images="$images" :roomName="$name" />
                </div>

                <!-- Right: Details -->
                <div class="flex flex-col justify-between p-6 sm:p-8 space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-2">
                            <span class="{{ $isAvailable ? 'bg-primary/10 text-primary ring-primary/30' : 'bg-destructive/10 text-destructive ring-destructive/30' }} inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1">
                                <x-ui.icon :name="$isAvailable ? 'check' : 'x'" class="size-3.5" />
                                {{ $isAvailable ? "Available" : "Not available" }}
                            </span>
                            <span class="text-xl font-bold font-heading text-primary">
                                ₹{{ number_format((float)$basePrice) }} <span class="text-xs text-muted-foreground font-normal">/ night</span>
                            </span>
                        </div>

                        <div>
                            <x-ui.dialog.title class="text-2xl font-bold font-heading text-foreground">
                                {{ $name }}
                            </x-ui.dialog.title>
                            <x-ui.dialog.description class="mt-1 text-sm text-muted-foreground line-clamp-3">
                                {{ $description }}
                            </x-ui.dialog.description>
                        </div>

                        <!-- Facts Grid -->
                        <div class="grid grid-cols-3 gap-2 py-2">
                            <div class="flex flex-col items-center justify-center rounded-xl border bg-muted/40 p-3 text-center">
                                <x-ui.icon name="bed-double" class="size-4 text-primary mb-1" />
                                <span class="text-2xs text-muted-foreground">Bed</span>
                                <span class="text-xs font-semibold">{{ $bedCount }} {{ ucwords(strtolower((string)$bedType)) }}</span>
                            </div>
                            <div class="flex flex-col items-center justify-center rounded-xl border bg-muted/40 p-3 text-center">
                                <x-ui.icon name="users" class="size-4 text-primary mb-1" />
                                <span class="text-2xs text-muted-foreground">Guests</span>
                                <span class="text-xs font-semibold">Up to {{ $capacity }}</span>
                            </div>
                            <div class="flex flex-col items-center justify-center rounded-xl border bg-muted/40 p-3 text-center">
                                <x-ui.icon name="maximize-2" class="size-4 text-primary mb-1" />
                                <span class="text-2xs text-muted-foreground">Size</span>
                                <span class="text-xs font-semibold">{{ $areaSqft ? $areaSqft . ' sqft' : "Spacious" }}</span>
                            </div>
                        </div>

                        <!-- Amenities -->
                        @if(!empty($amenities))
                            <div class="space-y-2">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Amenities</h4>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($amenities as $amenity)
                                        @php
                                            $aName = is_array($amenity) ? ($amenity['name'] ?? '') : ($amenity->name ?? '');
                                        @endphp
                                        <x-ui.badge variant="secondary" class="text-xs font-normal">
                                            {{ $aName }}
                                        </x-ui.badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- House Rules -->
                        <div class="space-y-2 pt-2 border-t">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">House Rules</h4>
                            <ul class="text-xs text-muted-foreground space-y-1">
                                <li class="flex items-center gap-2">
                                    <x-ui.icon :name="$smokingAllowed ? 'check' : 'x'" class="size-3.5 {{ $smokingAllowed ? 'text-primary' : 'text-muted-foreground' }}" />
                                    {{ $smokingAllowed ? "Smoking allowed" : "No smoking inside" }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <x-ui.icon :name="$petsAllowed ? 'check' : 'x'" class="size-3.5 {{ $petsAllowed ? 'text-primary' : 'text-muted-foreground' }}" />
                                    {{ $petsAllowed ? "Pets allowed" : "No pets permitted" }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-4 border-t flex items-center gap-3">
                        <x-ui.button
                            href="{{ url('/booking?roomTypeId=' . $roomId) }}"
                            class="w-full"
                        >
                            Proceed to Book
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif
    </x-ui.dialog.content>
</x-ui.dialog>
