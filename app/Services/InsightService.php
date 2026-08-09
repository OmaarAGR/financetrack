<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Turns the numbers ReportService already computed into short Spanish
 * sentences. Every phrase is a template filled with real aggregates —
 * never freeform generated text — so nothing here can be "made up".
 */
class InsightService
{
    /** Gastos individuales por debajo de este monto cuentan como "hormiga". */
    private const ANT_EXPENSE_THRESHOLD = 30000;

    /** Un gasto se marca inusual si supera la media histórica + N desviaciones. */
    private const UNUSUAL_STDDEV_MULTIPLIER = 1.5;

    /**
     * @return array<int, string>
     */
    public function monthlyHighlights(array $summary, ?User $user = null, ?int $year = null, ?int $month = null): array
    {
        $lines = [];
        $currency = $summary['currency'] ?? $user?->currency_default ?? 'COP';

        if (! $summary['income']->isZero() || ! $summary['expense']->isZero()) {
            $lines[] = $this->expenseVsPreviousMonth($summary);
        }

        if ($summary['topCategory']) {
            $lines[] = sprintf(
                '%s representó el %s%% de tus gastos este mes.',
                $summary['topCategory']['category']?->name ?? 'Una categoría',
                number_format($summary['topCategory']['percentage'], 1),
            );
        }

        if ($summary['savings']->isPositive() && ! $summary['income']->isZero()) {
            $lines[] = sprintf(
                'Ahorraste %s este mes, equivalente al %s%% de tus ingresos.',
                $summary['savings']->format($currency),
                number_format($summary['savingsRate'], 1),
            );
        } elseif ($summary['savings']->isNegative()) {
            $lines[] = sprintf(
                'Gastaste %s más de lo que ingresó este mes.',
                $summary['savings']->abs()->format($currency),
            );
        }

        if ($summary['daysOfAutonomy'] !== null) {
            $lines[] = sprintf(
                'Con tu ritmo de gasto actual, tu dinero disponible te alcanza para %s días sin nuevos ingresos.',
                number_format($summary['daysOfAutonomy'], 0),
            );
        }

        if ($user && $year && $month) {
            $ants = $this->antExpenses($user, $year, $month, $currency, $summary['expense']);
            if ($ants && $ants['percentageOfExpense'] >= 5) {
                $lines[] = sprintf(
                    'Tus gastos hormiga (compras menores a $%s) sumaron %s este mes en %d %s — %s%% de tu gasto total.',
                    number_format(self::ANT_EXPENSE_THRESHOLD, 0, ',', '.'),
                    $ants['total']->format($currency),
                    $ants['count'],
                    $ants['count'] === 1 ? 'movimiento' : 'movimientos',
                    number_format($ants['percentageOfExpense'], 1),
                );
            }

            $unusual = $this->unusualExpenses($user, $year, $month, $currency);
            if ($unusual->isNotEmpty()) {
                $top = $unusual->sortByDesc(fn (Transaction $t) => $t->amount->toFloat())->first();
                $lines[] = sprintf(
                    'Detectamos un gasto inusual: %s (%s) está muy por encima de tu promedio habitual en %s.',
                    $top->description ?: $top->category?->name,
                    $top->amount->format($currency),
                    $top->category?->name ?? 'esa categoría',
                );
            }
        }

        return $lines;
    }

    /**
     * Gastos hormiga: la suma de compras pequeñas y frecuentes que, juntas,
     * suelen pesar más de lo que el usuario percibe.
     *
     * @return array{total: Money, count: int, percentageOfExpense: float}|null
     */
    public function antExpenses(User $user, int $year, int $month, string $currency, Money $totalExpense): ?array
    {
        $rows = Transaction::query()
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.type', TransactionType::Expense->value)
            ->where('accounts.currency', $currency)
            ->whereYear('transactions.date', $year)->whereMonth('transactions.date', $month)
            ->where('transactions.amount', '<', self::ANT_EXPENSE_THRESHOLD)
            ->select('transactions.*')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $total = $rows->reduce(fn (Money $c, Transaction $t) => $c->add($t->amount), Money::zero());

        return [
            'total' => $total,
            'count' => $rows->count(),
            'percentageOfExpense' => $total->percentageOf($totalExpense),
        ];
    }

    /**
     * Gastos que se salen notablemente del patrón histórico de su categoría
     * (más de N desviaciones estándar sobre la media de los últimos 6 meses,
     * excluyendo el mes actual). No requiere ML: es media + desviación simple.
     *
     * @return Collection<int, Transaction>
     */
    public function unusualExpenses(User $user, int $year, int $month, string $currency): Collection
    {
        $periodStart = now()->createFromDate($year, $month, 1)->subMonths(6)->startOfMonth();
        $periodEnd = now()->createFromDate($year, $month, 1)->startOfMonth();

        $stats = Transaction::query()
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.type', TransactionType::Expense->value)
            ->where('accounts.currency', $currency)
            ->whereBetween('transactions.date', [$periodStart, $periodEnd])
            ->selectRaw('transactions.category_id as category_id, AVG(transactions.amount) as avg_amount, STDDEV(transactions.amount) as stddev_amount, COUNT(*) as n')
            ->groupBy('transactions.category_id')
            ->having('n', '>=', 3)
            ->get()
            ->keyBy('category_id');

        if ($stats->isEmpty()) {
            return collect();
        }

        return Transaction::query()
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->with('category')
            ->where('transactions.type', TransactionType::Expense->value)
            ->where('accounts.currency', $currency)
            ->whereYear('transactions.date', $year)->whereMonth('transactions.date', $month)
            ->whereIn('transactions.category_id', $stats->keys())
            ->select('transactions.*')
            ->get()
            ->filter(function (Transaction $transaction) use ($stats) {
                $stat = $stats->get($transaction->category_id);
                $stddev = (float) ($stat->stddev_amount ?? 0);
                $threshold = (float) $stat->avg_amount + (self::UNUSUAL_STDDEV_MULTIPLIER * max($stddev, (float) $stat->avg_amount * 0.2));

                return $transaction->amount->toFloat() > $threshold;
            })
            ->values();
    }

    private function expenseVsPreviousMonth(array $summary): string
    {
        $change = $summary['changeExpense'];

        if (abs($change) < 1) {
            return 'Tus gastos se mantuvieron prácticamente iguales al mes anterior.';
        }

        return sprintf(
            'Este mes gastaste %s%% %s al mes anterior.',
            number_format(abs($change), 1),
            $change > 0 ? 'más' : 'menos',
        );
    }
}
