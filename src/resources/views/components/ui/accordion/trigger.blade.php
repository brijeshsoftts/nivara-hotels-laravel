@props([
    "value",
])

<div class="flex">
    <button
        type="button"
        data-slot="accordion-trigger"
        @click="toggle('{{ $value }}')"
        :aria-expanded="isOpen('{{ $value }}') ? 'true' : 'false'"
        {{ $attributes->class([
            "group/accordion-trigger relative flex flex-1 items-start justify-between rounded-lg border border-transparent py-2.5 text-left text-sm font-medium transition-all outline-none hover:underline focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 focus-visible:after:border-ring aria-disabled:pointer-events-none aria-disabled:opacity-50 **:data-[slot=accordion-trigger-icon]:ml-auto **:data-[slot=accordion-trigger-icon]:size-4 **:data-[slot=accordion-trigger-icon]:text-muted-foreground"
        ]) }}
    >
        <span>{{ $slot }}</span>
        <svg data-slot="accordion-trigger-icon" class="pointer-events-none shrink-0 group-aria-expanded/accordion-trigger:hidden ml-auto size-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
        </svg>
        <svg data-slot="accordion-trigger-icon" class="pointer-events-none hidden shrink-0 group-aria-expanded/accordion-trigger:inline ml-auto size-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m18 15-6-6-6 6"/>
        </svg>
    </button>
</div>
