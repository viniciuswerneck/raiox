<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class LocationReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('welcome');
    }

    public function search(Request $request)
    {
        $cep = $request->validate([
            'cep' => 'required|string|regex:/^\d{5}-?\d{3}$/'
        ])['cep'];

        return redirect()->route('report.show', $cep);
    }

    public function show($cep)
    {
        // Simple regex check in show too or just rely on service
        $report = $this->reportService->getReportByCep($cep);

        if (!$report) {
            return redirect()->route('home')->withErrors(['cep' => 'CEP não encontrado ou erro nas APIs de terceiros.']);
        }

        return view('report.show', compact('report'));
    }
}
