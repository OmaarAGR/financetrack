<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionImportTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            // BOM para que Excel detecte UTF-8 correctamente.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Fecha', 'Descripción', 'Categoría', 'Cuenta', 'Tipo', 'Notas', 'Monto']);
            fputcsv($handle, ['2026-08-01', 'Salario de agosto', 'Salario', 'Nombre de tu cuenta', 'Ingreso', '', '3500000']);
            fputcsv($handle, ['2026-08-03', 'Mercado del mes', 'Alimentación', 'Nombre de tu cuenta', 'Gasto', 'Compra en el supermercado', '285000']);
            fclose($handle);
        }, 'plantilla-importacion-transacciones.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
