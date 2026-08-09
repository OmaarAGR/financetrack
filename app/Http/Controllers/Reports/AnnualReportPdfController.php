<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AnnualReportPdfController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        $year = (int) $request->integer('year', now()->year);
        $user = $request->user();

        $pdf = Pdf::loadView('pdf.annual-report', [
            'user' => $user,
            'year' => $year,
            'summary' => $reports->annualSummary($user, $year),
        ]);

        return $pdf->stream("reporte-anual-{$year}.pdf");
    }
}
