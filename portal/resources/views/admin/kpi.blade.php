<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Executive KPI Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded shadow">
                <p class="text-sm text-gray-500">
                    Logged in as <span class="font-medium">{{ $user->name }}</span>
                    &middot; Administrator
                </p>
                <p class="mt-4 text-gray-700">
                    Hero stats, MTTR, MTBF, and drill-down tiles will be built here.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
