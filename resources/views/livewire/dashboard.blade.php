<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (! $hasAnyAccount)
            <x-empty-state
                icon="wallet"
                :title="__('Empecemos por crear tu primera cuenta')"
                :description="__('Registra tus bancos, billeteras o efectivo para que el dashboard pueda calcular tu patrimonio y tus gastos.')"
            >
                <x-slot name="action">
                    <a href="{{ route('accounts.index') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        <x-dynamic-icon name="plus" class="h-4 w-4" />
                        {{ __('Crear mi primera cuenta') }}
                    </a>
                </x-slot>
            </x-empty-state>
        @elseif (! $hasAnyTransaction)
            <x-empty-state
                icon="list-bullet"
                :title="__('Registra tu primer movimiento')"
                :description="__('En cuanto registres ingresos y gastos, aquí verás tu resumen financiero completo.')"
            >
                <x-slot name="action">
                    <button type="button" x-on:click="$dispatch('open-transaction-form', { type: 'expense' })" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        <x-dynamic-icon name="plus" class="h-4 w-4" />
                        {{ __('Nueva transacción') }}
                    </button>
                </x-slot>
            </x-empty-state>
        @else
            {{-- Patrimonio total: nunca mezcla monedas, una línea por cada una --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card :label="__('Patrimonio total')" icon="banknotes">
                    @foreach ($netWorthByCurrency as $nwCurrency => $nwAmount)
                        <div><x-money :amount="$nwAmount" :currency="$nwCurrency" /></div>
                    @endforeach
                </x-stat-card>
            </div>

            {{-- El resto del dashboard se repite por cada moneda que uses, para no sumar montos de distintas monedas --}}
            @foreach ($summary as $currency => $currencySummary)
                <div class="space-y-6">
                    @if ($summary->count() > 1)
                        <h3 class="border-t border-gray-200 pt-6 text-sm font-semibold uppercase tracking-wide text-gray-400 dark:border-gray-700">{{ $currency }}</h3>
                    @endif

                    {{-- Fila 1: tarjetas de resumen --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <x-stat-card :label="__('Ingresos del mes')" icon="arrow-up-circle" icon-color="text-green-600 dark:text-green-400" icon-bg="bg-green-50 dark:bg-green-500/10">
                            <x-money :amount="$currencySummary['income']" :currency="$currency" />
                            <x-slot name="footer">
                                <x-badge-variation :value="$currencySummary['changeIncome']" :favorable-when-positive="true" />
                                <span class="ms-1 text-gray-400">{{ __('vs. mes anterior') }}</span>
                            </x-slot>
                        </x-stat-card>

                        <x-stat-card :label="__('Gastos del mes')" icon="arrow-down-circle" icon-color="text-red-600 dark:text-red-400" icon-bg="bg-red-50 dark:bg-red-500/10">
                            <x-money :amount="$currencySummary['expense']" :currency="$currency" />
                            <x-slot name="footer">
                                <x-badge-variation :value="$currencySummary['changeExpense']" :favorable-when-positive="false" />
                                <span class="ms-1 text-gray-400">{{ __('vs. mes anterior') }}</span>
                            </x-slot>
                        </x-stat-card>

                        <x-stat-card :label="__('Ahorro del mes')" icon="chart-pie">
                            <x-money :amount="$currencySummary['savings']" :currency="$currency" />
                            <x-slot name="footer">
                                <span class="font-medium {{ $currencySummary['savings']->isNegative() ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ number_format($currencySummary['savingsRate'], 1) }}%
                                </span>
                                <span class="ms-1 text-gray-400">{{ __('de tasa de ahorro') }}</span>
                            </x-slot>
                        </x-stat-card>

                        <x-stat-card :label="__('Gasto promedio diario')" icon="calendar">
                            <x-money :amount="$currencySummary['avgDailyExpense']" :currency="$currency" />
                        </x-stat-card>

                        <x-stat-card :label="__('Mayor gasto del mes')" icon="arrow-trending-up">
                            @if ($currencySummary['biggestExpense'])
                                <x-money :amount="$currencySummary['biggestExpense']->amount" :currency="$currency" />
                                <x-slot name="footer">
                                    <span class="text-gray-400">{{ $currencySummary['biggestExpense']->description ?: $currencySummary['biggestExpense']->category?->name }}</span>
                                </x-slot>
                            @else
                                <span class="text-gray-400">&mdash;</span>
                            @endif
                        </x-stat-card>

                        <x-stat-card :label="__('Categoría con más gasto')" icon="tag">
                            @if ($currencySummary['topCategory'])
                                {{ $currencySummary['topCategory']['category']?->name }}
                                <x-slot name="footer">
                                    <span class="text-gray-400">{{ number_format($currencySummary['topCategory']['percentage'], 1) }}% {{ __('de tus gastos') }}</span>
                                </x-slot>
                            @else
                                <span class="text-gray-400">&mdash;</span>
                            @endif
                        </x-stat-card>

                        <x-stat-card :label="__('Días de autonomía financiera')" icon="flag">
                            @if ($currencySummary['daysOfAutonomy'] !== null)
                                {{ number_format($currencySummary['daysOfAutonomy'], 0) }} {{ __('días') }}
                                <x-slot name="footer">
                                    <span class="text-gray-400">{{ __('con tu ritmo de gasto actual') }}</span>
                                </x-slot>
                            @else
                                <span class="text-gray-400">&mdash;</span>
                            @endif
                        </x-stat-card>
                    </div>

                    {{-- Insights automáticos --}}
                    @if (! empty($highlights[$currency]))
                        <div class="rounded-xl border border-primary-100 bg-primary-50/60 p-5 dark:border-primary-500/20 dark:bg-primary-500/5">
                            <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-primary-800 dark:text-primary-300">
                                <x-dynamic-icon name="document-chart-bar" class="h-4 w-4" />
                                {{ __('¿En qué se me fue el dinero este mes?') }}
                            </h3>
                            <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                @foreach ($highlights[$currency] as $line)
                                    <li class="flex gap-2">
                                        <span class="text-primary-500">&bull;</span>
                                        {{ $line }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Gráficos --}}
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        @php
                            $categoryRows = $expenseByCategory->get($currency, collect());
                        @endphp
                        {{-- Gastos por categoría --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Gastos por categoría (este mes)') }}</h3>
                            @if ($categoryRows->isEmpty())
                                <p class="py-12 text-center text-sm text-gray-400">{{ __('Sin gastos registrados este mes.') }}</p>
                            @else
                                @php
                                    $categoryLabels = $categoryRows->map(fn ($row) => $row['category']?->name ?? __('Sin categoría'));
                                    $categoryColors = $categoryRows->map(fn ($row) => $row['category']?->color ?? '#6b7280');
                                    $categoryValues = $categoryRows->map(fn ($row) => $row['total']->toFloat());
                                    $chartId = 'chart-expense-category-'.$currency;
                                    $chartKey = $chartId.'-'.md5($categoryValues->join(',').$categoryLabels->join(','));
                                @endphp
                                <div wire:key="{{ $chartKey }}" wire:ignore id="{{ $chartId }}" x-data x-init="window.renderFinanceChart('{{ $chartId }}', (palette) => ({
                                    chart: { type: 'donut', height: 288, fontFamily: 'Figtree, sans-serif' },
                                    labels: @js($categoryLabels),
                                    series: @js($categoryValues),
                                    colors: @js($categoryColors),
                                    legend: { position: 'bottom', labels: { colors: palette.text } },
                                    dataLabels: { enabled: false },
                                    stroke: { width: 2, colors: [isDarkNow() ? '#1f2937' : '#ffffff'] },
                                    tooltip: { y: { formatter: (v) => formatMoney(v, '{{ $currency }}', '{{ auth()->user()->locale }}') } },
                                }))"></div>
                            @endif
                        </div>

                        @php
                            $evoRows = $monthlyEvolution->get($currency, collect());
                        @endphp
                        {{-- Evolución mensual --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Evolución mensual') }}</h3>
                                <div class="flex gap-1">
                                    @foreach ([6 => '6M', 12 => '12M'] as $value => $label)
                                        <button
                                            wire:click="setEvolutionMonths({{ $value }})"
                                            @class([
                                                'rounded-lg px-2 py-1 text-xs font-medium',
                                                'bg-primary-600 text-white' => $evolutionMonths === $value,
                                                'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' => $evolutionMonths !== $value,
                                            ])
                                        >
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            @php
                                $evoLabels = $evoRows->pluck('label');
                                $evoIncome = $evoRows->map(fn ($row) => $row['income']->toFloat());
                                $evoExpense = $evoRows->map(fn ($row) => $row['expense']->toFloat());
                                $evoSavings = $evoRows->map(fn ($row) => $row['savings']->toFloat());
                                $chartId = 'chart-evolution-'.$currency;
                                $chartKey = $chartId.'-'.md5($evoLabels->join(',').$evoIncome->join(',').$evoExpense->join(','));
                            @endphp
                            <div wire:key="{{ $chartKey }}" wire:ignore id="{{ $chartId }}" x-data x-init="window.renderFinanceChart('{{ $chartId }}', (palette) => ({
                                chart: { type: 'line', height: 288, fontFamily: 'Figtree, sans-serif', toolbar: { show: false } },
                                series: [
                                    { name: '{{ __('Ingresos') }}', data: @js($evoIncome) },
                                    { name: '{{ __('Gastos') }}', data: @js($evoExpense) },
                                    { name: '{{ __('Ahorro') }}', data: @js($evoSavings) },
                                ],
                                colors: [palette.income, palette.expense, palette.savings],
                                xaxis: { categories: @js($evoLabels), labels: { style: { colors: palette.text } } },
                                yaxis: { labels: { style: { colors: palette.text }, formatter: (v) => formatCompact(v) } },
                                grid: { borderColor: palette.grid },
                                stroke: { width: 2, curve: 'smooth' },
                                legend: { labels: { colors: palette.text } },
                                tooltip: { y: { formatter: (v) => formatMoney(v, '{{ $currency }}', '{{ auth()->user()->locale }}') } },
                            }))"></div>
                        </div>

                        @php
                            $accountRows = $expenseByAccount->get($currency, collect());
                        @endphp
                        {{-- Gasto por cuenta --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Gastos por cuenta (este mes)') }}</h3>
                            @if ($accountRows->isEmpty())
                                <p class="py-12 text-center text-sm text-gray-400">{{ __('Sin gastos registrados este mes.') }}</p>
                            @else
                                @php
                                    $accLabels = $accountRows->map(fn ($row) => $row['account']->name);
                                    $accColors = $accountRows->map(fn ($row) => $row['account']->color);
                                    $accValues = $accountRows->map(fn ($row) => $row['total']->toFloat());
                                    $chartId = 'chart-expense-account-'.$currency;
                                    $chartKey = $chartId.'-'.md5($accValues->join(',').$accLabels->join(','));
                                @endphp
                                <div wire:key="{{ $chartKey }}" wire:ignore id="{{ $chartId }}" x-data x-init="window.renderFinanceChart('{{ $chartId }}', (palette) => ({
                                    chart: { type: 'bar', height: 288, fontFamily: 'Figtree, sans-serif', toolbar: { show: false } },
                                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4, barHeight: '55%' } },
                                    series: [{ name: '{{ __('Gasto') }}', data: @js($accValues) }],
                                    colors: @js($accColors),
                                    xaxis: { categories: @js($accLabels), labels: { style: { colors: palette.text }, formatter: (v) => formatCompact(v) } },
                                    yaxis: { labels: { style: { colors: palette.text } } },
                                    grid: { borderColor: palette.grid },
                                    legend: { show: false },
                                    dataLabels: { enabled: false },
                                    tooltip: { y: { formatter: (v) => formatMoney(v, '{{ $currency }}', '{{ auth()->user()->locale }}') } },
                                }))"></div>
                            @endif
                        </div>

                        @php
                            $distRows = $moneyDistribution->get($currency, collect());
                        @endphp
                        {{-- Distribución del dinero actual --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Distribución del dinero actual') }}</h3>
                            @if ($distRows->isEmpty())
                                <p class="py-12 text-center text-sm text-gray-400">{{ __('Todavía no tienes saldo positivo en tus cuentas.') }}</p>
                            @else
                                @php
                                    $distLabels = $distRows->map(fn ($row) => $row['account']->name);
                                    $distColors = $distRows->map(fn ($row) => $row['account']->color);
                                    $distValues = $distRows->map(fn ($row) => $row['balance']->toFloat());
                                    $chartId = 'chart-money-distribution-'.$currency;
                                    $chartKey = $chartId.'-'.md5($distValues->join(',').$distLabels->join(','));
                                @endphp
                                <div wire:key="{{ $chartKey }}" wire:ignore id="{{ $chartId }}" x-data x-init="window.renderFinanceChart('{{ $chartId }}', (palette) => ({
                                    chart: { type: 'donut', height: 288, fontFamily: 'Figtree, sans-serif' },
                                    labels: @js($distLabels),
                                    series: @js($distValues),
                                    colors: @js($distColors),
                                    legend: { position: 'bottom', labels: { colors: palette.text } },
                                    dataLabels: { enabled: false },
                                    stroke: { width: 2, colors: [isDarkNow() ? '#1f2937' : '#ffffff'] },
                                    tooltip: { y: { formatter: (v) => formatMoney(v, '{{ $currency }}', '{{ auth()->user()->locale }}') } },
                                }))"></div>
                            @endif
                        </div>

                        @php
                            $nwRows = $netWorthEvolution->get($currency, collect());
                        @endphp
                        {{-- Evolución del patrimonio --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Evolución del patrimonio') }}</h3>
                            @php
                                $nwLabels = $nwRows->pluck('label');
                                $nwValues = $nwRows->map(fn ($row) => $row['netWorth']->toFloat());
                                $chartId = 'chart-net-worth-'.$currency;
                                $chartKey = $chartId.'-'.md5($nwValues->join(',').$nwLabels->join(','));
                            @endphp
                            <div wire:key="{{ $chartKey }}" wire:ignore id="{{ $chartId }}" x-data x-init="window.renderFinanceChart('{{ $chartId }}', (palette) => ({
                                chart: { type: 'area', height: 260, fontFamily: 'Figtree, sans-serif', toolbar: { show: false } },
                                series: [{ name: '{{ __('Patrimonio') }}', data: @js($nwValues) }],
                                colors: [palette.series[0]],
                                fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
                                xaxis: { categories: @js($nwLabels), labels: { style: { colors: palette.text } } },
                                yaxis: { labels: { style: { colors: palette.text }, formatter: (v) => formatCompact(v) } },
                                grid: { borderColor: palette.grid },
                                stroke: { width: 2, curve: 'smooth' },
                                dataLabels: { enabled: false },
                                tooltip: { y: { formatter: (v) => formatMoney(v, '{{ $currency }}', '{{ auth()->user()->locale }}') } },
                            }))"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
