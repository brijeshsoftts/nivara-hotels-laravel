@props([
    "config" => [],
    "id" => null,
])

@php
    $id = $id ?? "chart_" . uniqid();
    $styleVars = [];
    foreach ($config as $key => $item) {
        if (isset($item['color'])) {
            $styleVars[] = "--color-{$key}: {$item['color']};";
        }
    }
@endphp

<div
    data-slot="chart"
    id="{{ $id }}"
    style="{{ implode(' ', $styleVars) }}"
    {{ $attributes->class([
        "flex aspect-video justify-center text-xs [&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground [&_.recharts-cartesian-grid_line[stroke='#ccc']]:stroke-border/50 [&_.recharts-curve.recharts-tooltip-cursor]:stroke-border [&_.recharts-dot[stroke='#fff']]:stroke-transparent [&_.recharts-layer]:outline-hidden [&_.recharts-polar-grid_[stroke='#ccc']]:stroke-border [&_.recharts-radial-bar-background-sector]:fill-muted [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted [&_.recharts-reference-line_[stroke='#ccc']]:stroke-border [&_.recharts-sector[stroke='#fff']]:stroke-transparent [&_.recharts-sector]:outline-hidden [&_.recharts-surface]:outline-hidden"
    ]) }}
>
    {{ $slot }}
</div>
