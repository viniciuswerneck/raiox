<?php

namespace App\Http\Controllers;

use App\Services\NeighborhoodService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $neighborhoodService;

    public function __construct(NeighborhoodService $neighborhoodService)
    {
        $this->neighborhoodService = $neighborhoodService;
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
        $report = $this->neighborhoodService->getCachedReport($cep);

        if (!$report) {
            return redirect()->route('home')->withErrors(['cep' => 'CEP não encontrado ou erro nas APIs de terceiros.']);
        }

        return view('report.show', compact('report'));
    }
}
