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
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #0f172a;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }

        .navbar-admin {
            background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .card-metric {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--card-border);
            padding: 1.25rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .card-metric:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .metric-change {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .change-positive { color: var(--success); }
        .change-negative { color: var(--danger); }
        .change-neutral { color: #64748b; }

        .card-section {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--card-border);
            overflow: hidden;
        }

        .card-section-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--card-border);
            background: #f8fafc;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .card-section-title {
            font-weight: 600;
            margin: 0;
            font-size: 0.95rem;
        }

        .table-admin {
            margin: 0;
        }

        .table-admin thead th {
            background: #f8fafc;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border: none;
        }

        .table-admin tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-color: var(--card-border);
        }

        .badge-status {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-online { background: #dcfce7; color: #15803d; }
        .badge-cooldown { background: #fef3c7; color: #b45309; }
        .badge-offline { background: #fee2e2; color: #b91c1c; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-failed { background: #fee2e2; color: #b91c1c; }
        .badge-processing { background: #dbeafe; color: #1d4ed8; }

        .btn-action {
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 8px;
        }

        .period-selector .btn {
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background: white;
            color: #64748b;
            transition: all 0.2s;
        }

        .period-selector .btn:hover {
            background: #f1f5f9;
        }

        .period-selector .btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .progress-rate {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
        }

        .progress-bar-usage {
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-item:last-child { border-bottom: none; }

        .info-label { color: #64748b; font-size: 0.85rem; }
        .info-value { font-weight: 600; font-size: 0.85rem; }

        .alert-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--danger);
            color: white;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #94a3b8;
        }

        .chart-container { position: relative; height: 220px; }
        .chart-container-sm { position: relative; height: 180px; }
    </style>
</head>
<body>

<nav class="navbar-admin">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-10 p-2 rounded-3">
                <i class="fas fa-bolt text-white"></i>
            </div>
            <div>
                <h5 class="mb-0 text-white fw-bold">Raio-X Admin</h5>
                <small class="text-white text-opacity-50">Territory Engine v3.0</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="period-selector d-flex gap-1">
                <a href="?period=7d" class="btn {{ $period === '7d' ? 'active' : '' }}">7D</a>
                <a href="?period=30d" class="btn {{ $period === '30d' ? 'active' : '' }}">30D</a>
                <a href="?period=90d" class="btn {{ $period === '90d' ? 'active' : '' }}">90D</a>
            </div>
            <button class="btn btn-outline-light btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#actionsModal">
                <i class="fas fa-server me-1"></i>Ações
            </button>
            <a href="{{ route('logout') }}" method="POST" class="btn btn-outline-light btn-sm rounded-pill">
                <i class="fas fa-sign-out-alt me-1"></i>Sair
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4 px-4">
    @if($overviewStats['error_rate'] > 5)
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 rounded-3">
        <i class="fas fa-exclamation-triangle"></i>
        <span><strong>Alerta:</strong> Taxa de erro está em {{ $overviewStats['error_rate'] }}% - acima do threshold de 5%</span>
    </div>
    @endif

    @if(!empty($queueMetrics['failed_jobs']) && $queueMetrics['failed_jobs'] > 0)
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 rounded-3">
        <i class="fas fa-clock"></i>
        <span><strong>Atenção:</strong> {{ $queueMetrics['failed_jobs'] }} jobs falharam e aguardam retry</span>
    </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card-metric">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">Requisições</div>
                        <div class="metric-value">{{ number_format($overviewStats['total_requests']) }}</div>
                        <div class="metric-change {{ $overviewStats['requests_change'] >= 0 ? 'change-positive' : 'change-negative' }}">
                            <i class="fas fa-arrow-{{ $overviewStats['requests_change'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($overviewStats['requests_change']) }}% vs período anterior
                        </div>
                    </div>
                    <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-metric">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">Tokens Consumidos</div>
                        <div class="metric-value">{{ number_format($overviewStats['total_tokens']) }}</div>
                        <div class="metric-change {{ $overviewStats['tokens_change'] >= 0 ? 'change-positive' : 'change-negative' }}">
                            <i class="fas fa-arrow-{{ $overviewStats['tokens_change'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($overviewStats['tokens_change']) }}%
                        </div>
                    </div>
                    <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-metric">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">Tempo Médio</div>
                        <div class="metric-value">{{ number_format($overviewStats['avg_response_time']) }}ms</div>
                        <div class="metric-change change-neutral">por requisição</div>
                    </div>
                    <div class="metric-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-metric">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">Taxa de Erro</div>
                        <div class="metric-value {{ $overviewStats['error_rate'] > 5 ? 'text-danger' : 'text-success' }}">
                            {{ $overviewStats['error_rate'] }}%
                        </div>
                        <div class="metric-change {{ $overviewStats['error_rate'] > 5 ? 'change-negative' : 'change-positive' }}">
                            <i class="fas fa-{{ $overviewStats['error_rate'] > 5 ? 'exclamation-circle' : 'check-circle' }}"></i>
                            {{ $overviewStats['error_rate'] > 5 ? 'Acima do limite' : 'Dentro do normal' }}
                        </div>
                    </div>
                    <div class="metric-icon {{ $overviewStats['error_rate'] > 5 ? 'bg-danger' : 'bg-success' }} bg-opacity-10 text-{{ $overviewStats['error_rate'] > 5 ? 'danger' : 'success' }}">
                        <i class="fas fa-{{ $overviewStats['error_rate'] > 5 ? 'exclamation-triangle' : 'shield-check' }}"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-2">
            <div class="card-metric text-center">
                <div class="metric-icon bg-primary bg-opacity-10 text-primary mx-auto mb-2">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="metric-value">{{ number_format($lifetimeStats['total_reports']) }}</div>
                <div class="text-muted small">Relatórios</div>
                <div class="text-success small fw-medium">+{{ $lifetimeStats['reports_today'] }} hoje</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card-metric text-center">
                <div class="metric-icon bg-warning bg-opacity-10 text-warning mx-auto mb-2">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="metric-value">{{ number_format($lifetimeStats['total_duels']) }}</div>
                <div class="text-muted small">Duelos</div>
                <div class="text-success small fw-medium">+{{ $lifetimeStats['duels_today'] }} hoje</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card-metric text-center">
                <div class="metric-icon bg-info bg-opacity-10 text-info mx-auto mb-2">
                    <i class="fas fa-database"></i>
                </div>
                <div class="metric-value">{{ number_format($lifetimeStats['total_tokens_ever']) }}</div>
                <div class="text-muted small">Tokens Total</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card-metric text-center">
                <div class="metric-icon bg-success bg-opacity-10 text-success mx-auto mb-2">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="metric-value">${{ number_format($lifetimeStats['estimated_cost_usd'], 2) }}</div>
                <div class="text-muted small">Custo Est.</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card-metric text-center">
                <div class="metric-icon bg-purple bg-opacity-10 mx-auto mb-2" style="color: #7c3aed;">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="metric-value">{{ number_format($reportStatus['completed']) }}</div>
                <div class="text-muted small">Concluídos</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card-metric text-center">
                <div class="metric-icon bg-danger bg-opacity-10 text-danger mx-auto mb-2">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="metric-value">{{ $queueMetrics['pending_jobs'] ?? 0 }}</div>
                <div class="text-muted small">Jobs Pendentes</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-chart-line me-2"></i>Volume de Requisições</h6>
                    <small class="text-muted">{{ ucfirst($period) }}</small>
                </div>
                <div class="p-3">
                    <div class="chart-container">
                        <canvas id="requestsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-tasks me-2"></i>Performance por Agente</h6>
                </div>
                <div class="p-3">
                    <div class="table-responsive">
                        <table class="table table-admin">
                            <thead>
                                <tr>
                                    <th>Agente</th>
                                    <th>Requisições</th>
                                    <th>Tempo Médio</th>
                                    <th>Tokens</th>
                                    <th>Sucesso</th>
                                    <th>Taxa Erro</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agentPerformance as $agent)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $agent['agent_name'] }}</span></td>
                                    <td>{{ number_format($agent['count']) }}</td>
                                    <td>{{ number_format($agent['avg_time']) }}ms</td>
                                    <td>{{ number_format($agent['tokens']) }}</td>
                                    <td>
                                        <span class="badge badge-success">{{ number_format($agent['success']) }}</span>
                                    </td>
                                    <td>
                                        @php $agentErrorRate = $agent['count'] > 0 ? round(($agent['fails'] / $agent['count']) * 100, 1) : 0; @endphp
                                        <span class="badge {{ $agentErrorRate > 5 ? 'badge-failed' : 'badge-success' }}">
                                            {{ $agentErrorRate }}%
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">Nenhum dado disponível</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card-section">
                        <div class="card-section-header">
                            <h6 class="card-section-title"><i class="fas fa-map me-2"></i>Top Cidades</h6>
                        </div>
                        <div class="p-3">
                            @forelse($topLocations as $index => $location)
                            <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark" style="min-width: 24px;">{{ $index + 1 }}</span>
                                    <span class="fw-medium">{{ $location['cidade'] }}</span>
                                    <span class="text-muted small">{{ $location['uf'] }}</span>
                                </div>
                                <span class="badge bg-primary">{{ number_format($location['count']) }}</span>
                            </div>
                            @empty
                            <div class="text-center text-muted py-3">Sem dados</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-section">
                        <div class="card-section-header">
                            <h6 class="card-section-title"><i class="fas fa-fire me-2"></i>Top CEPs</h6>
                        </div>
                        <div class="p-3">
                            @forelse($topCeps as $index => $cep)
                            <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div>
                                    <span class="fw-mono fw-medium">{{ substr($cep['cep'], 0, 5) }}-{{ substr($cep['cep'], 5) }}</span>
                                    <div class="small text-muted">{{ $cep['cidade'] }}, {{ $cep['uf'] }}</div>
                                </div>
                                <span class="badge bg-warning text-dark">{{ number_format($cep['views']) }}</span>
                            </div>
                            @empty
                            <div class="text-center text-muted py-3">Sem dados</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-key me-2"></i>API Keys</h6>
                </div>
                <div class="p-3">
                    @forelse($apiKeysStatus as $key)
                    <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-medium small">{{ $key['provider'] }}</div>
                            <div class="text-muted" style="font-size: 10px;">Final ...{{ $key['key_preview'] }}</div>
                            <div class="small text-muted">{{ $key['usage_24h'] }} uso/24h</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-status badge-{{ $key['status'] }}">{{ strtoupper($key['status']) }}</span>
                            <button class="btn btn-outline-secondary btn-action btn-reset" data-id="{{ $key['id'] }}" title="Resetar cooldown">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">Nenhuma chave configurada</div>
                    @endforelse
                </div>
            </div>

            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-chart-pie me-2"></i>Uso por Modelo</h6>
                </div>
                <div class="p-3">
                    <div class="chart-container-sm">
                        <canvas id="modelChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-dollar-sign me-2"></i>Projeção de Custos</h6>
                </div>
                <div class="p-3">
                    <div class="info-item">
                        <span class="info-label">Tokens ({{ ucfirst($period) }})</span>
                        <span class="info-value">{{ number_format($costProjection['tokens_last_7d']) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Média Diária</span>
                        <span class="info-value">{{ number_format($costProjection['daily_average']) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Custo/Dia</span>
                        <span class="info-value text-success">${{ $costProjection['cost_per_day'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Projeção Mensal</span>
                        <span class="info-value text-warning">${{ $costProjection['projected_monthly_cost'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tokens Mensais</span>
                        <span class="info-value">{{ number_format($costProjection['projected_monthly_tokens']) }}</span>
                    </div>
                </div>
            </div>

            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-tachometer-alt me-2"></i>Rate Limits</h6>
                </div>
                <div class="p-3">
                    @foreach($rateLimits as $key => $limit)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-medium">{{ $limit['name'] }}</span>
                            <span class="small text-muted">{{ $limit['used'] }}/{{ $limit['max'] }}</span>
                        </div>
                        <div class="progress-rate">
                            <div class="progress-bar-usage {{ $limit['is_limited'] ? 'bg-danger' : 'bg-primary' }}"
                                 style="width: {{ $limit['percentage'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-server me-2"></i>Infraestrutura</h6>
                </div>
                <div class="p-3">
                    <div class="info-item">
                        <span class="info-label">App</span>
                        <span class="info-value">v{{ $systemInfo['app_version'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Laravel</span>
                        <span class="info-value">v{{ $systemInfo['laravel_version'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">PHP</span>
                        <span class="info-value">v{{ $systemInfo['php_version'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Cache</span>
                        <span class="info-value">{{ $systemInfo['cache_driver'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Queue</span>
                        <span class="info-value">{{ $systemInfo['queue_driver'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Debug</span>
                        <span class="info-value">
                            @if($systemInfo['debug_mode'])
                                <span class="badge badge-failed">ON</span>
                            @else
                                <span class="badge badge-success">OFF</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            @if(!empty($cacheMetrics['stats']))
            <div class="card-section mb-4">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-memory me-2"></i>Cache Redis</h6>
                </div>
                <div class="p-3">
                    <div class="info-item">
                        <span class="info-label">Memória</span>
                        <span class="info-value">{{ $cacheMetrics['stats']['used_memory'] ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Clientes</span>
                        <span class="info-value">{{ $cacheMetrics['stats']['connected_clients'] ?? 0 }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Hit Rate</span>
                        <span class="info-value {{ ($cacheMetrics['hit_rate'] ?? 0) > 80 ? 'text-success' : 'text-warning' }}">
                            {{ $cacheMetrics['hit_rate'] ?? 'N/A' }}%
                        </span>
                    </div>
                </div>
            </div>
            @endif

            <div class="card-section">
                <div class="card-section-header">
                    <h6 class="card-section-title"><i class="fas fa-history me-2"></i>Logs Recentes</h6>
                </div>
                <div class="p-0">
                    <table class="table table-admin mb-0">
                        <tbody>
                            @foreach($recentLogs as $log)
                            <tr>
                                <td>
                                    <div class="small fw-medium">{{ $log->created_at->format('H:i:s') }}</div>
                                    <div class="small text-muted">{{ $log->agent_name }}</div>
                                </td>
                                <td>
                                    <div class="small">{{ $log->model }}</div>
                                    <div class="small text-muted">{{ $log->provider }}</div>
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

<div class="modal fade" id="actionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-server me-2"></i>Ações Administrativas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-action-admin" data-action="clear-cache">
                        <i class="fas fa-broom me-2"></i>Limpar Cache
                    </button>
                    <button class="btn btn-outline-warning btn-action-admin" data-action="clear-cooldowns">
                        <i class="fas fa-clock me-2"></i>Limpar Cooldowns de IA
                    </button>
                    <button class="btn btn-outline-info btn-action-admin" data-action="restart-queue">
                        <i class="fas fa-redo me-2"></i>Reiniciar Fila
                    </button>
                    <button class="btn btn-outline-danger btn-action-admin" data-action="clear-failed">
                        <i class="fas fa-trash me-2"></i>Limpar Jobs Falhados
                    </button>
                    <hr>
                    <a href="{{ route('admin.export') }}?period={{ $period }}" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Exportar Logs (CSV)
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

const ctx = document.getElementById('requestsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Requisições',
            data: dailyData,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
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

const ctx2 = document.getElementById('modelChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: modelLabels,
        datasets: [{
            data: modelData,
            backgroundColor: modelColors,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: { boxWidth: 12, padding: 10, font: { size: 11 } }
            }
        }
    }
});

document.querySelectorAll('.btn-reset').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        try {
            const res = await fetch(`/admin/api-keys/${id}/reset`, { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                location.reload();
            }
        } catch (e) {
            alert('Erro ao resetar chave');
        }
    });
});

document.querySelectorAll('.btn-action-admin').forEach(btn => {
    btn.addEventListener('click', async () => {
        const action = btn.dataset.action;
        let url = '/admin/action/' + action;
        
        if (!confirm('Tem certeza que deseja executar esta ação?')) return;
        
        try {
            const res = await fetch(url, { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (e) {
            alert('Erro ao executar ação');
        }
    });
});

setTimeout(() => location.reload(), 60000);
</script>

</body>
</html>
