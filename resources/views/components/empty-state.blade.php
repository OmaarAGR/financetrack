@props([
    'title',
    'description' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 dark:border-gray-700 px-6 py-16 text-center']) }}>
    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">
        <x-dynamic-icon :name="$icon" class="h-7 w-7" />
    </div>

    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
