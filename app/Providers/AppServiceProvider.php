<?php

namespace App\Providers;

use App\Events\TransactionsMutated;
use App\Listeners\CheckBudgetThreshold;
use App\Listeners\InvalidateBalanceCache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(TransactionsMutated::class, InvalidateBalanceCache::class);
        Event::listen(TransactionsMutated::class, CheckBudgetThreshold::class);
    }
}
