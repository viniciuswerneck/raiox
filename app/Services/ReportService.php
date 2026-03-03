<?php

namespace App\Services;

use App\Models\LocationReport;
use Carbon\Carbon;

class ReportService
{
    protected $viaCep;
    protected $ibge;

    public function __construct(ViaCepService $viaCep, IbgeService $ibge)
    {
        $this->viaCep = $viaCep;
        $this->ibge = $ibge;
    }

    /**
     * Get report for a specific CEP
     */
    public function getReportByCep(string $cep): ?LocationReport
    {
        $cepClean = preg_replace('/\D/', '', $cep);
        
        // 1. Check cache (max 30 days old)
        $report = LocationReport::where('cep', $cepClean)->first();

        if ($report && $report->updated_at->gt(Carbon::now()->subDays(30))) {
            return $report;
        }

        // 2. Refresh or Create new cache entry
        //    a. Get from ViaCEP
        $address = $this->viaCep->getAddressByCep($cepClean);
        if (!$address) {
            return null;
        }

        $ibgeCode = $address['ibge'];

        //    b. Get from IBGE
        $ibgeData = $this->ibge->getMunicipalityData($ibgeCode);

        // 3. Update or Create in DB
        $reportData = [
            'cep' => $cepClean,
            'logradouro' => $address['logradouro'] ?? '',
            'bairro' => $address['bairro'] ?? '',
            'cidade' => $address['localidade'],
            'uf' => $address['uf'],
            'codigo_ibge' => $ibgeCode,
            'populacao' => $ibgeData['population'] ?? null,
            'idhm' => $ibgeData['idhm'] ?? null,
            'raw_ibge_data' => $ibgeData['raw_data'],
        ];

        if ($report) {
            $report->update($reportData);
        } else {
            $report = LocationReport::create($reportData);
        }

        return $report;
    }
}
