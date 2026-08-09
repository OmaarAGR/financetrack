@props([
    'amount',
    'currency' => null,
    'signed' => false,
])

@php
    use App\Support\Money;

    $money = $amount instanceof Money ? $amount : Money::of($amount ?? 0);
    $user = auth()->user();
    $currency ??= $user->currency_default ?? 'COP';
    $locale = $user->locale ?? 'es';
    $prefix = $signed && ! $money->isNegative() && ! $money->isZero() ? '+' : '';
@endphp

<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ $prefix }}{{ $money->format($currency, $locale) }}</span>
