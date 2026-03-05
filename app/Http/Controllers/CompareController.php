<?php

namespace App\Http\Controllers;

use App\Models\LocationReport;
use App\Models\RegionComparison;
use App\Services\Agents\CompareAgent;
use App\Services\GeminiService;
use App\Services\NeighborhoodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompareController extends Controller
{
    protected $compareAgent;
    protected $geminiService;
    protected $neighborhoodService;

    public function __construct(
        CompareAgent $compareAgent,
        GeminiService $geminiService,
        NeighborhoodService $neighborhoodService
    ) {
        $this->compareAgent = $compareAgent;
        $this->geminiService = $geminiService;
        $this->neighborhoodService = $neighborhoodService;
    }

    public function show($cepA, $cepB)
    {
        $cepA = preg_replace('/\D/', '', $cepA);
        $cepB = preg_replace('/\D/', '', $cepB);

        // 1. Verificar se um CEP é igual ao outro
        if ($cepA === $cepB) {
            return redirect()->route('report.show', $cepA);
        }

        // 2. Tentar buscar no Cache do Laravel primeiro para velocidade máxima (< 50ms)
        $cacheKey = "compare_{$cepA}_{$cepB}";
        $comparison = Cache::remember($cacheKey, 86400, function() use ($cepA, $cepB) {
            
            // 3. Verificar se os relatórios base existem
            $reportA = LocationReport::where('cep', $cepA)->first();
            $reportB = LocationReport::where('cep', $cepB)->first();

            // Se algum não existir, retornamos nulo para tratar no Controller (disparar pipeline)
            if (!$reportA || !$reportB) return null;

            // --- LAZY UPDATE: Se os relatórios existem mas não têm scores calculados (Legados) ---
            $this->ensureScoresExist($reportA);
            $this->ensureScoresExist($reportB);

            // 4. Verificar se já existe uma comparação salva no Banco de Dados
            $dbComparison = RegionComparison::findPair($cepA, $cepB);
            if ($dbComparison) return $dbComparison;

            // 5. Caso não exista no banco, GERAMOS a comparação AGORA
            Log::info("CompareController: Gerando nova comparação entre {$cepA} e {$cepB}");
            
            $results = $this->compareAgent->compare($reportA, $reportB);
            
            // Gerar análise via Gemini
            $analysis = $this->geminiService->generateComparisonAnalysis(
                $this->mapReportToAnalysis($reportA),
                $this->mapReportToAnalysis($reportB)
            );

            return RegionComparison::create([
                'cep_a' => $cepA,
                'cep_b' => $cepB,
                'score_diff' => $results['deltas']['score_diff'],
                'infra_diff' => $results['deltas']['infra_diff'],
                'mobilidade_diff' => $results['deltas']['mobilidade_diff'],
                'lazer_diff' => $results['deltas']['lazer_diff'],
                'comparison_data' => [
                    'metrics_a' => $results['metrics_a'],
                    'metrics_b' => $results['metrics_b'],
                    'location_a' => "{$reportA->bairro}, {$reportA->cidade}",
                    'location_b' => "{$reportB->bairro}, {$reportB->cidade}"
                ],
                'analysis_text' => $analysis
            ]);
        });

        // 6. Se a comparação retornou nulo, significa que um dos CEPs não está no banco
        if (!$comparison) {
            Log::info("CompareController: Um dos CEPs não existe. Redirecionando para garantir geração.");
            
            // Validamos qual falta e garantimos que o sistema inicie o processamento
            $reportA = LocationReport::where('cep', $cepA)->first();
            if (!$reportA) return redirect()->route('report.show', $cepA);
            
            $reportB = LocationReport::where('cep', $cepB)->first();
            if (!$reportB) return redirect()->route('report.show', $cepB);
        }

        // 7. Retornar View
        $reportA = LocationReport::where('cep', $cepA)->first();
        $reportB = LocationReport::where('cep', $cepB)->first();

        return view('report.compare', [
            'comparison' => $comparison,
            'reportA' => $reportA,
            'reportB' => $reportB
        ]);
    }

    /**
     * Garante que o relatório tenha os scores populados (Lazy Update)
     */
    private function ensureScoresExist(LocationReport $report)
    {
        // Se já tem general_score > 0, assumimos que já foi processado
        // (Ou se não tem POIs, não há o que calcular)
        if ($report->general_score > 0 || empty($report->pois_json)) {
            return;
        }

        Log::info("CompareController: Lazy Update de scores para o CEP {$report->cep}");
        
        $metrics = $this->compareAgent->getRegionMetrics($report->pois_json);
        
        $report->update([
            'infra_score' => $metrics['infra'],
            'mobility_score' => $metrics['mobility'],
            'leisure_score' => $metrics['leisure'],
            'general_score' => $metrics['total_score']
        ]);
    }

    private function mapReportToAnalysis(LocationReport $report): array
    {
        $realEstate = $report->real_estate_json ?? [];
        $precoM2 = $realEstate['preco_m2'] ?? 'Sob Consulta';

        return [
            'cep' => $report->cep,
            'bairro' => $report->bairro,
            'cidade' => $report->cidade,
            'class' => $report->territorial_classification ?? 'Residencial',
            'income' => $report->average_income,
            'infra' => $report->infra_score,
            'mobility' => $report->mobility_score,
            'leisure' => $report->leisure_score,
            'preco_m2' => $precoM2,
            'lat' => $report->lat,
            'lng' => $report->lng
        ];
    }
}
