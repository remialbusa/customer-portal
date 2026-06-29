{{-- Optional wrapper: <x-guest-layout title="Login" header="Welcome back"> ... </x-guest-layout> --}}
<x-layouts.guest :title="$title ?? null" :header="$header ?? null">
    {{ $slot }}
</x-layouts.guest>
