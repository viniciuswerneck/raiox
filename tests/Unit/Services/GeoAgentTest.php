<?php

namespace Tests\Unit\Services;

use App\Services\Agents\GeoAgent;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoAgentTest extends TestCase
{
    private GeoAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new GeoAgent;
    }

    public function test_resolve_known_inactive_cep(): void
    {
        $result = $this->agent->resolveCep('13089470');

        $this->assertIsArray($result);
        $this->assertEquals('Rua Cesario Galli', $result['logradouro']);
        $this->assertEquals('Jardim Nilópolis', $result['bairro']);
        $this->assertEquals('Campinas', $result['localidade']);
        $this->assertEquals('SP', $result['uf']);
        $this->assertEquals('3509502', $result['ibge']);
    }

    public function test_resolve_cep_via_viacep(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '13201000',
                'logradouro' => 'Rua Teste',
                'bairro' => 'Centro',
                'localidade' => 'Jundiaí',
                'uf' => 'SP',
                'ibge' => '3525904',
            ], 200),
        ]);

        $result = $this->agent->resolveCep('13201000');

        $this->assertIsArray($result);
        $this->assertEquals('13201000', $result['cep']);
        $this->assertEquals('Rua Teste', $result['logradouro']);
    }

    public function test_fallback_to_brasilapi(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
            'brasilapi.com.br/*' => Http::response([
                'street' => 'Rua BrasilAPI',
                'neighborhood' => 'Bairro API',
                'city' => 'São Paulo',
                'state' => 'SP',
                'city_code' => '3550308',
            ], 200),
        ]);

        $result = $this->agent->resolveCep('00000000');

        $this->assertIsArray($result);
        $this->assertEquals('Rua BrasilAPI', $result['logradouro']);
        $this->assertEquals('Bairro API', $result['bairro']);
    }

    public function test_fallback_to_awesomeapi(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
            'brasilapi.com.br/*' => Http::response(null, 404),
            'cep.awesomeapi.com.br/*' => Http::response([
                'address' => 'Rua AwesomeAPI',
                'district' => 'Bairro Awesome',
                'city' => 'Campinas',
                'state' => 'SP',
                'city_ibge' => '3509502',
                'lat' => '-22.9',
                'lng' => '-47.0',
            ], 200),
        ]);

        $result = $this->agent->resolveCep('00000000');

        $this->assertIsArray($result);
        $this->assertEquals('Rua AwesomeAPI', $result['logradouro']);
        $this->assertEquals('-22.9', $result['lat']);
    }

    public function test_returns_null_when_all_apis_fail(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
            'brasilapi.com.br/*' => Http::response(null, 500),
            'cep.awesomeapi.com.br/*' => Http::response(null, 500),
        ]);

        $result = $this->agent->resolveCep('99999999');

        $this->assertNull($result);
    }

    public function test_geolocate_city(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '-23.5', 'lon' => '-46.6', 'boundingbox' => ['-23.6', '-23.4', '-46.7', '-46.5']],
            ], 200),
        ]);

        $result = $this->agent->geolocateCity('São Paulo', 'SP');

        $this->assertIsArray($result);
        $this->assertEquals('-23.5', $result['lat']);
        $this->assertEquals('-46.6', $result['lon']);
        $this->assertIsArray($result['bbox']);
    }

    public function test_geolocate_with_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '-23.5',
                    'lon' => '-46.6',
                    'address' => [
                        'city' => 'São Paulo',
                        'state' => 'São Paulo',
                        'country_code' => 'br',
                        'neighbourhood' => 'Vila Madalena',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->agent->geolocate('Rua Augusta', 'São Paulo', 'SP');

        $this->assertIsArray($result);
        $this->assertEquals('-23.5', $result['lat']);
        $this->assertEquals('-46.6', $result['lon']);
        $this->assertEquals('SP', $result['state']);
        $this->assertEquals('Vila Madalena', $result['suburb']);
    }

    public function test_geolocate_ignores_non_brazil_results(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '40.7',
                    'lon' => '-74.0',
                    'address' => ['city' => 'New York', 'state' => 'New York', 'country_code' => 'us'],
                ],
            ], 200),
        ]);

        $result = $this->agent->geolocate('Main Street', 'New York', 'NY');

        $this->assertNull($result);
    }
}
