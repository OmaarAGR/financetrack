<?php

namespace App\Livewire;

use App\Services\BalanceCalculator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class NetWorthBadge extends Component
{
    #[On('finances-updated')]
    public function refresh(): void
    {
        // El re-render en el próximo ciclo ya vuelve a calcular el patrimonio.
    }

    public function render(BalanceCalculator $calculator): View
    {
        return view('livewire.net-worth-badge', [
            'netWorthByCurrency' => $calculator->netWorthByCurrency(auth()->user()),
        ]);
    }
}
