@props([
    "name" => null,
    "value" => null,
])

<div
    data-slot="combobox"
    x-data="{
        open: false,
        search: '',
        value: @js(old($name, $value)),
        select(val) {
            this.value = val;
            this.open = false;
        }
    }"
    @keydown.escape.window="open = false"
    class="relative inline-block w-full"
    {{ $attributes }}
>
    @if($name)
        <input type="hidden" name="{{ $name }}" :value="value" />
    @endif
    {{ $slot }}
</div>
