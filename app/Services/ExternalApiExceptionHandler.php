<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class ExternalApiExceptionHandler
{
    private array $apiExceptions = [];

    public function handleApiException(string $apiName, \Exception $e, array $context = []): ?array
    {
        $exceptionData = [
            'api' => $apiName,
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'timestamp' => now()->toIso8601String(),
            'context' => $context,
        ];

        if ($e instanceof ConnectionException) {
            $exceptionData['type'] = 'connection';
            $exceptionData['suggestion'] = 'Verifique a conectividade de rede ou se o serviço está disponível.';
            Log::warning("ExternalAPI Connection Error [{$apiName}]: ".$e->getMessage(), $context);
        } elseif ($e instanceof RequestException) {
            $exceptionData['type'] = 'http';
            $exceptionData['http_code'] = $e->response?->status();
            $exceptionData['suggestion'] = $this->getSuggestionForHttpCode($e->response?->status());
            Log::warning("ExternalAPI HTTP Error [{$apiName}]: ".$e->getMessage(), [
                'http_code' => $e->response?->status(),
                ...$context,
            ]);
        } else {
            $exceptionData['type'] = 'unknown';
            $exceptionData['suggestion'] = 'Erro desconhecido. Verifique os logs para mais detalhes.';
            Log::error("ExternalAPI Error [{$apiName}]: ".$e->getMessage(), $context);
        }

        $this->apiExceptions[$apiName][] = $exceptionData;

        return $exceptionData;
    }

    private function getSuggestionForHttpCode(?int $code): string
    {
        return match ($code) {
            400 => 'Requisição inválida. Verifique os parâmetros enviados.',
            401, 403 => 'Autenticação negada. Verifique as credenciais da API.',
            404 => 'Recurso não encontrado. Verifique se o endpoint está correto.',
            429 => 'Rate limit excedido. Aguarde antes de fazer novas requisições.',
            500, 502, 503, 504 => 'Erro no servidor da API. Tente novamente mais tarde.',
            default => 'Erro HTTP desconhecido. Verifique a documentação da API.'
        };
    }

    public function getExceptions(string $apiName): array
    {
        return $this->apiExceptions[$apiName] ?? [];
    }

    public function getAllExceptions(): array
    {
        return $this->apiExceptions;
    }

    public function getExceptionCount(string $apiName): int
    {
        return count($this->apiExceptions[$apiName] ?? []);
    }

    public function clearExceptions(?string $apiName = null): void
    {
        if ($apiName) {
            unset($this->apiExceptions[$apiName]);
        } else {
            $this->apiExceptions = [];
        }
    }

    public function getRecentExceptions(string $apiName, int $minutes = 60): array
    {
        $exceptions = $this->getExceptions($apiName);

        return array_filter($exceptions, function ($e) use ($minutes) {
            return \Carbon\Carbon::parse($e['timestamp'])->gt(now()->subMinutes($minutes));
        });
    }

    public function shouldRetry(string $apiName): bool
    {
        $recent = $this->getRecentExceptions($apiName, 5);

        return count($recent) < 3;
    }

    public function getLastException(string $apiName): ?array
    {
        $exceptions = $this->getExceptions($apiName);

        return end($exceptions) ?: null;
    }
}
