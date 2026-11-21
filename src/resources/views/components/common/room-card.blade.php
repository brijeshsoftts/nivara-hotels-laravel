@props([
    "room" => null,
    "skeleton" => false,
])

@if($skeleton || !$room)
    <x-ui.card class="h-full gap-0 overflow-hidden rounded-xl py-0" aria-hidden="true">
        <x-ui.skeleton class="aspect-4/3 w-full" />
        <div class="space-y-4 p-6">
            <x-ui.skeleton class="h-7 w-2/3" />
            <x-ui.skeleton class="h-4 w-full" />
            <x-ui.skeleton class="h-4 w-4/5" />
            <div class="flex gap-2 pt-2">
                <x-ui.skeleton class="h-6 w-24 rounded-full" />
                <x-ui.skeleton class="h-6 w-24 rounded-full" />
            </div>
            <x-ui.skeleton class="h-10 w-full rounded-full" />
        </div>
    </x-ui.card>
@else
    @php
        $id = $room['id'] ?? ($room->id ?? '');
        $name = $room['name'] ?? ($room->name ?? "Room Type");
        $description = $room['description'] ?? ($room->description ?? "");
        $basePrice = $room['basePrice'] ?? ($room->basePrice ?? 0);
        $capacity = $room['capacity'] ?? ($room->capacity ?? 2);
        $areaSqft = $room['areaSqft'] ?? ($room->areaSqft ?? null);
        $bedType = $room['bedType'] ?? ($room->bedType ?? "KING");
        $bedCount = $room['bedCount'] ?? ($room->bedCount ?? 1);
        $amenities = $room['amenities'] ?? ($room->amenities ?? []);
        $images = $room['images'] ?? ($room->images ?? []);
        $primaryImg = null;
        if (!empty($images)) {
            foreach ($images as $img) {
                if (!empty($img['isPrimary']) || !empty($img->isPrimary)) {
                    $primaryImg = is_array($img) ? ($img['url'] ?? null) : ($img->url ?? null);
                    break;
                }
            }
            if (!$primaryImg) {
                $primaryImg = is_array($images[0]) ? ($images[0]['url'] ?? null) : ($images[0]->url ?? null);
            }
        }
        $formattedPrice = "₹" . number_format((float)$basePrice);
        $bedLabel = $bedCount . " " . ucwords(strtolower((string)$bedType)) . ($bedCount > 1 ? " beds" : " bed");
    @endphp

    <x-ui.card {{ $attributes->class(["group h-full gap-0 overflow-hidden rounded-xl border border-border/80 bg-card py-0 shadow-sm transition hover:shadow-md flex flex-col"]) }}>
        <!-- Room Image Container -->
        <div class="relative aspect-4/3 w-full overflow-hidden bg-muted">
            @if($primaryImg)
                <img
                    src="{{ $primaryImg }}"
                    alt="{{ $name }}"
                    loading="lazy"
                    class="size-full object-cover transition duration-500 group-hover:scale-105"
                />
            @else
                <div class="grid size-full place-items-center bg-muted">
                    <x-ui.icon name="bed-double" class="size-12 text-muted-foreground/60" />
                </div>
            @endif

            <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

            <!-- Price Tag -->
            <div class="absolute bottom-3 left-3 flex items-baseline gap-1 text-white">
                <span class="font-heading text-xl font-bold tracking-tight text-white drop-shadow-sm">
                    {{ $formattedPrice }}
                </span>
                <span class="text-xs text-white/80">/ night</span>
            </div>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col justify-between p-6">
            <div class="space-y-3">
                <h3 class="font-heading text-lg font-semibold tracking-tight text-foreground line-clamp-1 group-hover:text-primary transition-colors">
                    {{ $name }}
                </h3>

                @if($description)
                    <p class="text-xs leading-relaxed text-muted-foreground line-clamp-2">
                        {{ $description }}
                    </p>
                @endif

                <!-- Room Specifications -->
                <div class="flex flex-wrap items-center gap-3 pt-1 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-1">
                        <x-ui.icon name="bed-double" class="size-3.5 text-primary" />
                        {{ $bedLabel }}
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <x-ui.icon name="users" class="size-3.5 text-primary" />
                        Up to {{ $capacity }} Guests
                    </span>
                    @if($areaSqft)
                        <span class="inline-flex items-center gap-1">
                            <x-ui.icon name="maximize-2" class="size-3.5 text-primary" />
                            {{ $areaSqft }} sq ft
                        </span>
                    @endif
                </div>

                <!-- Amenities Tags -->
                @if(!empty($amenities))
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        @foreach(array_slice($amenities, 0, 3) as $amenity)
                            @php
                                $aName = is_array($amenity) ? ($amenity['name'] ?? '') : ($amenity->name ?? '');
                            @endphp
                            <x-ui.badge variant="secondary" class="text-2xs font-normal">
                                {{ $aName }}
                            </x-ui.badge>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex items-center gap-2 pt-2 border-t border-border/50">
                <x-ui.button
                    href="{{ url('/rooms/' . $id) }}"
                    variant="outline"
                    size="sm"
                    class="flex-1 text-xs"
                >
                    View Details
                </x-ui.button>
                <x-ui.button
                    href="{{ url('/booking?roomTypeId=' . $id) }}"
                    variant="default"
                    size="sm"
                    class="flex-1 text-xs"
                >
                    Book Now
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>
@endif
