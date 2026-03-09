<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\NeighborhoodService;

class ProcessCepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cep;
    
    // Tenta executar até 5 vezes se houver timeout da API do Gemini
    public $tries = 5;

    // Retry com Exponential Backoff (60s, 120s, 240s)
    public $backoff = [60, 120, 240, 480];

    /**
     * Create a new job instance.
     */
    public function __construct(string $cep)
    {
        $this->cep = $cep;
    }

    /**
     * Define the job's middleware for API rate limiting.
     * Permitimos x requisições por minuto na API do Gemini.
     */
    public function middleware()
    {
        // 15 requests allowed every 1 minute. Adhere strictly to the chosen LLM free tier limits.
        return [
            (new \Illuminate\Queue\Middleware\RateLimited('gemini-api'))->dontRelease()
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(NeighborhoodService $service)
    {
        // A lógica central do seed: Injeta o serviço de Neighborhood e 
        // e ativa o pipeline que por baixo chama LLMAgent asíncronos.
        try {
            // Se o Localização/CEP não resolver, evita que o Job pare
            $report = $service->getCachedReport($this->cep);
            
            // Só para colocar no Log o sucesso do bairro
            \Log::info("Carga bem-sucedida pelo Job para CEP: " . $this->cep . " Bairro: " . ($report->neighborhood ?? 'N/A'));

        } catch (\Exception $e) {
            // Se houver "429 Too Many Requests" ou timeout da AI
            if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), 'timeout')) {
                \Log::warning("Gemini API falhou por limitação, re-enfileirando CEP: {$this->cep}");
                $this->release($this->backoff[$this->attempts() - 1] ?? 600); // Wait longer
            } else {
                \Log::error("Erro genérico na carga do CEP {$this->cep}: " . $e->getMessage());
                // Permite a exceção para que o Worker registre como failed, ou não falha silenciado.
                throw $e;
            }
        }
    }
}
