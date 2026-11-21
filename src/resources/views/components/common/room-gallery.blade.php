@props([
    "images" => [],
    "roomName" => "Room",
])

@php
    $sortedImages = collect($images)->sortByDesc(fn($img) => is_array($img) ? ($img['isPrimary'] ?? false) : ($img->isPrimary ?? false))->values()->all();
@endphp

<div
    x-data="{
        images: @js($sortedImages),
        index: 0,
        get count() { return this.images.length; },
        get current() { return this.images[this.index] || null; },
        prev() { if (this.count > 0) this.index = (this.index - 1 + this.count) % this.count; },
        next() { if (this.count > 0) this.index = (this.index + 1) % this.count; },
        go(i) { this.index = i; },
    }"
    role="group"
    aria-roledescription="carousel"
    aria-label="{{ $roomName }} photos"
    tabindex="0"
    @keydown.left="prev()"
    @keydown.right="next()"
    class="outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset"
    {{ $attributes }}
>
    <!-- Main Display -->
    <div class="relative aspect-4/3 overflow-hidden bg-muted">
        <template x-if="count > 0 && current">
            <img
                :src="current.url"
                :alt="current.altText || '{{ $roomName }}'"
                class="size-full object-cover transition-opacity duration-300"
            />
        </template>
        <template x-if="count === 0">
            <div class="grid aspect-4/3 place-items-center bg-muted size-full">
                <x-ui.icon name="bed-double" class="size-14 text-muted-foreground" />
            </div>
        </template>

        <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-black/25 pointer-events-none"></div>

        <!-- Controls -->
        <template x-if="count > 1">
            <div>
                <button
                    type="button"
                    @click="prev()"
                    aria-label="Previous photo"
                    class="absolute top-1/2 left-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-black/45 text-white backdrop-blur-md transition hover:bg-black/65 focus-visible:ring-2 focus-visible:ring-ring outline-none"
                >
                    <x-ui.icon name="chevron-left" class="size-5" />
                </button>
                <button
                    type="button"
                    @click="next()"
                    aria-label="Next photo"
                    class="absolute top-1/2 right-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-black/45 text-white backdrop-blur-md transition hover:bg-black/65 focus-visible:ring-2 focus-visible:ring-ring outline-none"
                >
                    <x-ui.icon name="chevron-right" class="size-5" />
                </button>
                <span
                    aria-live="polite"
                    class="absolute right-3 bottom-3 rounded-full bg-black/50 px-3 py-1 text-xs font-medium text-white tabular-nums backdrop-blur-md"
                    x-text="`${index + 1} / ${count}`"
                ></span>
            </div>
        </template>
    </div>

    <!-- Thumbnails -->
    <template x-if="count > 1">
        <div class="flex gap-2 overflow-x-auto px-4 py-4 sm:px-6">
            <template x-for="(img, i) in images" :key="img.url || i">
                <button
                    type="button"
                    @click="go(i)"
                    :aria-label="`Show photo ${i + 1}`"
                    :class="i === index ? 'ring-2 ring-primary ring-offset-2 ring-offset-background opacity-100' : 'opacity-60 hover:opacity-100'"
                    class="h-14 w-20 shrink-0 overflow-hidden rounded-lg transition focus-visible:ring-2 focus-visible:ring-ring outline-none"
                >
                    <img :src="img.url" :alt="img.altText || '{{ $roomName }}'" loading="lazy" class="size-full object-cover" />
                </button>
            </template>
        </div>
    </template>
</div>
