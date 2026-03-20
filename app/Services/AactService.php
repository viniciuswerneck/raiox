<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AactService
{
    protected RealEstateTrendService $trendService;

    public function __construct(RealEstateTrendService $trendService)
    {
        $this->trendService = $trendService;
    }

    /**
     * Valida a coerência interna e distorções dos dados do bairro.
     * Retorna os dados atualizados com uma chave 'audit_log' e 'territorial_classification'.
     */
    public function auditAndRecalibrate(array $data): array
    {
        $log = [];
        $pois = $data['pois_json'] ?? [];
        $income = $data['average_income'] ?? 1500;
        $sanitation = $data['sanitation_rate'] ?? 50;
        $bairro = strtolower($data['bairro'] ?? '');
        $cidade = strtolower($data['localidade'] ?? '');
        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;

        $log[] = "Iniciada a auditoria territorial AACT para {$data['cep']}.";

        // 1. Identificação de Padrões Pelo POIs
        $poiCounts = [
            'popular' => 0, // mercadinhos, oficinas, padarias simples
            'central' => 0, // shoppings, lajes, bancos, hospitais
            'turistico' => 0, // hoteis, atracoes, monumentos
            'lazer_alto' => 0, // galerias de arte, rest. finos
        ];

        foreach ($pois as $poi) {
            $amenity = $poi['tags']['amenity'] ?? '';
            $shop = $poi['tags']['shop'] ?? '';
            $tourism = $poi['tags']['tourism'] ?? '';

            if (in_array($shop, ['supermarket', 'convenience', 'doityourself', 'car_repair', 'butcher'])) {
                $poiCounts['popular']++;
            }
            if (in_array($amenity, ['bank', 'hospital', 'university']) || $shop == 'mall') {
                $poiCounts['central']++;
            }
            if (! empty($tourism) || in_array($amenity, ['bar', 'nightclub'])) {
                $poiCounts['turistico']++;
            }
            if (in_array($amenity, ['arts_centre', 'theatre', 'ice_cream'])) {
                $poiCounts['lazer_alto']++;
            }
        }

        // 2. Classificação Territorial
        $classification = 'Residencial Médio';
        $isCentro = (str_contains($bairro, 'centro') || str_contains($bairro, 'central'));
        $isPeriferia = (! $isCentro && $income < 2000 && $poiCounts['central'] < 2);

        if ($poiCounts['turistico'] >= 5) {
            $classification = 'Turístico Premium';
            $log[] = 'Classificado como Turístico Premium devido à alta concentração de turismo/lazer.';
        } elseif ($isCentro || $poiCounts['central'] >= 5) {
            $classification = 'Comercial Central';
            $log[] = 'Classificado como Comercial Central baseado na concentração de bancos/shoppings/serviços.';
        } elseif ($income > 5000 || $poiCounts['lazer_alto'] > 3) {
            $classification = 'Residencial Alto Padrão';
        } elseif ($isPeriferia) {
            $classification = 'Residencial Popular';
            $log[] = 'Classificado como Residencial Popular devido à renda projetada e tipo de comércio.';
        } else {
            // Pode ser expansão se tiver muito poucos pontos
            if (count($pois) < 5) {
                $classification = 'Zona de Expansão / Rural';
            }
        }

        $data['territorial_classification'] = $classification;

        // 3. Checagem de Mercado Imobiliário vs Renda
        // Mercado não pode ultrapassar ~3 a 4x a renda média mensal daquele polígono
        $market = $data['real_estate_json'] ?? null;
        if ($market && isset($market['preco_m2'])) {
            // Extrai numeros do preço m2
            preg_match_all('/\d+\.?\d*/', str_replace(['R$', ' ', '.'], ['', '', ''], $market['preco_m2']), $matches);

            if (! empty($matches[0])) {
                $values = array_map('intval', $matches[0]);
                $avgPrice = array_sum($values) / count($values);

                // Se o preço for absurdamente alto em relação à renda para áreas não nobres
                $maxAcceptablePrice = $income * 4;

                if ($avgPrice > $maxAcceptablePrice && ! in_array($classification, ['Turístico Premium', 'Residencial Alto Padrão'])) {
                    $log[] = "INCONSISTÊNCIA DETECTADA: Preço imobiliário (R\$ {$avgPrice}) superou o teto viável da Renda (R\$ {$income}). Recalibrando.";
                    // Recalcula faixa
                    $newMin = max(1000, round(($income * 1.5) / 100) * 100);
                    $newMax = round(($income * 3) / 100) * 100;

                    $data['real_estate_json']['preco_m2'] = 'R$ '.number_format($newMin, 0, ',', '.').' a R$ '.number_format($newMax, 0, ',', '.');
                    $data['real_estate_json']['perfil_imoveis'] .= ' (Perfil recalibrado pela inteligência territorial)';
                }
            }
        }

        // [NEW] 3b. Auditoria de Adjacência (Efeito Maré)
        if ($lat && $lng) {
            // Criamos um objeto fake só para rodar a análise
            $mockReport = new \App\Models\LocationReport([
                'lat' => $lat,
                'lng' => $lng,
                'territorial_classification' => $classification,
                'real_estate_json' => $data['real_estate_json'],
                'bairro' => $data['bairro'],
            ]);

            $trend = $this->trendService->analyzePotential($mockReport);

            if ($trend['is_strategic'] && ! str_contains(strtoupper($data['real_estate_json']['tendencia_valorizacao'] ?? ''), 'ALTA')) {
                $hubName = ! empty($trend['nearby_hubs']) ? $trend['nearby_hubs'][0]['name'] : 'principais polos vizinhos';
                $log[] = "AJUSTE ESTRATÉGICO: Potencial de Catch-up detectado devido à proximidade com {$hubName}. Forçando tendência de ALTA.";
                $data['real_estate_json']['tendencia_valorizacao'] = 'ALTA: '.$trend['description'];
            }
        }

        // 4. Checagem de Infraestrutura (Saneamento)
        if ($classification === 'Comercial Central' || $classification === 'Turístico Premium') {
            if ($sanitation < 85) {
                $log[] = "INCONSISTÊNCIA DETECTADA: Infraestrutura sanitária incompatível com '{$classification}'. Ajustando Baseline para 90%.";
                $data['sanitation_rate'] = max($sanitation, 90.0);
            }
        } elseif ($classification === 'Residencial Popular' || $classification === 'Zona de Expansão / Rural') {
            // Na periferia não podemos assumir cegamente o valor do município. Se a renda é muito baixa, podemos reduzir a distorção.
            // Para efeitos de auditoria local, puxamos o índice um pouco pra baixo com base em peso.
            $weight = ($income / 2500); // Fator de redução
            if ($weight < 1) {
                $newSanitation = round($sanitation * ((0.5) + ($weight / 2)), 1);
                if ($newSanitation < $sanitation) {
                    $log[] = 'INCONSISTÊNCIA DETECTADA: Média municipal de saneamento ajustada ponderando a precariedade infraestrutural da região.';
                    $data['sanitation_rate'] = max(15.0, $newSanitation);
                }
            }
        }

        $data['aact_log'] = $log;

        Log::info("AACT Audit for {$data['cep']} concluid com ".count($log).' verificações.');

        return $data;
    }
}
