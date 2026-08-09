<?php

namespace App\Livewire;

use App\Services\BalanceCalculator;
use App\Services\InsightService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public int $evolutionMonths = 6;

    #[On('finances-updated')]
    public function refresh(): void
    {
        //
    }

    public function setEvolutionMonths(int $months): void
    {
        $this->evolutionMonths = $months;
    }

    public function render(ReportService $reports, BalanceCalculator $balances, InsightService $insights): View
    {
        $user = auth()->user();
        $now = Carbon::now();

        $summary = $reports->monthlySummary($user, $now->year, $now->month);

        return view('livewire.dashboard', [
            'summary' => $summary,
            'highlights' => $insights->monthlyHighlights($summary, $user, $now->year, $now->month),
            'expenseByCategory' => $reports->expenseByCategory($user, $now->year, $now->month),
            'expenseByAccount' => $reports->expenseByAccount($user, $now->year, $now->month),
            'moneyDistribution' => $reports->moneyDistribution($user),
            'monthlyEvolution' => $reports->monthlyEvolution($user, $this->evolutionMonths),
            'netWorthEvolution' => $reports->netWorthEvolution($user, $this->evolutionMonths),
            'hasAnyAccount' => $user->accounts()->exists(),
            'hasAnyTransaction' => $user->transactions()->exists(),
        ]);
    }
}
