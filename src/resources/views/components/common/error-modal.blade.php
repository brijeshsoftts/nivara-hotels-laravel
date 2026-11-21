@props([
    "id" => null,
    "open" => false,
    "title" => "Something went wrong",
    "message" => "Something went wrong. Please try again.",
    "buttonText" => "Try Again",
    "icon" => "alert-circle",
    "onRetry" => "window.location.reload()",
])

<x-ui.dialog :id="$id" :open="$open">
    <x-ui.dialog.content class="sm:max-w-md text-center">
        <x-ui.dialog.header class="items-center text-center">
            <x-ui.icon :name="$icon" class="mb-2 size-12 text-destructive" />
            <x-ui.dialog.title>{{ $title }}</x-ui.dialog.title>
            <x-ui.dialog.description>{{ $message }}</x-ui.dialog.description>
        </x-ui.dialog.header>

        <x-ui.button
            type="button"
            class="w-full mt-2"
            x-on:click="{{ $onRetry }}"
        >
            <x-ui.icon name="refresh-cw" class="mr-2 size-4" />
            {{ $buttonText }}
        </x-ui.button>
    </x-ui.dialog.content>
</x-ui.dialog>
