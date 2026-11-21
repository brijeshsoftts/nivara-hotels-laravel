@props([
    "options" => [],
    "value" => null,
    "name" => null,
    "placeholder" => "Select option...",
    "label" => null,
    "searchPlaceholder" => "Search options...",
    "emptyMessage" => "No options found.",
])

@php
    $selectedOption = collect($options)->first(function($opt) use ($value) {
        $v = is_array($opt) ? ($opt['value'] ?? null) : (is_object($opt) ? ($opt->value ?? null) : $opt);
        return (string)$v === (string)$value;
    });
    $selectedLabel = is_array($selectedOption) ? ($selectedOption['label'] ?? null) : (is_object($selectedOption) ? ($selectedOption->label ?? null) : $selectedOption);
@endphp

<div
    x-data="{
        open: false,
        value: @js($value),
        label: @js($selectedLabel ?? ''),
        search: '',
        options: @js($options),
        select(opt) {
            this.value = typeof opt === 'object' ? opt.value : opt;
            this.label = typeof opt === 'object' ? (opt.label || opt.value) : opt;
            this.open = false;
            this.$dispatch('change', this.value);
        }
    }"
    class="space-y-1.5"
    {{ $attributes }}
>
    @if($label)
        <x-ui.label>{{ $label }}</x-ui.label>
    @endif

    @if($name)
        <input type="hidden" name="{{ $name }}" :value="value" />
    @endif

    <x-ui.popover>
        <x-ui.popover.trigger class="w-full">
            <x-ui.button
                variant="outline"
                type="button"
                class="w-full justify-between font-normal text-left"
            >
                <span class="truncate" x-text="label || '{{ $placeholder }}'" :class="!value && 'text-muted-foreground'"></span>
                <x-ui.icon name="chevron-down" class="size-4 shrink-0 opacity-50" />
            </x-ui.button>
        </x-ui.popover.trigger>

        <x-ui.popover.content align="start" class="w-full p-0">
            <div class="flex items-center border-b px-3">
                <x-ui.icon name="search" class="mr-2 size-4 shrink-0 opacity-50" />
                <input
                    type="text"
                    x-model="search"
                    placeholder="{{ $searchPlaceholder }}"
                    class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground"
                />
            </div>
            <div class="max-h-60 overflow-y-auto p-1">
                <template x-for="opt in options" :key="typeof opt === 'object' ? opt.value : opt">
                    <div
                        x-show="!search || (typeof opt === 'object' ? (opt.label || opt.value) : opt).toLowerCase().includes(search.toLowerCase())"
                        @click="select(opt)"
                        class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                    >
                        <span class="truncate" x-text="typeof opt === 'object' ? (opt.label || opt.value) : opt"></span>
                        <span class="ml-auto" x-show="value == (typeof opt === 'object' ? opt.value : opt)">
                            <x-ui.icon name="check" class="size-4" />
                        </span>
                    </div>
                </template>
                <div
                    x-show="options.filter(opt => !search || (typeof opt === 'object' ? (opt.label || opt.value) : opt).toLowerCase().includes(search.toLowerCase())).length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    {{ $emptyMessage }}
                </div>
            </div>
        </x-ui.popover.content>
    </x-ui.popover>
</div>
