<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $title }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-empty-state
            icon="cog"
            :title="__('Este módulo está en construcción')"
            :description="__('Lo estamos desarrollando en una fase posterior del roadmap.')"
        />
    </div>
</x-app-layout>
