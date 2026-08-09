<?php

use App\Services\InsightService;
use App\Services\ReportService;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function with(ReportService $reports, InsightService $insights): array
    {
        $summary = $reports->monthlySummary(auth()->user(), $this->year, $this->month);

        $fixedExpense = Transaction::query()
            ->where('type', TransactionType::Expense->value)
            ->whereYear('date', $this->year)->whereMonth('date', $this->month)
            ->where('is_recurring_generated', true)
            ->sum('amount');

        return [
            'summary' => $summary,
            'highlights' => $insights->monthlyHighlights($summary, auth()->user(), $this->year, $this->month),
            'expenseByCategory' => $reports->expenseByCategory(auth()->user(), $this->year, $this->month),
            'fixedExpense' => \App\Support\Money::of($fixedExpense),
            'variableExpense' => $summary['expense']->subtract(\App\Support\Money::of($fixedExpense)),
            'periodLabel' => Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y'),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reporte mensual') }}
            </h2>
            <div class="flex items-center gap-2">
                <button wire:click="previousMonth" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                    <x-dynamic-icon name="chevron-right" class="h-4 w-4 -scale-x-100" />
                </button>
                <span class="min-w-[9rem] text-center text-sm font-medium capitalize text-gray-700 dark:text-gray-300">{{ $periodLabel }}</span>
                <button wire:click="nextMonth" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                    <x-dynamic-icon name="chevron-right" class="h-4 w-4" />
                </button>
                <a href="{{ route('reports.monthly.pdf', ['year' => $year, 'month' => $month]) }}" target="_blank" class="ms-2 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    <x-dynamic-icon name="arrow-down-tray" class="h-4 w-4" />
                    {{ __('Exportar PDF') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <x-stat-card :label="__('Ingresos')" icon="arrow-up-circle" icon-color="text-green-600 dark:text-green-400" icon-bg="bg-green-50 dark:bg-green-500/10">
                <x-money :amount="$summary['income']" />
            </x-stat-card>
            <x-stat-card :label="__('Gastos')" icon="arrow-down-circle" icon-color="text-red-600 dark:text-red-400" icon-bg="bg-red-50 dark:bg-red-500/10">
                <x-money :amount="$summary['expense']" />
            </x-stat-card>
            <x-stat-card :label="__('Ahorro')" icon="chart-pie">
                <x-money :amount="$summary['savings']" />
                <x-slot name="footer">{{ number_format($summary['savingsRate'], 1) }}% {{ __('de tasa de ahorro') }}</x-slot>
            </x-stat-card>
            <x-stat-card :label="__('Gastos fijos')" icon="arrow-path">
                <x-money :amount="$fixedExpense" />
                <x-slot name="footer">{{ __('generados por recurrentes') }}</x-slot>
            </x-stat-card>
            <x-stat-card :label="__('Gastos variables')" icon="wallet">
                <x-money :amount="$variableExpense" />
            </x-stat-card>
            <x-stat-card :label="__('Patrimonio actual')" icon="banknotes">
                <x-money :amount="$summary['netWorth']" />
            </x-stat-card>
        </div>

        @if (! empty($highlights))
            <div class="rounded-xl border border-primary-100 bg-primary-50/60 p-5 dark:border-primary-500/20 dark:bg-primary-500/5">
                <h3 class="mb-2 text-sm font-semibold text-primary-800 dark:text-primary-300">{{ __('Análisis del mes') }}</h3>
                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($highlights as $line)
                        <li class="flex gap-2"><span class="text-primary-500">&bull;</span>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Distribución de gastos por categoría') }}</h3>
            @if ($expenseByCategory->isEmpty())
                <p class="py-8 text-center text-sm text-gray-400">{{ __('Sin gastos registrados en este periodo.') }}</p>
            @else
                <div class="space-y-3">
                    @foreach ($expenseByCategory as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <x-category-icon :category="$row['category']" size="h-6 w-6" />
                                    {{ $row['category']?->name ?? __('Sin categoría') }}
                                </span>
                                <span class="font-medium text-gray-900 dark:text-white"><x-money :amount="$row['total']" /> ({{ number_format($row['percentage'], 1) }}%)</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full" style="width: {{ $row['percentage'] }}%; background-color: {{ $row['category']?->color ?? '#6b7280' }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
