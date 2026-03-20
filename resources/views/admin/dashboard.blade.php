<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Raio-X Vizinhança</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --card-bg: #ffffff;
        }

        * { box-sizing: border-box; }
        
        body {
            background: #f1f5f9;
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            margin: 0;
        }

        .navbar-admin {
            background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%);
            padding: 0.75rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .metric-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        .mini-stat {
            background: white;
            border-radius: 10px;
            padding: 0.75rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .mini-stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
        }

        .mini-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .section-header {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-sm th {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #64748b;
            padding: 0.5rem 0.75rem;
        }

        .table-sm td {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            vertical-align: middle;
        }

        .badge-status {
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .badge-online { background: #dcfce7; color: #15803d; }
        .badge-cooldown { background: #fef3c7; color: #b45309; }
        .badge-offline { background: #fee2e2; color: #b91c1c; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-failed { background: #fee2e2; color: #b91c1c; }

        .btn-period {
            padding: 0.3rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-period:hover { background: #f1f5f9; color: #1e293b; }
        .btn-period.active { background: var(--primary); color: white; border-color: var(--primary); }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.8rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; }
        .info-value { font-weight: 600; }

        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
            background: #e2e8f0;
        }

        .rank-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.8rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .rank-item:last-child { border-bottom: none; }
        .rank-num { 
            width: 20px; 
            height: 20px; 
            border-radius: 50%; 
            background: #f1f5f9; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .chart-box { height: 180px; position: relative; }

        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            border-radius: 4px;
        }

        .g-2 { gap: 0.5rem; }
        .g-3 { gap: 0.75rem; }
        .g-4 { gap: 1rem; }

        @media (max-width: 768px) {
            .hide-mobile { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar-admin">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-white bg-opacity-10 p-1.5 rounded-2">
                <i class="fas fa-bolt text-white"></i>
            </div>
            <div>
                <h5 class="mb-0 text-white fw-bold" style="font-size: 1rem;">Raio-X Admin</h5>
                <small class="text-white text-opacity-50" style="font-size: 0.7rem;">Territory Engine v3.0</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex gap-1">
                <a href="?period=7d" class="btn-period {{ $period === '7d' ? 'active' : '' }}">7D</a>
                <a href="?period=30d" class="btn-period {{ $period === '30d' ? 'active' : '' }}">30D</a>
                <a href="?period=90d" class="btn-period {{ $period === '90d' ? 'active' : '' }}">90D</a>
            </div>
            <button class="btn btn-outline-light btn-sm rounded" data-bs-toggle="modal" data-bs-target="#actionsModal" style="font-size: 0.75rem;">
                <i class="fas fa-cog"></i>
            </button>
            <a href="{{ route('logout') }}" class="btn btn-outline-light btn-sm rounded" style="font-size: 0.75rem;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid p-3">
    @if($overviewStats['error_rate'] > 5)
    <div class="alert alert-danger py-2 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem; border-radius: 8px;">
        <i class="fas fa-exclamation-triangle"></i>
        <span><strong>Alerta:</strong> Taxa de erro {{ $overviewStats['error_rate'] }}% (limite: 5%)</span>
    </div>
    @endif

    @if(!empty($queueMetrics['failed_jobs']) && $queueMetrics['failed_jobs'] > 0)
    <div class="alert alert-warning py-2 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem; border-radius: 8px;">
        <i class="fas fa-clock"></i>
        <span>{{ $queueMetrics['failed_jobs'] }} jobs falharam aguardando retry</span>
    </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted" style="font-size: 0.7rem;">Requisições</div>
                        <div class="fw-bold" style="font-size: 1.5rem;">{{ number_format($overviewStats['total_requests']) }}</div>
                        <div class="text-{{ $overviewStats['requests_change'] >= 0 ? 'success' : 'danger' }}" style="font-size: 0.7rem;">
                            <i class="fas fa-arrow-{{ $overviewStats['requests_change'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($overviewStats['requests_change']) }}%
                        </div>
                    </div>
                    <div class="mini-stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-sync-alt" style="font-size: 0.9rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted" style="font-size: 0.7rem;">Tokens</div>
                        <div class="fw-bold" style="font-size: 1.5rem;">{{ number_format($overviewStats['total_tokens']) }}</div>
                        <div class="text-{{ $overviewStats['tokens_change'] >= 0 ? 'success' : 'danger' }}" style="font-size: 0.7rem;">
                            <i class="fas fa-arrow-{{ $overviewStats['tokens_change'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($overviewStats['tokens_change']) }}%
                        </div>
                    </div>
                    <div class="mini-stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-coins" style="font-size: 0.9rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted" style="font-size: 0.7rem;">Tempo Médio</div>
                        <div class="fw-bold" style="font-size: 1.5rem;">{{ number_format($overviewStats['avg_response_time']) }}<span style="font-size: 0.7rem;">ms</span></div>
                    </div>
                    <div class="mini-stat-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-tachometer-alt" style="font-size: 0.9rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted" style="font-size: 0.7rem;">Taxa de Erro</div>
                        <div class="fw-bold text-{{ $overviewStats['error_rate'] > 5 ? 'danger' : 'success' }}" style="font-size: 1.5rem;">{{ $overviewStats['error_rate'] }}%</div>
                    </div>
                    <div class="mini-stat-icon {{ $overviewStats['error_rate'] > 5 ? 'bg-danger' : 'bg-success' }} bg-opacity-10 text-{{ $overviewStats['error_rate'] > 5 ? 'danger' : 'success' }}">
                        <i class="fas fa-{{ $overviewStats['error_rate'] > 5 ? 'exclamation-triangle' : 'check' }}" style="font-size: 0.9rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2">
            <div class="mini-stat">
                <div class="mini-stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-map-marked-alt"></i></div>
                <div class="mini-stat-value">{{ number_format($lifetimeStats['total_reports']) }}</div>
                <div class="text-muted" style="font-size: 0.65rem;">Relatórios</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="mini-stat">
                <div class="mini-stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-bolt"></i></div>
                <div class="mini-stat-value">{{ number_format($lifetimeStats['total_duels']) }}</div>
                <div class="text-muted" style="font-size: 0.65rem;">Duelos</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="mini-stat">
                <div class="mini-stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-dollar-sign"></i></div>
                <div class="mini-stat-value" style="font-size: 1rem;">${{ number_format($lifetimeStats['estimated_cost_usd'], 2) }}</div>
                <div class="text-muted" style="font-size: 0.65rem;">Custo Total</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="mini-stat">
                <div class="mini-stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                <div class="mini-stat-value">{{ number_format($reportStatus['completed']) }}</div>
                <div class="text-muted" style="font-size: 0.65rem;">Concluídos</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="mini-stat">
                <div class="mini-stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                <div class="mini-stat-value">{{ $queueMetrics['pending_jobs'] ?? 0 }}</div>
                <div class="text-muted" style="font-size: 0.65rem;">Jobs Pend.</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="mini-stat">
                <div class="mini-stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle"></i></div>
                <div class="mini-stat-value">{{ $reportStatus['failed'] }}</div>
                <div class="text-muted" style="font-size: 0.65rem;">Falhas</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <span><i class="fas fa-chart-line me-1"></i> Volume de Requisições</span>
                            <small class="text-muted">{{ ucfirst($period) }}</small>
                        </div>
                        <div class="p-2">
                            <div class="chart-box">
                                <canvas id="requestsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <span><i class="fas fa-microchip me-1"></i> Performance por Agente</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Agente</th>
                                        <th class="text-end">Req</th>
                                        <th class="text-end hide-mobile">Tempo</th>
                                        <th class="text-end">Erro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($agentPerformance as $agent)
                                    <tr>
                                        <td><span class="badge bg-primary" style="font-size: 0.65rem;">{{ $agent['agent_name'] }}</span></td>
                                        <td class="text-end">{{ number_format($agent['count']) }}</td>
                                        <td class="text-end hide-mobile">{{ number_format($agent['avg_time']) }}ms</td>
                                        <td class="text-end">
                                            @php $agentErrorRate = $agent['count'] > 0 ? round(($agent['fails'] / $agent['count']) * 100, 1) : 0; @endphp
                                            <span class="badge {{ $agentErrorRate > 5 ? 'bg-danger' : 'bg-success' }}" style="font-size: 0.65rem;">
                                                {{ $agentErrorRate }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted">Sem dados</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <span><i class="fas fa-map me-1"></i> Top Cidades</span>
                        </div>
                        <div class="p-2">
                            @forelse($topLocations as $index => $location)
                            <div class="rank-item">
                                <div class="d-flex align-items-center">
                                    <span class="rank-num">{{ $index + 1 }}</span>
                                    <span class="fw-medium">{{ $location['cidade'] }}</span>
                                    <span class="text-muted ms-1" style="font-size: 0.7rem;">{{ $location['uf'] }}</span>
                                </div>
                                <span class="badge bg-light text-dark" style="font-size: 0.65rem;">{{ number_format($location['count']) }}</span>
                            </div>
                            @empty
                            <div class="text-center text-muted py-3" style="font-size: 0.8rem;">Sem dados</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <span><i class="fas fa-fire me-1"></i> Top CEPs</span>
                        </div>
                        <div class="p-2">
                            @forelse($topCeps as $index => $cep)
                            <div class="rank-item">
                                <div>
                                    <span class="fw-mono" style="font-size: 0.8rem;">{{ substr($cep['cep'], 0, 5) }}-{{ substr($cep['cep'], 5) }}</span>
                                    <span class="text-muted ms-1" style="font-size: 0.65rem;">{{ $cep['cidade'] }}</span>
                                </div>
                                <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">{{ number_format($cep['views']) }}</span>
                            </div>
                            @empty
                            <div class="text-center text-muted py-3" style="font-size: 0.8rem;">Sem dados</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <span><i class="fas fa-chart-pie me-1"></i> Uso por Modelo</span>
                        </div>
                        <div class="p-2">
                            <div class="chart-box" style="height: 140px;">
                                <canvas id="modelChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="section-card mb-3">
                <div class="section-header">
                    <span><i class="fas fa-key me-1"></i> API Keys</span>
                    <span class="badge bg-light text-dark" style="font-size: 0.6rem;">{{ count($apiKeysStatus) }}</span>
                </div>
                <div class="p-2">
                    @forelse($apiKeysStatus as $key)
                    <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <div class="fw-medium" style="font-size: 0.8rem;">{{ $key['provider'] }}</div>
                            <div class="text-muted" style="font-size: 0.65rem;">...{{ $key['key_preview'] }} • {{ $key['usage_24h'] }}/24h</div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge-status badge-{{ $key['status'] }}">{{ strtoupper($key['status']) }}</span>
                            <button class="btn btn-outline-secondary action-btn btn-reset" data-id="{{ $key['id'] }}" title="Resetar">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3" style="font-size: 0.8rem;">Nenhuma chave</div>
                    @endforelse
                </div>
            </div>

            <div class="section-card mb-3">
                <div class="section-header">
                    <span><i class="fas fa-tachometer-alt me-1"></i> Rate Limits</span>
                </div>
                <div class="p-2">
                    @foreach($rateLimits as $key => $limit)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size: 0.75rem;">{{ $limit['name'] }}</span>
                            <span class="text-muted" style="font-size: 0.65rem;">{{ $limit['used'] }}/{{ $limit['max'] }}</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-bar bg-{{ $limit['is_limited'] ? 'danger' : 'primary' }}" style="width: {{ $limit['percentage'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="section-card mb-3">
                <div class="section-header">
                    <span><i class="fas fa-dollar-sign me-1"></i> Projeção de Custos</span>
                </div>
                <div class="p-2">
                    <div class="info-row">
                        <span class="info-label">Custo/Dia</span>
                        <span class="info-value text-success">${{ $costProjection['cost_per_day'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Proj. Mensal</span>
                        <span class="info-value text-warning">${{ $costProjection['projected_monthly_cost'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tokens/Mês</span>
                        <span class="info-value">{{ number_format($costProjection['projected_monthly_tokens']) }}</span>
                    </div>
                </div>
            </div>

            <div class="section-card mb-3">
                <div class="section-header">
                    <span><i class="fas fa-server me-1"></i> Sistema</span>
                </div>
                <div class="p-2">
                    <div class="info-row">
                        <span class="info-label">App</span>
                        <span class="info-value">v{{ $systemInfo['app_version'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">PHP/Laravel</span>
                        <span class="info-value">v{{ $systemInfo['php_version'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cache/Queue</span>
                        <span class="info-value">{{ $systemInfo['cache_driver'] }}/{{ $systemInfo['queue_driver'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Debug</span>
                        <span class="info-value">
                            <span class="badge bg-{{ $systemInfo['debug_mode'] ? 'danger' : 'success' }}" style="font-size: 0.6rem;">
                                {{ $systemInfo['debug_mode'] ? 'ON' : 'OFF' }}
                            </span>
                        </span>
                    </div>
                    @if(!empty($cacheMetrics['stats']))
                    <div class="info-row">
                        <span class="info-label">Redis Mem</span>
                        <span class="info-value">{{ $cacheMetrics['stats']['used_memory'] ?? 'N/A' }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <span><i class="fas fa-history me-1"></i> Logs Recentes</span>
                    <small class="text-muted">{{ $recentLogs->count() }}</small>
                </div>
                <div class="table-responsive" style="max-height: 200px;">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($recentLogs as $log)
                            <tr>
                                <td>
                                    <div style="font-size: 0.75rem;">{{ $log->created_at->format('H:i:s') }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">{{ $log->agent_name }}</div>
                                </td>
                                <td class="hide-mobile">
                                    <div style="font-size: 0.75rem;">{{ $log->model }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">{{ $log->provider }}</div>
                                </td>
                                <td class="text-end">
                                    @if($log->status === 'success')
                                        <i class="fas fa-check-circle text-success"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger" title="{{ Str::limit($log->error_message, 30) }}"></i>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="actionsModal">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fas fa-cog me-1"></i>Ações</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                <div class="d-grid gap-1">
                    <button class="btn btn-outline-primary btn-sm btn-action-admin" data-action="clear-cache">
                        <i class="fas fa-broom me-1"></i>Limpar Cache
                    </button>
                    <button class="btn btn-outline-warning btn-sm btn-action-admin" data-action="clear-cooldowns">
                        <i class="fas fa-clock me-1"></i>Limpar Cooldowns
                    </button>
                    <button class="btn btn-outline-info btn-sm btn-action-admin" data-action="restart-queue">
                        <i class="fas fa-redo me-1"></i>Reiniciar Fila
                    </button>
                    <button class="btn btn-outline-danger btn-sm btn-action-admin" data-action="clear-failed">
                        <i class="fas fa-trash me-1"></i>Limpar Falhas
                    </button>
                    <hr class="my-1">
                    <a href="{{ route('admin.export') }}?period={{ $period }}" class="btn btn-success btn-sm">
                        <i class="fas fa-download me-1"></i>Exportar CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const dailyData = {!! json_encode(array_values($dailyRequests)) !!};
const dailyLabels = {!! json_encode(array_keys($dailyRequests)) !!}.map(d => {
    const parts = d.split('-');
    return parts.length === 3 ? `${parts[2]}/${parts[1]}` : d;
});

new Chart(document.getElementById('requestsChart'), {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            data: dailyData,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#6366f1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});

const modelData = {!! json_encode(collect($modelUsage)->take(5)->pluck('count')->toArray()) !!};
const modelLabels = {!! json_encode(collect($modelUsage)->take(5)->pluck('model')->toArray()) !!};
const modelColors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];

new Chart(document.getElementById('modelChart'), {
    type: 'doughnut',
    data: {
        labels: modelLabels,
        datasets: [{ data: modelData, backgroundColor: modelColors, borderWidth: 0 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 5, font: { size: 10 } } }
        }
    }
});

document.querySelectorAll('.btn-reset').forEach(btn => {
    btn.addEventListener('click', async () => {
        try {
            const res = await fetch(`/admin/api-keys/${btn.dataset.id}/reset`, { method: 'POST' });
            if (res.ok) location.reload();
        } catch (e) { alert('Erro ao resetar'); }
    });
});

document.querySelectorAll('.btn-action-admin').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('Executar ação?')) return;
        try {
            const res = await fetch('/admin/action/' + btn.dataset.action, { method: 'POST' });
            const data = await res.json();
            alert(data.message);
            if (data.success) location.reload();
        } catch (e) { alert('Erro'); }
    });
});

setTimeout(() => location.reload(), 60000);
</script>

</body>
</html>
