<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte anual — {{ $year }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .subtitle { color: #6b7280; margin-top: 2px; margin-bottom: 20px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .grid td { width: 25%; padding: 10px; border: 1px solid #e5e7eb; }
        .label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .value { font-size: 15px; font-weight: bold; margin-top: 4px; }
        .green { color: #16a34a; }
        .red { color: #dc2626; }
        h2 { font-size: 14px; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { text-align: left; font-size: 10px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding: 6px 4px; }
        table.list td { padding: 6px 4px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Reporte financiero anual</h1>
    <p class="subtitle">{{ $user->name }} &middot; {{ $year }}</p>

    <table class="grid">
        <tr>
            <td>
                <div class="label">Ingresos totales</div>
                <div class="value green">{{ $summary['income']->format($user->currency_default, $user->locale) }}</div>
            </td>
            <td>
                <div class="label">Gastos totales</div>
                <div class="value red">{{ $summary['expense']->format($user->currency_default, $user->locale) }}</div>
            </td>
            <td>
                <div class="label">Ahorro anual ({{ number_format($summary['savingsRate'], 1) }}%)</div>
                <div class="value">{{ $summary['savings']->format($user->currency_default, $user->locale) }}</div>
            </td>
            <td>
                <div class="label">Promedio mensual de gasto</div>
                <div class="value">{{ $summary['avgMonthlyExpense']->format($user->currency_default, $user->locale) }}</div>
            </td>
        </tr>
    </table>

    @if ($summary['topCategory'])
        <p><strong>Categoría donde más gastaste:</strong> {{ $summary['topCategory']['category']?->name }} ({{ number_format($summary['topCategory']['percentage'], 1) }}% del total)</p>
    @endif
    @if ($summary['bestIncomeMonth'])
        <p><strong>Mes con mayores ingresos:</strong> {{ ucfirst($summary['bestIncomeMonth']['label']) }} ({{ $summary['bestIncomeMonth']['income']->format($user->currency_default, $user->locale) }})</p>
    @endif
    @if ($summary['worstExpenseMonth'])
        <p><strong>Mes con mayores gastos:</strong> {{ ucfirst($summary['worstExpenseMonth']['label']) }} ({{ $summary['worstExpenseMonth']['expense']->format($user->currency_default, $user->locale) }})</p>
    @endif

    <h2>Comparación mes a mes</h2>
    <table class="list">
        <thead>
            <tr><th>Mes</th><th style="text-align:right">Ingresos</th><th style="text-align:right">Gastos</th><th style="text-align:right">Ahorro</th></tr>
        </thead>
        <tbody>
            @foreach ($summary['months'] as $row)
                <tr>
                    <td>{{ ucfirst($row['label']) }}</td>
                    <td style="text-align:right">{{ $row['income']->format($user->currency_default, $user->locale) }}</td>
                    <td style="text-align:right">{{ $row['expense']->format($user->currency_default, $user->locale) }}</td>
                    <td style="text-align:right">{{ $row['savings']->format($user->currency_default, $user->locale) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
