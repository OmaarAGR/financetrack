@props([
    'label',
    'icon' => null,
    'iconColor' => 'text-primary-600 dark:text-primary-400',
    'iconBg' => 'bg-primary-50 dark:bg-primary-500/10',
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>
    <div class="flex items-start justify-between">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>

        @if ($icon)
            <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $iconBg }} {{ $iconColor }}">
                <x-dynamic-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>

    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="mt-2 text-sm">
            {{ $footer }}
        </div>
    @endisset
</div>
