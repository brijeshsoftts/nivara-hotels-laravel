<div {{ $attributes->class(["flex flex-col items-center justify-center gap-4 py-20"]) }}>
    <h1 class="text-3xl font-bold">No Access</h1>
    <p class="text-center text-muted-foreground/60">
        You do not have access to this page.
    </p>
    {{ $slot }}
</div>
