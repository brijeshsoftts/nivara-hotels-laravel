@props([
    "pagination" => null,
    "page" => 1,
    "limit" => 10,
    "total" => 0,
    "onPageChange" => null,
])

@php
    if (is_object($pagination) && method_exists($pagination, "currentPage")) {
        $currentPage = $pagination->currentPage();
        $pageSize = $pagination->perPage();
        $totalRecords = $pagination->total();
        $totalPages = $pagination->lastPage();
    } elseif (is_array($pagination)) {
        $currentPage = $pagination['page'] ?? $page;
        $pageSize = $pagination['limit'] ?? $limit;
        $totalRecords = $pagination['total'] ?? $total;
        $totalPages = $pageSize > 0 ? (int) ceil($totalRecords / $pageSize) : 0;
    } else {
        $currentPage = (int) $page;
        $pageSize = (int) $limit;
        $totalRecords = (int) $total;
        $totalPages = $pageSize > 0 ? (int) ceil($totalRecords / $pageSize) : 0;
    }

    $startRecord = $totalRecords === 0 ? 0 : ($currentPage - 1) * $pageSize + 1;
    $endRecord = min($currentPage * $pageSize, $totalRecords);
    $isFirstPage = $currentPage <= 1;
    $isLastPage = $currentPage >= $totalPages || $totalPages === 0;
@endphp

@if($totalRecords > 0)
<div {{ $attributes->class(["flex flex-col gap-4 border-t border-border/60 bg-card px-4 py-4 sm:px-6 md:flex-row md:items-center md:justify-between"]) }}>
    <div class="text-center text-xs text-muted-foreground md:text-left">
        Showing <span class="font-medium text-foreground">{{ $startRecord }}–{{ $endRecord }}</span> of <span class="font-medium text-foreground">{{ $totalRecords }}</span> records
    </div>

    <div class="flex items-center justify-center gap-2">
        <x-ui.button
            variant="outline"
            size="sm"
            :disabled="$isFirstPage"
            class="gap-1 border-border/60 bg-background hover:bg-accent disabled:opacity-50"
            @if($onPageChange) x-on:click="{{ $onPageChange }}({{ $currentPage - 1 }})" @endif
        >
            <x-ui.icon name="chevron-left" class="size-4" />
            Previous
        </x-ui.button>

        <span class="px-2 text-xs text-muted-foreground">
            Page <span class="font-medium text-foreground">{{ $currentPage }}</span> of <span class="font-medium text-foreground">{{ max($totalPages, 1) }}</span>
        </span>

        <x-ui.button
            variant="outline"
            size="sm"
            :disabled="$isLastPage"
            class="gap-1 border-border/60 bg-background hover:bg-accent disabled:opacity-50"
            @if($onPageChange) x-on:click="{{ $onPageChange }}({{ $currentPage + 1 }})" @endif
        >
            Next
            <x-ui.icon name="chevron-right" class="size-4" />
        </x-ui.button>
    </div>
</div>
@endif
