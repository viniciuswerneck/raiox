<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WikiAgent v1.0.0
 * Especialista em extração de contexto histórico, cultural e imagens da Wikipedia.
 * Isolado para garantir que falhas na API da Wikipedia não afetem o processamento de Geo/Socio.
 */
class WikiAgent extends BaseAgent
{
    public const VERSION = '1.0.0';

    private const AMBIGUOUS_TERMS = [
        'centro', 'norte', 'sul', 'leste', 'oeste', 'central',
        'jardim', 'vila', 'parque', 'alto', 'baixo', 'bela vista', 'boa vista',
        'santa', 'santo', 'são', 'nossa senhora', 'residencial', 'portal',
    ];

    /**
     * Retorna os closures para o Master Pool.
     */
    public function getPoolRequests(\Illuminate\Http\Client\Pool $pool, string $bairro, string $city, string $state): array
    {
        $this->logInfo("Preparando requisições de Pool para {$bairro}/{$city}");

        $headers = ['User-Agent' => 'RaioXNeighborhood/'.self::VERSION];
        $base = 'https://pt.wikipedia.org/api/rest_v1/page/summary/';

        $stateFullName = $this->getStateFullName($state);
        $candidates = $this->generateCandidates($bairro, $city, $stateFullName);

        $requests = [];
        foreach ($candidates as $index => [$term, $source, $shouldValidate]) {
            $key = "wiki_{$index}_{$source}";
            $requests[$key] = $pool->as($key)->withoutVerifying()->timeout(10)->withHeaders($headers)
                ->get($base.str_replace('%2F', '/', urlencode($term)));
        }

        return $requests;
    }

    /**
     * Processa os resultados do Pool (Wikipedia)
     */
    public function processResultsFromPool(array $responses, string $bairro, string $city, string $state): array
    {
        $stateFullName = $this->getStateFullName($state);
        $candidates = $this->generateCandidates($bairro, $city, $stateFullName);
        $headers = ['User-Agent' => 'RaioXNeighborhood/'.self::VERSION];

        $bestResult = null;

        foreach ($candidates as $index => [$term, $source, $shouldValidate]) {
            try {
                $key = "wiki_{$index}_{$source}";
                $response = $responses[$key] ?? null;

                if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                    $data = $response->json();

                    $vCity = $shouldValidate ? $city : '';
                    $vState = $shouldValidate ? ($source === 'cidade' ? '' : $stateFullName) : '';

                    if (! $this->isValidPlace($data, $vCity, $vState)) {
                        continue;
                    }

                    // Imagem e FullText ainda são síncronos (precisam do título exato resolvido)
                    // mas rodam apenas se encontrarmos um candidato válido no pool
                    $officialImage = $this->fetchImageViaAPI($term, $headers);
                    $imageUrl = $officialImage ?: ($data['originalimage']['source'] ?? $data['thumbnail']['source'] ?? null);
                    $fullText = $this->fetchFullContent($term, $headers);

                    $currentResult = [
                        'source' => $source,
                        'term' => $term,
                        'extract' => $data['extract'],
                        'full_text' => $fullText ?: $data['extract'],
                        'image' => $imageUrl,
                        'desktop_url' => $data['content_urls']['desktop']['page'] ?? null,
                        'agent_version' => self::VERSION,
                    ];

                    if (! $bestResult) {
                        $bestResult = $currentResult;
                    }

                    if ($bestResult && ! $bestResult['image'] && $currentResult['image']) {
                        $bestResult['image'] = $currentResult['image'];
                    }

                    if ($bestResult && $bestResult['image']) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("WikiAgent Pool: Erro ao processar [{$term}]: ".$e->getMessage());
            }
        }

        return $bestResult ?: [];
    }

    /**
     * Busca informações da Wikipedia com lógica de fallback (Bairro -> Cidade -> Estado)
     */
    public function fetchInfo(string $bairro, string $city, string $state): array
    {
        Log::info('WikiAgent v'.self::VERSION.": Iniciando busca para {$bairro}/{$city}");

        $headers = ['User-Agent' => 'RaioXNeighborhood/'.self::VERSION];
        $base = 'https://pt.wikipedia.org/api/rest_v1/page/summary/';

        $stateFullName = $this->getStateFullName($state);
        $candidates = $this->generateCandidates($bairro, $city, $stateFullName);

        $bestResult = null;

        foreach ($candidates as [$term, $source, $shouldValidate]) {
            try {
                $url = $base.str_replace('%2F', '/', urlencode($term));
                /** @var \Illuminate\Http\Client\Response $response */
                $response = Http::withoutVerifying()->timeout(10)->withHeaders($headers)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    $vCity = $shouldValidate ? $city : '';
                    $vState = $shouldValidate ? ($source === 'cidade' ? '' : $stateFullName) : '';

                    if (! $this->isValidPlace($data, $vCity, $vState)) {
                        continue;
                    }

                    $officialImage = $this->fetchImageViaAPI($term, $headers);
                    $imageUrl = $officialImage ?: ($data['originalimage']['source'] ?? $data['thumbnail']['source'] ?? null);
                    $fullText = $this->fetchFullContent($term, $headers);

                    $currentResult = [
                        'source' => $source,
                        'term' => $term,
                        'extract' => $data['extract'],
                        'full_text' => $fullText ?: $data['extract'],
                        'image' => $imageUrl,
                        'desktop_url' => $data['content_urls']['desktop']['page'] ?? null,
                        'agent_version' => self::VERSION,
                    ];

                    if (! $bestResult) {
                        $bestResult = $currentResult;
                    }

                    if ($bestResult && ! $bestResult['image'] && $currentResult['image']) {
                        $bestResult['image'] = $currentResult['image'];
                    }

                    if ($bestResult && $bestResult['image']) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("WikiAgent: Erro ao buscar [{$term}]: ".$e->getMessage());
            }
        }

        return $bestResult ?: [];
    }

    private function isValidPlace(array $data, string $expectedCity = '', string $expectedState = ''): bool
    {
        $type = $data['type'] ?? '';
        $description = strtolower($data['description'] ?? '');
        $extract = $data['extract'] ?? '';

        if ($type === 'disambiguation') {
            return false;
        }
        if (empty($extract)) {
            return false;
        }

        $placeKeywords = ['município', 'cidade', 'bairro', 'distrito', 'região', 'localidade', 'capital', 'entidade', 'unidade federativa', 'povoado'];
        $isPlace = false;
        foreach ($placeKeywords as $kw) {
            if (str_contains($description, $kw)) {
                $isPlace = true;
                break;
            }
        }

        if ($expectedCity || $expectedState) {
            $textToSearch = strtolower($description.' '.$extract);
            if (! str_contains($textToSearch, strtolower($expectedCity)) && ! str_contains($textToSearch, strtolower($expectedState))) {
                return false;
            }
        }

        return $isPlace || ! empty($description);
    }

    private function generateCandidates(string $bairro, string $city, string $stateFullName): array
    {
        $candidates = [];
        if ($bairro) {
            $candidates[] = [str_replace(' ', '_', "{$bairro} ({$city})"), 'bairro', true];

            $bairroIsAmbiguous = false;
            foreach (self::AMBIGUOUS_TERMS as $term) {
                if (str_contains(strtolower($bairro), $term)) {
                    $bairroIsAmbiguous = true;
                    break;
                }
            }
            if (! $bairroIsAmbiguous) {
                $candidates[] = [str_replace(' ', '_', $bairro), 'bairro', true];
            }
        }

        $candidates[] = [str_replace(' ', '_', "{$city} ({$stateFullName})"), 'cidade', false];
        $candidates[] = [str_replace(' ', '_', $city), 'cidade', true];

        return $candidates;
    }

    private function fetchImageViaAPI(string $term, array $headers): ?string
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withoutVerifying()->timeout(10)->withHeaders($headers)
                ->get('https://pt.wikipedia.org/w/api.php', [
                    'action' => 'query', 'prop' => 'pageimages', 'format' => 'json',
                    'piprop' => 'thumbnail', 'pithumbsize' => 960, 'titles' => str_replace('_', ' ', $term),
                ]);

            if ($response->successful()) {
                $pages = $response->json()['query']['pages'] ?? [];
                $page = reset($pages);

                return $page['thumbnail']['source'] ?? null;
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    private function fetchFullContent(string $term, array $headers): ?string
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withoutVerifying()->timeout(15)->withHeaders($headers)
                ->get('https://pt.wikipedia.org/w/api.php', [
                    'action' => 'query', 'prop' => 'extracts', 'exlimit' => 1,
                    'titles' => str_replace('_', ' ', $term), 'explaintext' => 1, 'format' => 'json',
                ]);

            if ($response->successful()) {
                $pages = $response->json()['query']['pages'] ?? [];
                $page = reset($pages);
                $raw = $page['extract'] ?? '';
                $raw = preg_replace('/\[\d+\]/', '', $raw);

                return trim(mb_substr(preg_replace('/\s+/', ' ', trim($raw)), 0, 15000)) ?: null;
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    private function getStateFullName(string $uf): string
    {
        $states = [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
            'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
            'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
            'SE' => 'Sergipe', 'TO' => 'Tocantins',
        ];

        return $states[strtoupper($uf)] ?? $uf;
    }
}
