<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns the raw transaction ledger into the aggregates the dashboard and
 * reports need. Every number here is derived on read (no stored snapshots
 * outside monthly_reports, added in Fase 5) directly from `transactions`.
 */
class ReportService
{
    public function __construct(private readonly BalanceCalculator $balances) {}

    /**
     * @return array{
     *   income: Money, expense: Money, savings: Money, savingsRate: float,
     *   avgDailyExpense: Money, biggestExpense: ?Transaction, transactionCount: int,
     *   changeIncome: float, changeExpense: float, changeSavings: float,
     *   changeSavingsRate: float, changeTransactionCount: float,
     *   netWorth: Money, daysOfAutonomy: ?float, topCategory: ?array,
     * }
     */
    public function monthlySummary(User $user, int $year, int $month): array
    {
        $current = $this->periodTotals($user, $year, $month);
        $previousDate = Carbon::create($year, $month, 1)->subMonthNoOverflow();
        $previous = $this->periodTotals($user, $previousDate->year, $previousDate->month);

        $netWorth = $this->balances->netWorth($user);

        $daysElapsed = min(
            Carbon::create($year, $month, 1)->daysInMonth,
            Carbon::create($year, $month, 1)->isSameMonth(now()) ? now()->day : Carbon::create($year, $month, 1)->daysInMonth,
        );
        $avgDailyExpense = $daysElapsed > 0 ? $current['expense']->divide($daysElapsed) : Money::zero();

        $liquidExpenseAvg = $avgDailyExpense->isPositive() ? $avgDailyExpense : $this->trailingDailyExpenseAverage($user);

        return [
            'income' => $current['income'],
            'expense' => $current['expense'],
            'savings' => $current['savings'],
            'savingsRate' => $current['savingsRate'],
            'avgDailyExpense' => $avgDailyExpense,
            'biggestExpense' => $current['biggestExpense'],
            'transactionCount' => $current['transactionCount'],
            'netWorth' => $netWorth,
            'daysOfAutonomy' => $liquidExpenseAvg->isPositive() ? $netWorth->divide($liquidExpenseAvg->toDecimalString())->toFloat() : null,
            'topCategory' => $current['topCategory'],
            'changeIncome' => $this->percentChange($previous['income'], $current['income']),
            'changeExpense' => $this->percentChange($previous['expense'], $current['expense']),
            'changeSavings' => $this->percentChange($previous['savings'], $current['savings']),
            'changeSavingsRate' => $current['savingsRate'] - $previous['savingsRate'],
            'changeTransactionCount' => $previous['transactionCount'] > 0
                ? (($current['transactionCount'] - $previous['transactionCount']) / $previous['transactionCount']) * 100
                : 0.0,
            'previous' => $previous,
        ];
    }

    /**
     * @return Collection<int, array{category: Category, total: Money, percentage: float}>
     */
    public function expenseByCategory(User $user, int $year, int $month): Collection
    {
        $rows = Transaction::query()
            ->where('type', TransactionType::Expense->value)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $totalExpense = $rows->reduce(fn (Money $carry, $row) => $carry->add(Money::of($row->total)), Money::zero());
        $categories = Category::withoutGlobalScopes()->whereIn('id', $rows->pluck('category_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($categories, $totalExpense) {
            $total = Money::of($row->total);

            return [
                'category' => $categories->get($row->category_id),
                'total' => $total,
                'percentage' => $total->percentageOf($totalExpense),
            ];
        })->values();
    }

    /**
     * @return Collection<int, array{account: Account, total: Money}>
     */
    public function expenseByAccount(User $user, int $year, int $month): Collection
    {
        $rows = Transaction::query()
            ->where('type', TransactionType::Expense->value)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw('account_id, SUM(amount) as total')
            ->groupBy('account_id')
            ->orderByDesc('total')
            ->get();

        $accounts = Account::withoutGlobalScopes()->whereIn('id', $rows->pluck('account_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => [
            'account' => $accounts->get($row->account_id),
            'total' => Money::of($row->total),
        ])->filter(fn ($row) => $row['account'] !== null)->values();
    }

    /**
     * @return Collection<int, array{account: Account, balance: Money}>
     */
    public function moneyDistribution(User $user): Collection
    {
        return $user->accounts()->where('is_active', true)->get()
            ->map(fn (Account $account) => [
                'account' => $account,
                'balance' => $this->balances->accountBalance($account),
            ])
            ->filter(fn ($row) => $row['balance']->isPositive());
    }

    /**
     * @return Collection<int, array{label: string, income: Money, expense: Money, savings: Money}>
     */
    public function monthlyEvolution(User $user, int $months = 12): Collection
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $rows = Transaction::query()
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->where('date', '>=', $start)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym, type, SUM(amount) as total")
            ->groupBy('ym', 'type')
            ->get()
            ->groupBy('ym');

        return collect(range(0, $months - 1))->map(function ($i) use ($start, $rows) {
            $date = $start->copy()->addMonths($i);
            $ym = $date->format('Y-m');
            $bucket = $rows->get($ym, collect());

            $income = Money::of($bucket->firstWhere('type', TransactionType::Income->value)->total ?? 0);
            $expense = Money::of($bucket->firstWhere('type', TransactionType::Expense->value)->total ?? 0);

            return [
                'label' => $date->translatedFormat('M Y'),
                'income' => $income,
                'expense' => $expense,
                'savings' => $income->subtract($expense),
            ];
        });
    }

    /**
     * @return array{
     *   income: Money, expense: Money, savings: Money, savingsRate: float,
     *   avgMonthlyIncome: Money, avgMonthlyExpense: Money,
     *   bestIncomeMonth: ?array, worstExpenseMonth: ?array, topCategory: ?array,
     *   months: Collection,
     * }
     */
    public function annualSummary(User $user, int $year): array
    {
        $rows = Transaction::query()
            ->whereYear('date', $year)
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->get();

        $months = collect(range(1, 12))->map(function ($month) use ($rows, $year) {
            $monthRows = $rows->filter(fn (Transaction $t) => $t->date->month === $month);
            $income = $monthRows->where('type', TransactionType::Income)->reduce(fn (Money $c, Transaction $t) => $c->add($t->amount), Money::zero());
            $expense = $monthRows->where('type', TransactionType::Expense)->reduce(fn (Money $c, Transaction $t) => $c->add($t->amount), Money::zero());

            return [
                'month' => $month,
                'label' => Carbon::create($year, $month, 1)->translatedFormat('M'),
                'income' => $income,
                'expense' => $expense,
                'savings' => $income->subtract($expense),
            ];
        });

        $income = $months->reduce(fn (Money $c, $m) => $c->add($m['income']), Money::zero());
        $expense = $months->reduce(fn (Money $c, $m) => $c->add($m['expense']), Money::zero());
        $savings = $income->subtract($expense);

        $bestIncomeMonth = $months->sortByDesc(fn ($m) => $m['income']->toFloat())->first();
        $worstExpenseMonth = $months->sortByDesc(fn ($m) => $m['expense']->toFloat())->first();

        $topCategoryRow = $rows->where('type', TransactionType::Expense)
            ->groupBy('category_id')
            ->map(fn ($group, $categoryId) => [
                'category_id' => $categoryId,
                'total' => $group->reduce(fn (Money $c, Transaction $t) => $c->add($t->amount), Money::zero()),
            ])
            ->sortByDesc(fn ($row) => $row['total']->toFloat())
            ->first();

        $topCategory = null;
        if ($topCategoryRow) {
            $topCategory = [
                'category' => Category::withoutGlobalScopes()->find($topCategoryRow['category_id']),
                'total' => $topCategoryRow['total'],
                'percentage' => $topCategoryRow['total']->percentageOf($expense),
            ];
        }

        $monthsWithData = $months->filter(fn ($m) => ! $m['income']->isZero() || ! $m['expense']->isZero())->count() ?: 1;

        return [
            'income' => $income,
            'expense' => $expense,
            'savings' => $savings,
            'savingsRate' => $savings->percentageOf($income),
            'avgMonthlyIncome' => $income->divide($monthsWithData),
            'avgMonthlyExpense' => $expense->divide($monthsWithData),
            'bestIncomeMonth' => $bestIncomeMonth,
            'worstExpenseMonth' => $worstExpenseMonth,
            'topCategory' => $topCategory,
            'months' => $months,
        ];
    }

    /**
     * @return Collection<int, array{label: string, netWorth: Money}>
     */
    public function netWorthEvolution(User $user, int $months = 12): Collection
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $accounts = $user->accounts()->get(['id', 'initial_balance', 'created_at']);

        $flows = Transaction::query()
            ->where('date', '>=', $start)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym, type, SUM(amount) as total")
            ->groupBy('ym', 'type')
            ->get()
            ->groupBy('ym');

        $cumulativeFlow = Money::zero();

        return collect(range(0, $months - 1))->map(function ($i) use ($start, $accounts, $flows, &$cumulativeFlow) {
            $monthEnd = $start->copy()->addMonths($i)->endOfMonth();
            $ym = $monthEnd->format('Y-m');
            $bucket = $flows->get($ym, collect());

            $income = Money::of($bucket->firstWhere('type', TransactionType::Income->value)->total ?? 0);
            $expense = Money::of($bucket->firstWhere('type', TransactionType::Expense->value)->total ?? 0);
            $cumulativeFlow = $cumulativeFlow->add($income)->subtract($expense);

            $baseline = $accounts
                ->filter(fn ($account) => $account->created_at->lte($monthEnd))
                ->reduce(fn (Money $carry, Account $account) => $carry->add($account->initial_balance), Money::zero());

            return [
                'label' => $monthEnd->translatedFormat('M Y'),
                'netWorth' => $baseline->add($cumulativeFlow),
            ];
        });
    }

    private function periodTotals(User $user, int $year, int $month): array
    {
        $rows = Transaction::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->get();

        $income = $rows->where('type', TransactionType::Income)->reduce(fn (Money $c, Transaction $t) => $c->add($t->amount), Money::zero());
        $expense = $rows->where('type', TransactionType::Expense)->reduce(fn (Money $c, Transaction $t) => $c->add($t->amount), Money::zero());
        $savings = $income->subtract($expense);

        $biggestExpense = $rows->where('type', TransactionType::Expense)->sortByDesc(fn (Transaction $t) => $t->amount->toFloat())->first();

        $topCategoryRow = $rows->where('type', TransactionType::Expense)
            ->groupBy('category_id')
            ->map(fn ($group, $categoryId) => [
                'category_id' => $categoryId,
                'total' => $group->reduce(fn (Money $c, Transaction $t) => $c->add($t->amount), Money::zero()),
            ])
            ->sortByDesc(fn ($row) => $row['total']->toFloat())
            ->first();

        $topCategory = null;
        if ($topCategoryRow) {
            $category = Category::withoutGlobalScopes()->find($topCategoryRow['category_id']);
            $topCategory = [
                'category' => $category,
                'total' => $topCategoryRow['total'],
                'percentage' => $topCategoryRow['total']->percentageOf($expense),
            ];
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'savings' => $savings,
            'savingsRate' => $savings->percentageOf($income),
            'biggestExpense' => $biggestExpense,
            'transactionCount' => $rows->count(),
            'topCategory' => $topCategory,
        ];
    }

    private function trailingDailyExpenseAverage(User $user): Money
    {
        $total = Transaction::query()
            ->where('type', TransactionType::Expense->value)
            ->where('date', '>=', now()->subDays(30))
            ->sum('amount');

        return Money::of($total)->divide(30);
    }

    private function percentChange(Money $previous, Money $current): float
    {
        if ($previous->isZero()) {
            return $current->isZero() ? 0.0 : 100.0;
        }

        return $current->subtract($previous)->divide($previous->abs()->toDecimalString())->toFloat() * 100;
    }
}
