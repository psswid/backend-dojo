<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $topic?->name ?? __('Due Review') }}
        </h2>
    </x-slot>

    <livewire:quiz-session :topic="$topic" />
</x-app-layout>
