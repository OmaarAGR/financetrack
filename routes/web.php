<?php

use App\Http\Controllers\Reports\AnnualReportPdfController;
use App\Http\Controllers\Reports\CustomReportCsvController;
use App\Http\Controllers\Reports\MonthlyReportPdfController;
use App\Http\Controllers\TransactionImportTemplateController;
use App\Livewire\Actions\Logout;
use App\Livewire\Dashboard;
use App\Livewire\TransactionsIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::post('logout', function (Request $request, Logout $logout) {
    $logout();

    return redirect('/');
})->middleware('auth')->name('logout');

// Módulos pendientes: cada línea se reemplaza por su controlador real en la
// fase del roadmap que le corresponde (ver plan de implementación).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('finanzas/transacciones', TransactionsIndex::class)->defaults('typeFilter', 'all')->name('transactions.index');
    Volt::route('finanzas/transacciones/importar', 'transactions.import')->name('transactions.import');
    Route::get('finanzas/transacciones/importar/plantilla', TransactionImportTemplateController::class)->name('transactions.import.template');
    Route::get('finanzas/ingresos', TransactionsIndex::class)->defaults('typeFilter', 'income')->name('incomes.index');
    Route::get('finanzas/gastos', TransactionsIndex::class)->defaults('typeFilter', 'expense')->name('expenses.index');
    Route::get('finanzas/transferencias', TransactionsIndex::class)->defaults('typeFilter', 'transfer')->name('transfers.index');

    Volt::route('cuentas', 'accounts.index')->name('accounts.index');

    Volt::route('planificacion/presupuestos', 'budgets.index')->name('budgets.index');
    Volt::route('planificacion/metas', 'savings-goals.index')->name('savings-goals.index');
    Volt::route('planificacion/recurrentes', 'recurring-transactions.index')->name('recurring-transactions.index');

    Volt::route('reportes/mensual', 'reports.monthly')->name('reports.monthly');
    Route::get('reportes/mensual/pdf', MonthlyReportPdfController::class)->name('reports.monthly.pdf');

    Volt::route('reportes/anual', 'reports.annual')->name('reports.annual');
    Route::get('reportes/anual/pdf', AnnualReportPdfController::class)->name('reports.annual.pdf');

    Volt::route('reportes/personalizado', 'reports.custom')->name('reports.custom');
    Route::get('reportes/personalizado/csv', CustomReportCsvController::class)->name('reports.custom.csv');

    Volt::route('configuracion/categorias', 'categories.index')->name('categories.index');
    Volt::route('configuracion/preferencias', 'preferences.edit')->name('preferences.edit');
    Route::view('configuracion/exportar', 'coming-soon', ['title' => 'Exportar mis datos'])->name('data-export.index');
});

require __DIR__.'/auth.php';
