<?php

namespace App\Services;

use App\Models\LocationReport;
use Illuminate\Support\Facades\Log;

class RealEstateTrendService
{
    /**
     * Analisa o potencial de valorização baseado na vizinhança.
     * Algoritmo de Waves (Efeito Maré - Gentrificação e Expansão)
     */
    public function analyzePotential(LocationReport $report): array
    {
        $lat = $report->lat;
        $lng = $report->lng;
        
        // 1. Buscar vizinhos em um raio de ~5km (Aprox. 0.045 graus)
        $neighbors = LocationReport::where('status', 'completed')
            ->where('id', '!=', $report->id)
            ->whereBetween('lat', [$lat - 0.045, $lat + 0.045])
            ->whereBetween('lng', [$lng - 0.045, $lng + 0.045])
            ->get();

        if ($neighbors->isEmpty()) {
            return [
                'trend' => 'ESTÁVEL',
                'description' => 'A região mantém um ritmo de valorização estável, acompanhando a média municipal por ser uma área em consolidação isolada.',
                'nearby_hubs' => [],
                'neighbor_avg_price' => 0,
                'appreciation_score' => 50,
                'is_strategic' => false
            ];
        }

        $highValueHubs = [];
        $hasPremiumNeighbor = false;
        $avgNeighborPrice = 0;
        $priceCount = 0;
        $neighborDistortions = [];

        foreach ($neighbors as $n) {
            $classification = $n->territorial_classification;
            $market = $n->real_estate_json;
            
            // Se tem um vizinho Premium ou Comercial Central perto (< 3.5km)
            $dist = $this->calculateDistance($lat, $lng, $n->lat, $n->lng);
            
            if (in_array($classification, ['Turístico Premium', 'Residencial Alto Padrão', 'Comercial Central'])) {
                if ($dist < 3.5) {
                    $hasPremiumNeighbor = true;
                    $highValueHubs[] = [
                        'name' => $n->bairro ?: $n->logradouro,
                        'dist' => round($dist, 1),
                        'classification' => $classification
                    ];
                }
            }

            // Extrair preço m2 médio do vizinho
            if (isset($market['preco_m2'])) {
                 $price = $this->extractPrice($market['preco_m2']);
                 if ($price > 0) {
                     $avgNeighborPrice += $price;
                     $priceCount++;
                     $neighborDistortions[] = $price;
                 }
            }
        }

        $avgNeighborPrice = $priceCount > 0 ? $avgNeighborPrice / $priceCount : 0;
        $currentPrice = $this->extractPrice($report->real_estate_json['preco_m2'] ?? '');

        // Lógica de Predição de "Onda"
        $description = 'Tendência de valorização orgânica baseada na infraestrutura local.';
        $score = 50;
        $trend = 'ESTÁVEL';

        // 1. Efeito Catch-up (Preço atual baixo perto de áreas caras)
        if ($hasPremiumNeighbor && $currentPrice > 0 && $currentPrice < ($avgNeighborPrice * 0.85)) {
            $trend = 'ALTA EXPOENTE';
            $score = 85;
            $hubName = $highValueHubs[0]['name'] ?? 'centros vizinhos';
            $description = "Forte potencial de 'Catch-up'. A proximidade com o polo de alto valor ({$hubName}) está pressionando a valorização desta região, tornando-a um alvo estratégico para investimento.";
        } 
        // 2. Consolidação por Proximidade
        elseif ($hasPremiumNeighbor) {
            $trend = 'ALTA';
            $score = 75;
            $description = "Valorização sustentada pela saturação de áreas nobres vizinhas. O bairro absorve a demanda excedente de regiões de alto padrão.";
        }
        // 3. Diferencial Local
        elseif ($currentPrice > ($avgNeighborPrice * 1.2) && $avgNeighborPrice > 0) {
            $trend = 'ESTÁVEL (ACIMA DA MÉDIA)';
            $score = 65;
            $description = "O bairro já atingiu um patamar de valorização superior à sua vizinhança imediata, configurando-se como uma 'ilha' de maior valor local.";
        }
        // 4. Sincronia Regional
        elseif ($priceCount > 10) {
            $trend = 'ESTÁVEL';
            $score = 55;
            $description = "O mercado local está em perfeita sincronia com a dinâmica imobiliária da microrregião.";
        }

        return [
            'trend' => $trend,
            'description' => $description,
            'nearby_hubs' => array_slice($highValueHubs, 0, 3),
            'neighbor_avg_price' => round($avgNeighborPrice, 0),
            'appreciation_score' => $score,
            'is_strategic' => ($score >= 75)
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $km = $dist * 60 * 1.1515 * 1.609344;
        return $km;
    }

    private function extractPrice(string $priceStr): int {
        // Remove R$, pontos e espaços, pega os numeros
        $clean = str_replace(['R$', ' ', '.', ','], ['', '', '', '.'], $priceStr);
        preg_match_all('/\d+\.?\d*/', $clean, $matches);
        if (empty($matches[0])) return 0;
        
        $values = array_map('floatval', $matches[0]);
        // Se pegou uma faixa (ex: 5000 a 7000), tira a média
        return count($values) > 0 ? (int)(array_sum($values) / count($values)) : 0;
    }
}
