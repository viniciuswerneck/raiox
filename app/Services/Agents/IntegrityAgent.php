<?php

namespace App\Services\Agents;

use App\Models\City;
use App\Models\LocationReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * IntegrityAgent v1.0.0
 * O "Auditor" do Sistema Raio-X.
 * Verifica a integridade e completude dos dados em tempo real quando as páginas são acessadas.
 */
class IntegrityAgent extends BaseAgent
{
    public const VERSION = '1.0.0';

    /**
     * Auditoria completa de um Relatório de CEP (LocationReport)
     */
    public function auditReport(LocationReport $report): array
    {
        $issues = [];
        $needsBackgroundUpdate = false;
        $needsImmediateUpdate = false;

        // 1. Verificação de Versão de Dados
        if ($report->data_version < 3) {
            $issues[] = "Versão de dados defasada (v{$report->data_version} < v3)";
            $needsBackgroundUpdate = true;
        }

        // 2. Verificação de Campos Estruturados (Novos campos de Segurança e Imobiliário)
        if ($report->status === 'completed') {
            if (empty($report->safety_description)) {
                $issues[] = "Descrição de segurança ausente";
                $needsBackgroundUpdate = true;
            }
            if (empty($report->real_estate_json)) {
                $issues[] = "Dados imobiliários ausentes";
                $needsBackgroundUpdate = true;
            }
            if (empty($report->history_extract)) {
                $issues[] = "Narrativa histórica ausente";
                $needsBackgroundUpdate = true;
            }
        }

        // 3. Verificação de Coordenadas
        if (empty($report->lat) || empty($report->lng)) {
            $issues[] = "Geolocalização (Lat/Lng) ausente";
            $needsImmediateUpdate = true;
        }

        // 4. Verificação de Status Travado (Mais de 10 min em pending/processing)
        if (in_array($report->status, ['pending', 'processing']) && $report->updated_at->diffInMinutes(now()) > 10) {
            $issues[] = "Status travado em '{$report->status}' por mais de 10 minutos";
            $needsImmediateUpdate = true;
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'needs_background_update' => $needsBackgroundUpdate,
            'needs_immediate_update' => $needsImmediateUpdate,
            'agent_version' => self::VERSION
        ];
    }

    /**
     * Auditoria completa de uma Cidade (Dashboard de Cidade)
     */
    public function auditCity(City $city): array
    {
        $issues = [];
        $needsUpdate = false;
        $isCritical = false;

        // 1. Verificação de Cache Geral
        if (empty($city->stats_cache)) {
            $issues[] = "Stats cache vazio";
            $needsUpdate = true;
            $isCritical = true;
        }

        // 2. Verificação de Proximidade Temporal
        if ($city->last_calculated_at && $city->last_calculated_at->diffInHours(now()) > 48) {
            $issues[] = "Dados com mais de 48h de idade";
            $needsUpdate = true;
        }

        // 3. Verificação de Novos CEPs processados
        $totalReports = LocationReport::where('cidade', $city->name)
            ->where('uf', $city->uf)
            ->where('status', 'completed')
            ->count();
        $cachedCount = $city->stats_cache['total_mapped_ceps'] ?? 0;
        
        if ($totalReports > $cachedCount) {
            $issues[] = "Novos CEPs detectados ({$totalReports} > {$cachedCount})";
            $needsUpdate = true;
        }

        // 4. Verificação de Mapeamento de Bairros (Discovery)
        $neighborhoodCount = count($city->stats_cache['neighborhood_list'] ?? []);
        if ($neighborhoodCount < 30 && $city->bbox_json) {
            // Se ainda não atingiu o threshold de 30 bairros e faz tempo que não tenta
            if (!$city->last_calculated_at || $city->last_calculated_at->diffInHours(now()) > 12) {
                $issues[] = "Mapeamento de bairros incompleto ({$neighborhoodCount} < 30)";
                $needsUpdate = true;
            }
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'needs_update' => $needsUpdate,
            'is_critical' => $isCritical,
            'agent_version' => self::VERSION
        ];
    }

    /**
     * Executa a reparação automática baseada na auditoria
     */
    public function autoRepairByReport(LocationReport $report): void
    {
        $audit = $this->auditReport($report);
        if ($audit['healthy']) return;

        $lockKey = "integrity_repair_report_{$report->id}";
        if (Cache::has($lockKey)) return;
        Cache::put($lockKey, true, 300);

        Log::warning("[IntegrityAgent] Reparando Relatório {$report->cep}: " . implode(", ", $audit['issues']));

        if ($audit['needs_immediate_update']) {
             // Se for crítico (falta lat/lng ou travado), deletamos e forçamos o PipelineCoordinator
             $report->update(['status' => 'pending', 'error_message' => 'Integrity Repair Triggered']);
             \App\Jobs\ProcessLocationReport::dispatch($report->cep, $report->id);
        } elseif ($audit['needs_background_update']) {
            // Se for apenas enriquecimento (v3, narrativa, segurança), dispara a narrativa/enriquecimento
            $wikiContext = [
                'bairro' => $report->bairro,
                'city' => $report->cidade,
                'state' => $report->uf
            ];
            \App\Jobs\GenerateNeighborhoodText::dispatch($report->cep, $report->id, $wikiContext);
        }
    }

    /**
     * Executa a reparação automática da Cidade
     */
    public function autoRepairByCity(City $city): void
    {
        $audit = $this->auditCity($city);
        if ($audit['needs_update']) {
            $lockKey = "integrity_repair_city_{$city->id}";
            if (Cache::has($lockKey)) return;
            Cache::put($lockKey, true, 600);

            Log::warning("[IntegrityAgent] Reparando Dashboard da Cidade {$city->name}: " . implode(", ", $audit['issues']));
            
            \App\Jobs\UpdateCityDataJob::dispatch($city);
        }
    }
}
