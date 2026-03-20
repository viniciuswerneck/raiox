<?php

namespace Tests\Unit\Services;

use App\Services\ViaCepService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViaCepServiceTest extends TestCase
{
    private ViaCepService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ViaCepService;
    }

    public function test_returns_null_for_invalid_cep(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
        ]);

        $result = $this->service->getAddressByCep('00000000');
        $this->assertNull($result);
    }

    public function test_returns_address_data_for_valid_cep(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '13201000',
                'logradouro' => 'Rua teste',
                'bairro' => 'Centro',
                'localidade' => 'Jundiaí',
                'uf' => 'SP',
                'ibge' => '3525904',
            ], 200),
        ]);

        $result = $this->service->getAddressByCep('13201-000');

        $this->assertIsArray($result);
        $this->assertEquals('13201000', $result['cep']);
        $this->assertEquals('Rua teste', $result['logradouro']);
        $this->assertEquals('Centro', $result['bairro']);
        $this->assertEquals('Jundiaí', $result['localidade']);
        $this->assertEquals('SP', $result['uf']);
    }

    public function test_cleans_cep_formatting(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '13201000',
                'logradouro' => 'Rua teste',
                'localidade' => 'Jundiaí',
                'uf' => 'SP',
            ], 200),
        ]);

        $result = $this->service->getAddressByCep('13.201-000');
        $this->assertIsArray($result);
        $this->assertEquals('13201000', $result['cep']);
    }

    public function test_returns_null_on_http_error(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 500),
        ]);

        $result = $this->service->getAddressByCep('13201000');
        $this->assertNull($result);
    }
}
