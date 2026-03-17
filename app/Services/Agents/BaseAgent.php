<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Log;

/**
 * BaseAgent v1.0.0
 * Classe base para todos os micro-agentes do sistema Raio-X.
 * Garante padronização de versão e logs.
 */
abstract class BaseAgent
{
    /**
     * Versão do Agente - Deve ser sobrescrito nas classes filhas.
     */
    public const VERSION = '1.0.0';

    /**
     * Nome amigável do Agente.
     */
    protected string $name;

    public function __construct()
    {
        $this->name = str_replace('App\Services\Agents\\', '', static::class);
    }

    /**
     * Log especializado informando a versão do agente.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[{$this->name} v" . static::VERSION . "] {$message}", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->name} v" . static::VERSION . "] ERROR: {$message}", $context);
    }

    /**
     * Retorna a identidade do agente para a telemetria.
     */
    public function getIdentity(): array
    {
        return [
            'name' => $this->name,
            'version' => static::VERSION
        ];
    }
}
