<x-app-layout>
    {{-- The Livewire component provides its own header slot via
         <x-slot name="header">...</x-slot>, so the layout fills
         the header from the component itself. We only need a
         wrapping container to constrain the dashboard's max width. --}}
    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-6">
            <livewire:tsp.dashboard />
        </div>
    </div>
</x-app-layout>
