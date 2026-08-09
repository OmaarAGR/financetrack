<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $currency_default = 'COP';
    public string $locale = 'es';
    public string $timezone = 'America/Bogota';

    public function mount(): void
    {
        $user = Auth::user();

        $this->currency_default = $user->currency_default;
        $this->locale = $user->locale;
        $this->timezone = $user->timezone;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'currency_default' => ['required', 'string', 'size:3'],
            'locale' => ['required', 'in:es,en'],
            'timezone' => ['required', 'timezone'],
        ]);

        Auth::user()->update($validated);

        $this->dispatch('toast', type: 'success', message: __('Preferencias actualizadas.'));
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Preferencias') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <header class="mb-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Moneda, idioma y zona horaria') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Estas preferencias controlan cómo se formatean los montos y fechas en toda la aplicación.') }}
                </p>
            </header>

            <form wire:submit="save" class="space-y-6">
                <div>
                    <x-input-label for="currency_default" :value="__('Moneda principal')" />
                    <select wire:model="currency_default" id="currency_default" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="COP">COP — Peso colombiano</option>
                        <option value="USD">USD — Dólar estadounidense</option>
                        <option value="EUR">EUR — Euro</option>
                        <option value="MXN">MXN — Peso mexicano</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('currency_default')" />
                </div>

                <div>
                    <x-input-label for="locale" :value="__('Idioma')" />
                    <select wire:model="locale" id="locale" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="es">Español</option>
                        <option value="en">English</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('locale')" />
                </div>

                <div>
                    <x-input-label for="timezone" :value="__('Zona horaria')" />
                    <select wire:model="timezone" id="timezone" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @foreach (['America/Bogota', 'America/Mexico_City', 'America/Lima', 'America/Santiago', 'America/Argentina/Buenos_Aires', 'UTC'] as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
                </div>

                <x-primary-button>{{ __('Guardar') }}</x-primary-button>
            </form>
        </div>
    </div>
</div>
