<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Categorías del sistema (user_id null, visibles para todos los usuarios).
     * El usuario puede crear las suyas propias además de estas.
     */
    public function run(): void
    {
        $expenseCategories = [
            ['name' => 'Alimentación', 'icon' => 'banknotes', 'color' => '#f59e0b'],
            ['name' => 'Transporte', 'icon' => 'arrow-path', 'color' => '#3b82f6'],
            ['name' => 'Vivienda', 'icon' => 'home', 'color' => '#8b5cf6'],
            ['name' => 'Servicios', 'icon' => 'cog', 'color' => '#64748b'],
            ['name' => 'Entretenimiento', 'icon' => 'flag', 'color' => '#ec4899'],
            ['name' => 'Educación', 'icon' => 'document-chart-bar', 'color' => '#06b6d4'],
            ['name' => 'Salud', 'icon' => 'exclamation-triangle', 'color' => '#ef4444'],
            ['name' => 'Compras', 'icon' => 'tag', 'color' => '#f97316'],
            ['name' => 'Tecnología', 'icon' => 'adjustments', 'color' => '#0ea5e9'],
            ['name' => 'Suscripciones', 'icon' => 'arrow-path', 'color' => '#a855f7'],
            ['name' => 'Viajes', 'icon' => 'arrows-right-left', 'color' => '#14b8a6'],
            ['name' => 'Mascotas', 'icon' => 'flag', 'color' => '#84cc16'],
            ['name' => 'Deudas', 'icon' => 'arrow-down-circle', 'color' => '#dc2626'],
            ['name' => 'Impuestos', 'icon' => 'document-chart-bar', 'color' => '#78716c'],
            ['name' => 'Otros', 'icon' => 'tag', 'color' => '#6b7280'],
        ];

        $incomeCategories = [
            ['name' => 'Salario', 'icon' => 'banknotes', 'color' => '#22c55e'],
            ['name' => 'Trabajo independiente', 'icon' => 'wallet', 'color' => '#10b981'],
            ['name' => 'Bonificación', 'icon' => 'arrow-up-circle', 'color' => '#059669'],
            ['name' => 'Regalo', 'icon' => 'flag', 'color' => '#84cc16'],
            ['name' => 'Rendimientos', 'icon' => 'arrow-trending-up', 'color' => '#16a34a'],
            ['name' => 'Venta', 'icon' => 'tag', 'color' => '#0d9488'],
            ['name' => 'Reembolso', 'icon' => 'arrow-path', 'color' => '#0891b2'],
            ['name' => 'Otros', 'icon' => 'tag', 'color' => '#6b7280'],
        ];

        foreach ($expenseCategories as $category) {
            Category::withoutGlobalScopes()->updateOrCreate(
                ['name' => $category['name'], 'type' => CategoryType::Expense->value, 'user_id' => null],
                [...$category, 'type' => CategoryType::Expense->value, 'is_system' => true]
            );
        }

        foreach ($incomeCategories as $category) {
            Category::withoutGlobalScopes()->updateOrCreate(
                ['name' => $category['name'], 'type' => CategoryType::Income->value, 'user_id' => null],
                [...$category, 'type' => CategoryType::Income->value, 'is_system' => true]
            );
        }
    }
}
