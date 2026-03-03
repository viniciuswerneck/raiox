<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ViaCepService
{
    public function getAddressByCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);
        $response = Http::withoutVerifying()->get("https://viacep.com.br/ws/{$cep}/json/");

        if ($response->failed() || isset($response['erro'])) {
            return null;
        }

        return $response->json();
    }
}
