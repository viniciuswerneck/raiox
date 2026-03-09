<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Jobs\ProcessCepJob;
use Illuminate\Support\Str;
use App\Models\LocationReport;

class SeedCityCeps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raiox:seed-city {city : The name of the city (e.g. Jarinu)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches jobs to process all extracted CEPs for a given city.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $city = $this->argument('city');
        $fileName = Str::slug($city, '_') . '_ceps.json';
        
        $filePath = storage_path('app/' . $fileName);

        if (!File::exists($filePath)) {
            $this->error("Erro: Arquivo base não encontrado.");
            $this->line("Execute o script Python primeiro:");
            $this->info("python scripts/fetch_ceps.py");
            return 1;
        }

        $ceps = json_decode(File::get($filePath), true);

        if (empty($ceps)) {
            $this->error("O arquivo está vazio ou não possui CEPs válidos.");
            return 1;
        }

        $cleanCeps = [];
        foreach ($ceps as $cep) {
            $clean = preg_replace('/[^0-9]/', '', $cep);
            if (strlen($clean) === 8) {
                $cleanCeps[] = $clean;
            }
        }

        $existingCeps = LocationReport::whereIn('cep', $cleanCeps)->pluck('cep')->toArray();
        $missingCeps = array_diff($cleanCeps, $existingCeps);

        if (empty($missingCeps)) {
            $this->info("Todos os " . count($cleanCeps) . " CEPs já existem no banco de dados. Nenhum trabalho necessário.");
            return 0;
        }

        $this->info("Encontrados " . count($cleanCeps) . " CEPs. Destes, " . count($missingCeps) . " são novos e serão processados.");
        
        $dispatched = 0;
        $bar = $this->output->createProgressBar(count($missingCeps));
        $bar->start();

        foreach ($missingCeps as $cleanCep) {
            // Despacha para a Fila com 10 segundos de atraso entre cada um para garantir ida "1 por 1"
            $delaySeconds = $dispatched * 10;
            ProcessCepJob::dispatch($cleanCep)->delay(now()->addSeconds($delaySeconds));
            $dispatched++;
            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine(2);
        $this->info("Carga de {$dispatched} CEPs enviada para o servidor de Filas com sucesso!");
        $this->line("Os jobs estão rodando em background para proteger o limite da API do Gemini.");
        $this->line("Para processá-los, certifique-se de estar rodando: php artisan queue:work");

        return 0;
    }
}
