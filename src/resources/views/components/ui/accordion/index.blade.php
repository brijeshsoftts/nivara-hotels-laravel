@props([
    "type" => "single",
    "collapsible" => true,
    "defaultValue" => null,
])

<div
    data-slot="accordion"
    x-data="{
        active: @js($defaultValue),
        type: '{{ $type }}',
        collapsible: {{ $collapsible ? 'true' : 'false' }},
        toggle(val) {
            if (this.type === 'single') {
                this.active = (this.active === val) ? (this.collapsible ? null : this.active) : val;
            } else {
                if (!Array.isArray(this.active)) this.active = this.active ? [this.active] : [];
                this.active = this.active.includes(val) ? this.active.filter(i => i !== val) : [...this.active, val];
            }
        },
        isOpen(val) {
            return this.type === 'single' ? this.active === val : (Array.isArray(this.active) && this.active.includes(val));
        }
    }"
    {{ $attributes->class(["flex w-full flex-col"]) }}
>
    {{ $slot }}
</div>
