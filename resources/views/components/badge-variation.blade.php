@props([
    'value',
    'favorableWhenPositive' => true,
    'suffix' => '%',
])

@php
    $isUp = $value > 0;
    $isFlat = abs($value) < 0.05;
    $isFavorable = $isFlat ? null : ($favorableWhenPositive ? $isUp : ! $isUp);

    $classes = match (true) {
        $isFlat => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        $isFavorable => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        default => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium $classes"]) }}>
    @unless ($isFlat)
        <x-dynamic-icon name="arrow-trending-up" class="h-3.5 w-3.5 {{ $isUp ? '' : '-scale-y-100' }}" />
    @endunless
    {{ $isUp && ! $isFlat ? '+' : '' }}{{ number_format(abs($value), 1) }}{{ $suffix }}
</span>
