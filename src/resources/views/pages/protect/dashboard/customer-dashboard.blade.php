<x-layouts.protect-layout title="Customer Dashboard">
    <h1 class="text-4xl">Customer Dashboard</h1>
    {{$name}}

    {{Auth::user()}}
</x-layouts.protect-layout>