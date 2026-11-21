@props([
    "columns" => [],
    "data" => [],
    "searchable" => true,
    "searchPlaceholder" => "Search records...",
    "emptyMessage" => "No records found.",
    "pagination" => null,
    "onRowClick" => null,
])

<div
    x-data="{
        search: '',
    }"
    {{ $attributes->class(["w-full space-y-4"]) }}
>
    <!-- Table Header Toolbar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        @if($searchable)
            <div class="relative w-full max-w-sm">
                <x-ui.icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <x-ui.input
                    type="text"
                    x-model="search"
                    placeholder="{{ $searchPlaceholder }}"
                    class="pl-9 h-9"
                />
            </div>
        @else
            <div></div>
        @endif

        @if(isset($toolbar) || isset($actions))
            <div class="flex items-center gap-2">
                {{ $toolbar ?? $actions }}
            </div>
        @endif
    </div>

    <!-- Table Container -->
    <div class="rounded-xl border border-border/80 bg-card overflow-hidden shadow-sm">
        <x-ui.table>
            <x-ui.table.header class="bg-muted/40">
                <x-ui.table.row>
                    @foreach($columns as $col)
                        @php
                            $colTitle = is_array($col) ? ($col['title'] ?? ($col['header'] ?? ($col['label'] ?? ''))) : $col;
                            $align = is_array($col) ? ($col['align'] ?? 'left') : 'left';
                        @endphp
                        <x-ui.table.head class="{{ $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left') }} font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">
                            {{ $colTitle }}
                        </x-ui.table.head>
                    @endforeach
                </x-ui.table.row>
            </x-ui.table.header>

            <x-ui.table.body>
                @forelse($data as $rowIndex => $row)
                    <x-ui.table.row
                        class="hover:bg-muted/50 transition-colors {{ $onRowClick ? 'cursor-pointer' : '' }}"
                        @if($onRowClick) x-on:click="{{ $onRowClick }}(@js($row))" @endif
                    >
                        @foreach($columns as $col)
                            @php
                                $colKey = is_array($col) ? ($col['key'] ?? ($col['dataIndex'] ?? '')) : $col;
                                $align = is_array($col) ? ($col['align'] ?? 'left') : 'left';
                                $cellVal = is_array($row) ? ($row[$colKey] ?? '') : ($row->$colKey ?? '');
                            @endphp
                            <x-ui.table.cell class="{{ $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left') }} py-3 text-sm">
                                @if(isset(${"cell_" . $colKey}))
                                    {{ ${"cell_" . $colKey}($row) }}
                                @else
                                    {{ $cellVal }}
                                @endif
                            </x-ui.table.cell>
                        @endforeach
                    </x-ui.table.row>
                @empty
                    <x-ui.table.row>
                        <x-ui.table.cell :colspan="count($columns)" class="h-48 text-center">
                            <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                <x-ui.icon name="inbox" class="size-8 stroke-[1.5]" />
                                <p class="text-sm font-medium">{{ $emptyMessage }}</p>
                            </div>
                        </x-ui.table.cell>
                    </x-ui.table.row>
                @endforelse
            </x-ui.table.body>
        </x-ui.table>
    </div>

    <!-- Pagination Footer -->
    @if($pagination)
        <x-common.pagination :pagination="$pagination" />
    @endif
</div>
