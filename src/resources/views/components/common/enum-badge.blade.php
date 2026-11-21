@props([
    "value",
    "variant" => "outline",
])

@php
    $strValue = is_bool($value) ? ($value ? "ACTIVE" : "INACTIVE") : (string) $value;
    $normalized = strtoupper(trim($strValue));

    $colorMap = [
        // Success / Available (Emerald / Green)
        "VACANT" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",
        "CLEAN" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",
        "CHECKED_IN" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",
        "COMPLETED" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",
        "PAID" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",
        "RESOLVED" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",
        "CASH" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",
        "ACTIVE" => "bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-emerald-200",

        // Warning / Action Required (Amber / Yellow / Orange)
        "PENDING" => "bg-amber-100 text-amber-800 hover:bg-amber-200 border-amber-200",
        "PARTIALLY_PAID" => "bg-amber-100 text-amber-800 hover:bg-amber-200 border-amber-200",
        "OCCUPIED" => "bg-amber-100 text-amber-800 hover:bg-amber-200 border-amber-200",
        "MEDIUM" => "bg-amber-100 text-amber-800 hover:bg-amber-200 border-amber-200",
        "REPORTED" => "bg-amber-100 text-amber-800 hover:bg-amber-200 border-amber-200",
        "ELECTRICAL" => "bg-amber-100 text-amber-800 hover:bg-amber-200 border-amber-200",
        "FURNITURE" => "bg-orange-100 text-orange-800 hover:bg-orange-200 border-orange-200",

        // In Progress / Active (Blue)
        "RESERVED" => "bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200",
        "CLEANING" => "bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200",
        "CONFIRMED" => "bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200",
        "IN_PROGRESS" => "bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200",
        "ASSIGNED" => "bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200",
        "CARD" => "bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200",
        "PLUMBING" => "bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200",

        // Critical / Destructive (Red / Rose)
        "OUT_OF_ORDER" => "bg-red-100 text-red-800 hover:bg-red-200 border-red-200",
        "DIRTY" => "bg-red-100 text-red-800 hover:bg-red-200 border-red-200",
        "CANCELLED" => "bg-red-100 text-red-800 hover:bg-red-200 border-red-200",
        "NO_SHOW" => "bg-rose-100 text-rose-800 hover:bg-rose-200 border-rose-200",
        "UNPAID" => "bg-red-100 text-red-800 hover:bg-red-200 border-red-200",
        "FAILED" => "bg-red-100 text-red-800 hover:bg-red-200 border-red-200",
        "URGENT" => "bg-red-100 text-red-800 hover:bg-red-200 border-red-200",
        "HIGH" => "bg-rose-100 text-rose-800 hover:bg-rose-200 border-rose-200",
        "INACTIVE" => "bg-rose-100 text-rose-800 hover:bg-rose-200 border-rose-200",

        // Neutral / Inactive (Slate / Gray)
        "CHECKED_OUT" => "bg-slate-100 text-slate-800 hover:bg-slate-200 border-slate-200",
        "REFUNDED" => "bg-slate-100 text-slate-800 hover:bg-slate-200 border-slate-200",
        "BANK_TRANSFER" => "bg-slate-100 text-slate-800 hover:bg-slate-200 border-slate-200",
        "LOW" => "bg-slate-100 text-slate-800 hover:bg-slate-200 border-slate-200",
        "OTHER" => "bg-slate-100 text-slate-800 hover:bg-slate-200 border-slate-200",
        "TURNDOWN" => "bg-slate-100 text-slate-800 hover:bg-slate-200 border-slate-200",

        // Special / Category specific (Purple / Cyan)
        "DEEP_CLEAN" => "bg-purple-100 text-purple-800 hover:bg-purple-200 border-purple-200",
        "UPI" => "bg-purple-100 text-purple-800 hover:bg-purple-200 border-purple-200",
        "APPLIANCE" => "bg-purple-100 text-purple-800 hover:bg-purple-200 border-purple-200",
        "HVAC" => "bg-cyan-100 text-cyan-800 hover:bg-cyan-200 border-cyan-200",
    ];

    $colorClass = $colorMap[$normalized] ?? "bg-gray-100 text-gray-800 border-gray-200";
    $label = ucwords(str_replace('_', ' ', strtolower((string)$strValue)));
@endphp

<x-ui.badge
    :variant="$variant"
    {{ $attributes->class(["font-medium", $colorClass]) }}
>
    {{ $label }}
</x-ui.badge>
