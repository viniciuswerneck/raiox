<?php

namespace App\Services\Agents;

use App\Jobs\GenerateNeighborhoodText;
use Illuminate\Support\Facades\Log;

class LLMAgent
{
    /**
     * Inicia a saga de requisição em background de IA enviando o CEP para a Fila Dedicada
     */
    public function dispatchTextGeneration(string $cep, int $reportId, array $wikiSearchContext): void
    {
        Log::info("LLMAgent: Fila disparada para o CEP {$cep} -> Report ID {$reportId}.");
        
        GenerateNeighborhoodText::dispatch($cep, $reportId, $wikiSearchContext)
            ->afterResponse();
    }
}
