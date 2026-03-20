<?php

namespace Tests\Unit\Services;

use App\Models\LocationReport;
use App\Services\Agents\CacheAgent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CacheAgentTest extends TestCase
{
    use RefreshDatabase;

    private CacheAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new CacheAgent;
    }

    public function test_returns_null_for_non_existent_cep(): void
    {
        $result = $this->agent->getCachedReport('00000000');
        $this->assertNull($result);
    }

    public function test_returns_report_within_six_months(): void
    {
        $report = LocationReport::create([
            'cep' => '13201000',
            'logradouro' => 'Rua Teste',
            'bairro' => 'Centro',
            'cidade' => 'Jundiaí',
            'uf' => 'SP',
            'status' => 'completed',
            'data_version' => 1,
        ]);

        $report->touch();

        $result = $this->agent->getCachedReport('13201000');

        $this->assertNotNull($result);
        $this->assertEquals('13201000', $result->cep);
    }

    public function test_returns_null_for_expired_report(): void
    {
        $report = LocationReport::create([
            'cep' => '13201000',
            'logradouro' => 'Rua Teste',
            'bairro' => 'Centro',
            'cidade' => 'Jundiaí',
            'uf' => 'SP',
            'status' => 'completed',
            'data_version' => 1,
        ]);

        \DB::table('location_reports')
            ->where('id', $report->id)
            ->update(['updated_at' => Carbon::now()->subMonths(7)]);

        $result = $this->agent->getCachedReport('13201000');
        $this->assertNull($result);
    }

    public function test_upsert_basic_data_creates_new_report(): void
    {
        $data = [
            'cep' => '13201000',
            'logradouro' => 'Rua Nova',
            'bairro' => 'Vila Nova',
            'cidade' => 'Jundiaí',
            'uf' => 'SP',
            'status' => 'processing',
            'data_version' => 1,
        ];

        $result = $this->agent->upsertBasicData('13201000', $data);

        $this->assertInstanceOf(LocationReport::class, $result);
        $this->assertEquals('13201000', $result->cep);
        $this->assertDatabaseHas('location_reports', ['cep' => '13201000', 'logradouro' => 'Rua Nova']);
    }

    public function test_upsert_basic_data_updates_existing_report(): void
    {
        LocationReport::create([
            'cep' => '13201000',
            'logradouro' => 'Rua Velha',
            'bairro' => 'Centro',
            'cidade' => 'Jundiaí',
            'uf' => 'SP',
            'status' => 'processing',
            'data_version' => 1,
        ]);

        $data = [
            'cep' => '13201000',
            'logradouro' => 'Rua Atualizada',
            'bairro' => 'Vila Nova',
            'cidade' => 'Jundiaí',
            'uf' => 'SP',
            'status' => 'completed',
            'data_version' => 2,
        ];

        $result = $this->agent->upsertBasicData('13201000', $data);

        $this->assertEquals('Rua Atualizada', $result->logradouro);
        $this->assertEquals(1, LocationReport::where('cep', '13201000')->count());
    }

    public function test_update_volatile_data(): void
    {
        $report = LocationReport::create([
            'cep' => '13201000',
            'logradouro' => 'Rua Teste',
            'bairro' => 'Centro',
            'cidade' => 'Jundiaí',
            'uf' => 'SP',
            'status' => 'completed',
            'data_version' => 1,
        ]);

        $clima = ['temp' => 25, 'humidity' => 60];
        $aqi = 45;

        $this->agent->updateVolatileData($report, $clima, $aqi);

        $report->refresh();
        $this->assertEquals($clima, $report->climate_json);
        $this->assertEquals(45, $report->air_quality_index);
    }

    public function test_cleans_cep_formatting(): void
    {
        LocationReport::create([
            'cep' => '13201000',
            'logradouro' => 'Rua Teste',
            'bairro' => 'Centro',
            'cidade' => 'Jundiaí',
            'uf' => 'SP',
            'status' => 'completed',
            'data_version' => 1,
        ]);

        $result = $this->agent->getCachedReport('13.201-000');

        $this->assertNotNull($result);
        $this->assertEquals('13201000', $result->cep);
    }
}
