@props([
    'id',
    'name',
    'autocomplete' => 'current-password',
    'placeholder' => 'Enter your password',
    'required' => true,
])

<div class="relative" data-password-field>
    <svg class="pointer-events-none absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </svg>

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="password"
        autocomplete="{{ $autocomplete }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        data-password-input
        {{ $attributes->merge([
            'class' => 'h-11 w-full rounded-md border border-input bg-background px-3 pr-10 pl-10 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring',
        ]) }}
    >

    <button
        type="button"
        data-password-toggle
        aria-label="Show password"
        aria-pressed="false"
        class="absolute top-1/2 right-3 -translate-y-1/2 cursor-pointer rounded-sm p-0.5 text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
    >
        <svg data-eye-icon class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
            <circle cx="12" cy="12" r="3" />
        </svg>
        <svg data-eye-off-icon class="hidden size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" />
            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" />
            <path d="m2 2 20 20" />
        </svg>
    </button>
</div>

@once
<script>
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) return;

        const wrapper = toggle.closest('[data-password-field]');
        const input = wrapper?.querySelector('[data-password-input]');
        if (!input) return;

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';

        toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        toggle.querySelector('[data-eye-icon]')?.classList.toggle('hidden', isHidden);
        toggle.querySelector('[data-eye-off-icon]')?.classList.toggle('hidden', !isHidden);
    });
</script>
@endonce

