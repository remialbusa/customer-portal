<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Access denied
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">403 — Forbidden</h3>
                <p class="text-sm text-gray-600">{{ $message ?? 'You do not have access to that resource.' }}</p>
                <a href="{{ url()->previous() ?: route('dashboard') }}"
                   class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded text-xs uppercase tracking-widest">
                    Go back
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
