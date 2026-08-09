@props(['category', 'size' => 'h-8 w-8'])

@php
    $color = $category->color ?? '#6b7280';
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex $size shrink-0 items-center justify-center rounded-full"]) }}
    style="background-color: {{ $color }}1a; color: {{ $color }};"
>
    <x-dynamic-icon :name="$category->icon ?? 'tag'" class="h-4 w-4" />
</span>
