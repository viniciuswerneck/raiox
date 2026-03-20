<?php

namespace App\Console\Commands;

use App\Models\CepScanLog;
use App\Models\CepScanSession;
use App\Models\LocationReport;
use App\Services\ViaCepService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScanCepsCommand extends Command
{
    protected $signature = 'cep:scan 
                            {--limit=100 : Número de CEPs por execução}
                            {--delay=2000 : Delay entre requisições (ms)}
                            {--state= : Filtrar por estado (ex: SP, RJ, MG)}
                            {--dry-run : Apenas simular sem salvar}
                            {--aggressive : Usar delay mínimo (500ms) para max speed}';

    protected $description = 'Escaneia CEPs do Brasil para alimentar o banco de dados';

    private array $statePrefixes = [
        'AC' => ['69'], 'AL' => ['57'], 'AP' => ['68', '69', '89'],
        'AM' => ['69', '70', '73', '76', '77', '78', '79', '80', '83', '84', '85', '86', '87', '92', '93', '94', '95', '96', '97'],
        'BA' => ['40', '41', '42', '43', '44', '45', '46', '47', '48', '49'],
        'CE' => ['60', '61', '62', '63', '64', '65', '66', '67'],
        'DF' => ['70', '71', '72', '73'],
        'ES' => ['29'],
        'GO' => ['72', '73', '74', '75', '76', '77', '78', '79'],
        'MA' => ['65', '66', '67', '68', '69'],
        'MT' => ['78', '79', '88'],
        'MS' => ['79', '88', '89'],
        'MG' => ['30', '31', '32', '33', '34', '35', '36', '37', '38', '39'],
        'PA' => ['66', '67', '68', '69', '87', '88'],
        'PB' => ['58', '59', '60', '61', '62', '63', '64'],
        'PR' => ['80', '81', '82', '83', '84', '85', '86', '87', '88', '89'],
        'PE' => ['50', '51', '52', '53', '54', '55', '56', '57'],
        'PI' => ['64', '65', '66', '67', '68'],
        'RJ' => ['20', '21', '22', '23', '24', '25', '26', '27', '28', '29'],
        'RN' => ['59', '64', '65', '66', '67'],
        'RS' => ['90', '91', '92', '93', '94', '95', '96', '97', '98', '99'],
        'RO' => ['76', '77', '78', '79'],
        'RR' => ['69', '83', '84', '85', '86', '87'],
        'SC' => ['88', '89', '90', '91', '92', '93', '94'],
        'SP' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'],
        'SE' => ['49', '50'],
        'TO' => ['77', '78', '79', '86', '87'],
    ];

    public function handle(ViaCepService $viaCepService): int
    {
        $limit = (int) $this->option('limit');
        $delay = $this->option('aggressive') ? 500 : (int) $this->option('delay');
        $state = $this->option('state');
        $dryRun = $this->option('dry-run');

        $this->info('══════════════════════════════════════════');
        $this->info('🚀 ROBÔ DE SCAN DE CEPs');
        $this->info('══════════════════════════════════════════');
        $this->info("📊 Limite: {$limit} | Delay: {$delay}ms");

        if ($state) {
            $stateUpper = strtoupper($state);
            $this->info("📍 Estado: {$stateUpper}");
            if (! isset($this->statePrefixes[$stateUpper])) {
                $this->error("❌ Estado {$stateUpper} não encontrado!");

                return self::FAILURE;
            }
        }

        if ($dryRun) {
            $this->warn('⚠️ MODO SIMULAÇÃO (dry-run)');
        }

        $lockKey = 'cep_scan_lock';
        $lock = Cache::lock($lockKey, 3600);

        if (! $lock->get()) {
            $this->error('❌ Scanner já está rodando!');

            return self::FAILURE;
        }

        $session = null;

        if (! $dryRun) {
            $session = CepScanSession::create([
                'state' => $state ? strtoupper($state) : null,
                'status' => 'running',
                'limit_planned' => $limit,
                'delay_ms' => $delay,
                'started_at' => now(),
            ]);
        }

        try {
            $existingCeps = LocationReport::pluck('cep')
                ->map(fn ($c) => preg_replace('/\D/', '', $c))
                ->flip()
                ->toArray();

            $this->info('📦 CEPs já existentes: '.count($existingCeps));

            $prefixes = $state
                ? ($this->statePrefixes[strtoupper($state)] ?? [])
                : $this->getAllPrefixes();

            $ceps = $this->generateCeps($prefixes, $existingCeps, $limit);

            $this->info("📋 {$this->count} novos CEPs para processar\n");

            $progressBar = $this->output->createProgressBar($this->count);
            $progressBar->start();

            foreach ($ceps as $cep) {
                $this->processCep($cep, $viaCepService, $dryRun, $session, $delay);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            if (! $dryRun && $session) {
                $session->update(['status' => 'completed', 'finished_at' => now()]);
            }

            $this->showSummary();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erro: '.$e->getMessage());
            Log::error('Scan CEP error: '.$e->getMessage());

            if ($session) {
                $session->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'notes' => $e->getMessage(),
                ]);
            }

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    private int $count = 0;

    private int $successCount = 0;

    private int $notFoundCount = 0;

    private int $errorCount = 0;

    private function generateCeps(array $prefixes, array $existing, int $limit): array
    {
        $ceps = [];
        $attempts = 0;
        $maxAttempts = $limit * 10;

        while (count($ceps) < $limit && $attempts < $maxAttempts) {
            $attempts++;

            $prefix = $prefixes[array_rand($prefixes)];
            $suffix = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $cep = $prefix.$suffix;

            if (! isset($existing[$cep]) && ! in_array($cep, $ceps)) {
                $ceps[] = $cep;
            }
        }

        $this->count = count($ceps);

        return $ceps;
    }

    private function getAllPrefixes(): array
    {
        return [
            '010', '011', '012', '013', '014', '015', '016', '017', '018', '019',
            '020', '021', '022', '023', '024', '025', '026', '027', '028', '029',
            '030', '031', '032', '033', '034', '035', '036', '037', '038', '039',
            '040', '041', '042', '043', '044', '045', '046', '047', '048', '049',
            '050', '051', '052', '053', '054', '055', '056', '057', '058', '059',
            '060', '061', '062', '063', '064', '065', '066', '067', '068', '069',
            '070', '071', '072', '073', '074', '075', '076', '077', '078', '079',
            '080', '081', '082', '083', '084', '085', '086', '087', '088', '089',
            '090', '091', '092', '093', '094', '095', '096', '097', '098', '099',
        ];
    }

    private function processCep(
        string $cep,
        ViaCepService $viaCepService,
        bool $dryRun,
        ?CepScanSession $session,
        int $delay
    ): void {
        if ($dryRun) {
            $this->info("\n  [DRY-RUN] {$cep} - seria processado");
            $this->successCount++;

            return;
        }

        $startTime = microtime(true);

        try {
            $data = $viaCepService->getAddressByCep($cep);

            if ($data) {
                $this->saveReport($cep, $data, $session);
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
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;

        $report = LocationReport::updateOrCreate(
            ['cep' => $cep],
            [
                'logradouro' => $data['logradouro'] ?? $data['address'] ?? null,
                'bairro' => $data['bairro'] ?? $data['district'] ?? null,
                'cidade' => $data['localidade'] ?? $data['city'] ?? null,
                'uf' => $data['uf'] ?? $data['state'] ?? null,
                'codigo_ibge' => $data['ibge'] ?? null,
                'lat' => $lat,
                'lng' => $lng,
                'status' => 'pending',
                'data_version' => 1,
            ]
        );

        $responseTime = (int) ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'] ?? 0) * 1000);

        CepScanLog::create([
            'cep' => $cep,
            'status' => 'success',
            'logradouro' => $data['logradouro'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['localidade'] ?? null,
            'uf' => $data['uf'] ?? null,
            'codigo_ibge' => $data['ibge'] ?? null,
            'lat' => $lat,
            'lng' => $lng,
            'source' => 'viacep',
            'state_target' => $session?->state,
            'response_time_ms' => $responseTime,
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
            'source' => 'viacep',
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
        $this->info('📝 Acesse /admin/cep-scan para ver os logs detalhados');
        $this->info('🔄 Os novos CEPs já estão no sitemap.xml');
    }
}
