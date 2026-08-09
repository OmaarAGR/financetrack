<?php

namespace App\Listeners;

use App\Enums\TransactionType;
use App\Events\TransactionsMutated;
use App\Notifications\LowAccountBalance;
use App\Services\BalanceCalculator;
use App\Support\Money;
use App\Models\Transaction;

/**
 * Flags an account as "low" when its balance can't cover ~3 days of the
 * user's recent average spending. Notifies once per low-balance episode —
 * skipped while an unread alert for that account is still in the bell.
 */
class CheckLowAccountBalance
{
    public function __construct(private readonly BalanceCalculator $balances) {}

    public function handle(TransactionsMutated $event): void
    {
        $dailyAverage = $this->trailingDailyExpenseAverage($event->user->id);

        if ($dailyAverage->isZero()) {
            return;
        }

        $threshold = $dailyAverage->multiply(3);

        foreach ($event->accounts as $account) {
            if (! $account->is_active) {
                continue;
            }

            $balance = $this->balances->accountBalance($account->fresh());

            if ($balance->greaterThan($threshold) || $balance->isNegative()) {
                continue;
            }

            $alreadyNotified = $event->user->unreadNotifications()
                ->where('type', LowAccountBalance::class)
                ->where('data->account_id', $account->id)
                ->exists();

            if (! $alreadyNotified) {
                $event->user->notify(new LowAccountBalance($account, $balance));
            }
        }
    }

    private function trailingDailyExpenseAverage(int $userId): Money
    {
        $total = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::Expense->value)
            ->where('date', '>=', now()->subDays(30))
            ->sum('amount');

        return Money::of($total)->divide(30);
    }
}
