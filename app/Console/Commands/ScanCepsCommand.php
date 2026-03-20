<?php

namespace App\Console\Commands;

use App\Models\CepScanLog;
use App\Models\CepScanSession;
use App\Models\LocationReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScanCepsCommand extends Command
{
    protected $signature = 'cep:scan 
                            {--limit=100 : Número de CEPs por execução}
                            {--delay=1000 : Delay entre requisições (ms)}
                            {--state= : Filtrar por estado (ex: SP, RJ, MG)}
                            {--city= : Filtrar por cidade específica}
                            {--dry-run : Apenas simular sem salvar}
                            {--reset : Resetar cache de progresso}';

    protected $description = 'Escaneia CEPs do Brasil usando Nominatim para descobrir CEPs reais';

    private array $majorCities = [
        'SP' => ['São Paulo', 'Campinas', 'Santos', 'São José dos Campos', 'Ribeirão Preto', 'Sorocaba', 'Santo André', 'São Bernardo do Campo', 'Osasco', 'Piracicaba'],
        'RJ' => ['Rio de Janeiro', 'Niterói', 'São Gonçalo', 'Duque de Caxias', 'Nova Iguaçu', 'Cabo Frio', 'Petrópolis', 'Volta Redonda', 'Campos dos Goytacazes'],
        'MG' => ['Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim', 'Montes Claros', 'Curvelo'],
        'BA' => ['Salvador', 'Feira de Santana', 'Vitória da Conquista', 'Camaçari', 'Itabuna', 'Juazeiro'],
        'RS' => ['Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Canoas', 'Santa Maria', 'Gravataí'],
        'PR' => ['Curitiba', 'Londrina', 'Maringá', 'Ponta Grossa', 'Cascavel', 'São José dos Pinhais'],
        'PE' => ['Recife', 'Olinda', 'Jaboatão dos Guararapes', 'Caruaru', 'Petrolina'],
        'CE' => ['Fortaleza', 'Caucaia', 'Juazeiro do Norte', 'Maracanaú', 'Sobral'],
        'GO' => ['Goiânia', 'Aparecida de Goiânia', 'Anápolis', 'Rio Verde', 'Luziânia'],
        'DF' => ['Brasília', 'Taguatinga', 'Ceilândia', 'Samambaia', 'Planaltina'],
        'ES' => ['Vitória', 'Serra', 'Vila Velha', 'Cariacica', 'Cachoeiro de Itapemirim', 'Linhares', 'São José do Calçado'],
        'SC' => ['Florianópolis', 'Joinville', 'Blumenau', 'Chapecó', 'Criciúma', 'Itajaí', 'Palhoça'],
        'MT' => ['Cuiabá', 'Várzea Grande', 'Rondonópolis', 'Sinop', 'Tangará da Serra', 'Cáceres'],
        'MS' => ['Campo Grande', 'Dourados', 'Três Lagoas', 'Corumbá', 'Aquidauana'],
        'PA' => ['Belém', 'Ananindeua', 'Santarém', 'Marabá', 'Parauapebas', 'Castanhal'],
        'AM' => ['Manaus', 'Itacoatiara', 'Manacapuru', 'Coari', 'Tefé'],
        'PB' => ['João Pessoa', 'Campina Grande', 'Santa Rita', 'Patos', 'Bayeux'],
        'AL' => ['Maceió', 'Arapiraca', 'Rio Largo', 'Palmeira dos Índios', 'Maceió'],
        'PI' => ['Teresina', 'Parnaíba', 'Picos', 'Floriano', 'Altos'],
        'MA' => ['São Luís', 'Imperatriz', 'Timon', 'Caxias', 'Codó'],
        'RO' => ['Porto Velho', 'Ji-Paraná', 'Ariquemes', 'Cacoal', 'Vilhena'],
        'SE' => ['Aracaju', ' Nossa Senhora do Socorro', 'Lagarto', 'Itabaiana', 'Estância'],
        'TO' => ['Palmas', 'Araguaína', 'Gurupi', 'Porto Nacional', 'Miracema'],
        'AP' => ['Macapá', 'Santana', 'Laranjal do Jari', 'Oiapoque'],
        'AC' => ['Rio Branco', 'Cruzeiro do Sul', 'Sena Madureira', 'Tarauacá'],
        'RR' => ['Boa Vista', 'Rorainópolis', 'Cantá', 'Mucajaí'],
    ];

    private array $neighborhoodTypes = [
        'Centro', 'Jardim', 'Vila', 'Parque', 'Residencial', 'Alphaville',
        'Zona Sul', 'Zona Norte', 'Zona Leste', 'Zona Oeste',
    ];

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $state = $this->option('state');
        $city = $this->option('city');
        $dryRun = $this->option('dry-run');
        $reset = $this->option('reset');

        $this->info('══════════════════════════════════════════');
        $this->info('🚀 ROBÔ DE SCAN DE CEPs');
        $this->info('══════════════════════════════════════════');
        $this->info("📊 Limite: {$limit} | Delay: {$delay}ms");

        if ($reset) {
            $this->resetProgress();
        }

        if ($state) {
            $this->info("📍 Estado: ".strtoupper($state));
        }

        if ($city) {
            $this->info("🏙️ Cidade: {$city}");
        }

        if ($dryRun) {
            $this->warn('⚠️ MODO SIMULAÇÃO (dry-run)');
        }

        $session = CepScanSession::create([
            'state' => $state ? strtoupper($state) : null,
            'status' => 'running',
            'limit_planned' => $limit,
            'delay_ms' => $delay,
            'started_at' => now(),
        ]);

        try {
            $existingCeps = LocationReport::pluck('cep')
                ->map(fn ($c) => preg_replace('/\D/', '', $c))
                ->flip()
                ->toArray();

            $this->info('📦 CEPs já existentes: '.count($existingCeps));

            $queries = $this->buildQueries($state, $city);

            $this->info("📋 {$limit} CEPs para buscar\n");

            $progressBar = $this->output->createProgressBar($limit);
            $progressBar->start();

            $processed = 0;
            $queryIndex = 0;

            while ($processed < $limit && $queryIndex < count($queries)) {
                $query = $queries[$queryIndex];
                $queryKey = $this->getCacheKey($query);
                $queryIndex++;

                if ($this->isQueryProcessed($queryKey)) {
                    continue;
                }

                $cepsFound = $this->searchCepsFromNominatim($query);

                if (empty($cepsFound)) {
                    $this->markQueryProcessed($queryKey);
                    continue;
                }

                foreach ($cepsFound as $cep) {
                    if ($processed >= $limit) {
                        break;
                    }

                    if (isset($existingCeps[$cep])) {
                        continue;
                    }

                    if ($this->isCepProcessed($cep)) {
                        continue;
                    }

                    $this->processCep($cep, $existingCeps, $dryRun, $session, $delay);
                    $this->markCepProcessed($cep);
                    $progressBar->advance();
                    $processed++;
                }

                $this->markQueryProcessed($queryKey);
            }

            $progressBar->finish();
            $this->newLine(2);

            $session->update(['status' => 'completed', 'finished_at' => now()]);

            $this->showSummary();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erro: '.$e->getMessage());
            Log::error('Scan CEP error: '.$e->getMessage());

            $session->update([
                'status' => 'failed',
                'finished_at' => now(),
                'notes' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    private function resetProgress(): void
    {
        $keys = ['scan_progress_queries', 'scan_progress_ceps'];
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        $this->info('🧹 Cache de progresso resetado!');
    }

    private function getCacheKey(string $query): string
    {
        return md5($query);
    }

    private function isQueryProcessed(string $key): bool
    {
        $processed = Cache::get('scan_progress_queries', []);

        return in_array($key, $processed);
    }

    private function markQueryProcessed(string $key): void
    {
        $processed = Cache::get('scan_progress_queries', []);
        $processed[] = $key;
        Cache::put('scan_progress_queries', $processed, now()->addDays(30));
    }

    private function isCepProcessed(string $cep): bool
    {
        $processed = Cache::get('scan_progress_ceps', []);

        return in_array($cep, $processed);
    }

    private function markCepProcessed(string $cep): void
    {
        $processed = Cache::get('scan_progress_ceps', []);
        $processed[] = $cep;
        Cache::put('scan_progress_ceps', $processed, now()->addDays(30));
    }

    private function buildQueries(?string $state, ?string $city): array
    {
        $queries = [];

        if ($city) {
            $stateToUse = $state ?? $this->guessState($city);
            foreach ($this->neighborhoodTypes as $type) {
                $queries[] = "{$type}, {$city}, {$stateToUse}";
            }

            return $queries;
        }

        if ($state) {
            $stateUpper = strtoupper($state);
            $cities = $this->majorCities[$stateUpper] ?? [];
        } else {
            $cities = [];
            foreach ($this->majorCities as $stateCities) {
                $cities = array_merge($cities, $stateCities);
            }
        }

        foreach ($cities as $cityName) {
            $stateForCity = $this->getStateForCity($cityName);
            foreach ($this->neighborhoodTypes as $type) {
                $queries[] = "{$type}, {$cityName}, {$stateForCity}";
            }
        }

        shuffle($queries);

        return $queries;
    }

    private function guessState(string $city): string
    {
        return $this->getStateForCity($city) ?? 'SP';
    }

    private function getStateForCity(string $city): ?string
    {
        foreach ($this->majorCities as $state => $cities) {
            if (in_array($city, $cities)) {
                return $state;
            }
        }

        return null;
    }

    private function searchCepsFromNominatim(string $query): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 RaioX/1.0',
            'Referer' => 'https://raiox.app/',
        ];

        try {
            $this->info("\n  🔍 Buscando: {$query}");

            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 20,
                    'countrycodes' => 'br',
                ]);

            if (! $response->successful()) {
                return [];
            }

            $ceps = [];
            foreach ($response->json() as $result) {
                $postcode = $result['address']['postcode'] ?? null;
                if ($postcode) {
                    $cep = preg_replace('/\D/', '', $postcode);
                    if (strlen($cep) === 8 && ! in_array($cep, $ceps)) {
                        $ceps[] = $cep;
                    }
                }
            }

            if (! empty($ceps)) {
                $this->info("     ✅ Encontrados: ".implode(', ', $ceps));
            }

            return $ceps;
        } catch (\Exception $e) {
            Log::warning("Nominatim search failed for [{$query}]: ".$e->getMessage());

            return [];
        }
    }

    private int $successCount = 0;

    private int $notFoundCount = 0;

    private int $errorCount = 0;

    private function processCep(
        string $cep,
        array $existingCeps,
        bool $dryRun,
        ?CepScanSession $session,
        int $delay
    ): void {
        if ($dryRun) {
            $this->info("     📝 [DRY] {$cep}");
            $this->successCount++;

            return;
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(8)
                ->get("https://viacep.com.br/ws/{$cep}/json/");

            if ($response->successful() && ! isset($response['erro'])) {
                $this->saveReport($cep, $response->json(), $session);
                $this->successCount++;
            } else {
                $this->notFoundCount++;
            }
        } catch (\Exception $e) {
            $this->logFailed($cep, $e->getMessage(), $session);
            $this->errorCount++;
        }

        usleep($delay * 1000);
    }

    private function saveReport(string $cep, array $data, ?CepScanSession $session): void
    {
        LocationReport::updateOrCreate(
            ['cep' => $cep],
            [
                'logradouro' => $data['logradouro'] ?? null,
                'bairro' => $data['bairro'] ?? null,
                'cidade' => $data['localidade'] ?? null,
                'uf' => $data['uf'] ?? null,
                'codigo_ibge' => $data['ibge'] ?? null,
                'lat' => null,
                'lng' => null,
                'status' => 'pending',
                'data_version' => 1,
            ]
        );

        CepScanLog::create([
            'cep' => $cep,
            'status' => 'success',
            'logradouro' => $data['logradouro'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['localidade'] ?? null,
            'uf' => $data['uf'] ?? null,
            'codigo_ibge' => $data['ibge'] ?? null,
            'lat' => null,
            'lng' => null,
            'source' => 'nominatim+viacep',
            'state_target' => $session?->state,
            'response_time_ms' => 0,
        ]);

        if ($session) {
            $session->increment('success');
            $session->increment('processed');
        }
    }

    private function logFailed(string $cep, string $error, ?CepScanSession $session): void
    {
        CepScanLog::create([
            'cep' => $cep,
            'status' => 'failed',
            'error_message' => $error,
            'source' => 'nominatim+viacep',
            'state_target' => $session?->state,
        ]);

        if ($session) {
            $session->increment('failed');
            $session->increment('processed');
        }
    }

    private function showSummary(): void
    {
        $this->info('══════════════════════════════════════════');
        $this->info('📊 RESUMO DO SCAN');
        $this->info('══════════════════════════════════════════');
        $this->info("✅ Salvos: {$this->successCount}");
        $this->info("⚪ Não encontrados: {$this->notFoundCount}");
        $this->info("❌ Erros: {$this->errorCount}");
        $this->info('══════════════════════════════════════════');

        $tried = $this->successCount + $this->notFoundCount + $this->errorCount;
        if ($tried > 0) {
            $rate = round(($this->successCount / $tried) * 100, 1);
            $this->info("📈 Taxa de sucesso: {$rate}%");
        }

        $this->info('');
        $this->info('💡 Use --reset para limpar o cache e recomeçar');
        $this->info('📝 Acesse /admin/cep-scan para ver os logs detalhados');
    }
}
