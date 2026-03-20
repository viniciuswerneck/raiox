<?php

namespace Tests\Unit\Services;

use App\Services\IbgeService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IbgeServiceTest extends TestCase
{
    private IbgeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IbgeService;
    }

    public function test_get_municipality_data_returns_array(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/api/v1/localidades/municipios/3525904' => Http::response([
                'id' => 3525904,
                'nome' => 'Jundiaí',
                'microrregiao' => ['nome' => 'Jundiaí'],
                'uf' => ['sigla' => 'SP', 'nome' => 'São Paulo'],
            ], 200),
            'servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/*' => Http::response([
                ['id' => '96385', 'res' => [['res' => [2022 => 500000]]]],
            ], 200),
        ]);

        $result = $this->service->getMunicipalityData('3525904');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('municipality_info', $result);
        $this->assertArrayHasKey('population', $result);
        $this->assertArrayHasKey('pib_per_capita', $result);
    }

    public function test_get_municipality_data_by_name(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/api/v1/localidades/estados/SP/municipios' => Http::response([
                ['id' => 3525904, 'nome' => 'Jundiaí'],
                ['id' => 3550308, 'nome' => 'Jarinu'],
            ], 200),
            'servicodados.ibge.gov.br/api/v1/localidades/municipios/3525904' => Http::response(['id' => 3525904, 'nome' => 'Jundiaí'], 200),
            'servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/*' => Http::response([], 200),
        ]);

        $result = $this->service->getMunicipalityDataByName('Jundiaí', 'SP');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ibge_code', $result);
        $this->assertEquals(3525904, $result['ibge_code']);
    }

    public function test_get_municipality_data_by_name_case_insensitive(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/api/v1/localidades/estados/SP/municipios' => Http::response([
                ['id' => 3525904, 'nome' => 'Jundiaí'],
            ], 200),
            'servicodados.ibge.gov.br/api/v1/localidades/municipios/3525904' => Http::response(['id' => 3525904, 'nome' => 'Jundiaí'], 200),
            'servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/*' => Http::response([], 200),
        ]);

        $result = $this->service->getMunicipalityDataByName('JUNDIAÍ', 'sp');

        $this->assertArrayHasKey('ibge_code', $result);
    }

    public function test_returns_empty_array_when_city_not_found(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/api/v1/localidades/estados/SP/municipios' => Http::response([], 200),
        ]);

        $result = $this->service->getMunicipalityDataByName('CidadeInvalida', 'SP');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
