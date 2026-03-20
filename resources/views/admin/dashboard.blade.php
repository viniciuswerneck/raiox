<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Raio-X</title>
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
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }

        .navbar { background: var(--dark); padding: 0.5rem 1rem; }
        .logo { display: flex; align-items: center; gap: 0.5rem; }
        .logo-icon { width: 32px; height: 32px; background: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
        .stat-label { font-size: 0.7rem; color: #64748b; }
        .stat-change { font-size: 0.65rem; font-weight: 600; }

        .panel { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .panel-header { padding: 0.5rem 0.75rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 600; font-size: 0.8rem; display: flex; align-items: center; justify-content: space-between; }

        table { font-size: 0.75rem; }
        th { font-size: 0.65rem; text-transform: uppercase; color: #64748b; font-weight: 600; background: #f8fafc !important; padding: 0.4rem 0.6rem !important; }
        td { padding: 0.4rem 0.6rem !important; vertical-align: middle; }
        
        .badge-sm { font-size: 0.6rem; padding: 0.15rem 0.4rem; }
        .btn-xs { padding: 0.2rem 0.4rem; font-size: 0.7rem; }

        .row-inline { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .col-fit { flex: 0 0 auto; }
        .col-fill { flex: 1 1 auto; min-width: 0; }

        .progress { height: 6px; background: #e2e8f0; border-radius: 3px; }
        .progress-bar-sm { height: 100%; border-radius: 3px; }

        .mini-row { display: flex; justify-content: space-between; padding: 0.3rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.75rem; }
        .mini-row:last-child { border-bottom: none; }

        .chart-container { height: 150px; position: relative; }
        .chart-container-sm { height: 120px; position: relative; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-bolt text-white"></i></div>
            <div>
                <div class="text-white fw-bold" style="font-size: 0.9rem;">Raio-X Admin</div>
                <div class="text-white-50" style="font-size: 0.65rem;">Territory Engine v3.0</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="row-inline">
                <a href="?period=7d" class="btn {{ $period === '7d' ? 'btn-primary' : 'btn-outline-light' }} btn-xs">{{ $period === '7d' ? '7D' : '7D' }}</a>
                <a href="?period=30d" class="btn {{ $period === '30d' ? 'btn-primary' : 'btn-outline-light' }} btn-xs">{{ $period === '30d' ? '30D' : '30D' }}</a>
                <a href="?period=90d" class="btn {{ $period === '90d' ? 'btn-primary' : 'btn-outline-light' }} btn-xs">{{ $period === '90d' ? '90D' : '90D' }}</a>
            </div>
            <button class="btn btn-outline-light btn-xs" data-bs-toggle="modal" data-bs-target="#actionsModal"><i class="fas fa-cog"></i></button>
            <a href="{{ route('logout') }}" class="btn btn-outline-light btn-xs"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</nav>

<div class="container-fluid p-2">
    @if($overviewStats['error_rate'] > 5)
    <div class="alert alert-danger py-1 px-2 mb-2 d-flex align-items-center gap-2" style="font-size: 0.75rem; border-radius: 6px;">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Taxa de erro {{ $overviewStats['error_rate'] }}% (limite: 5%)</span>
    </div>
    @endif

    <div class="d-flex gap-2 mb-2" style="overflow-x: auto;">
        <div class="stat-box col-fit" style="min-width: 140px;">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-sync-alt"></i></div>
            <div>
                <div class="stat-value">{{ number_format($overviewStats['total_requests']) }}</div>
                <div class="stat-label">Requisições</div>
                <div class="stat-change text-{{ $overviewStats['requests_change'] >= 0 ? 'success' : 'danger' }}">
                    <i class="fas fa-arrow-{{ $overviewStats['requests_change'] >= 0 ? 'up' : 'down' }}"></i> {{ abs($overviewStats['requests_change']) }}%
                </div>
            </div>
        </div>
        <div class="stat-box col-fit" style="min-width: 140px;">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-coins"></i></div>
            <div>
                <div class="stat-value">{{ number_format($overviewStats['total_tokens']) }}</div>
                <div class="stat-label">Tokens</div>
                <div class="stat-change text-{{ $overviewStats['tokens_change'] >= 0 ? 'success' : 'danger' }}">
                    <i class="fas fa-arrow-{{ $overviewStats['tokens_change'] >= 0 ? 'up' : 'down' }}"></i> {{ abs($overviewStats['tokens_change']) }}%
                </div>
            </div>
        </div>
        <div class="stat-box col-fit" style="min-width: 120px;">
            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-tachometer-alt"></i></div>
            <div>
                <div class="stat-value">{{ number_format($overviewStats['avg_response_time']) }}<span style="font-size: 0.7rem;">ms</span></div>
                <div class="stat-label">Tempo Médio</div>
            </div>
        </div>
        <div class="stat-box col-fit" style="min-width: 110px;">
            <div class="stat-icon {{ $overviewStats['error_rate'] > 5 ? 'bg-danger' : 'bg-success' }} bg-opacity-10 text-{{ $overviewStats['error_rate'] > 5 ? 'danger' : 'success' }}"><i class="fas fa-{{ $overviewStats['error_rate'] > 5 ? 'exclamation' : 'check' }}"></i></div>
            <div>
                <div class="stat-value text-{{ $overviewStats['error_rate'] > 5 ? 'danger' : 'success' }}">{{ $overviewStats['error_rate'] }}%</div>
                <div class="stat-label">Taxa Erro</div>
            </div>
        </div>
        <div class="stat-box col-fit" style="min-width: 100px;">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-map-marked-alt"></i></div>
            <div>
                <div class="stat-value">{{ number_format($lifetimeStats['total_reports']) }}</div>
                <div class="stat-label">Relatórios</div>
            </div>
        </div>
        <div class="stat-box col-fit" style="min-width: 90px;">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-bolt"></i></div>
            <div>
                <div class="stat-value">{{ number_format($lifetimeStats['total_duels']) }}</div>
                <div class="stat-label">Duelos</div>
            </div>
        </div>
        <div class="stat-box col-fit" style="min-width: 100px;">
            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="stat-value" style="font-size: 1rem;">${{ number_format($lifetimeStats['estimated_cost_usd'], 2) }}</div>
                <div class="stat-label">Custo Total</div>
            </div>
        </div>
        <div class="stat-box col-fit" style="min-width: 80px;">
            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle"></i></div>
            <div>
                <div class="stat-value">{{ $reportStatus['failed'] }}</div>
                <div class="stat-label">Falhas</div>
            </div>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-lg-8">
            <div class="panel mb-2">
                <div class="panel-header">
                    <span><i class="fas fa-chart-line me-1"></i> Volume de Requisições ({{ ucfirst($period) }})</span>
                </div>
                <div class="p-2">
                    <div class="chart-container">
                        <canvas id="requestsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-microchip me-1"></i> Agentes</span>
                        </div>
                        <div class="table-responsive" style="max-height: 200px;">
                            <table class="table table-hover table-sm mb-0">
                                <thead><tr><th>Agente</th><th class="text-end">Req</th><th class="text-end">Erro</th></tr></thead>
                                <tbody>
                                    @forelse($agentPerformance as $agent)
                                    <tr>
                                        <td><span class="badge badge-primary badge-sm">{{ $agent['agent_name'] }}</span></td>
                                        <td class="text-end">{{ number_format($agent['count']) }}</td>
                                        <td class="text-end">
                                            @php $e = $agent['count'] > 0 ? round(($agent['fails'] / $agent['count']) * 100, 1) : 0; @endphp
                                            <span class="badge badge-sm bg-{{ $e > 5 ? 'danger' : 'success' }}">{{ $e }}%</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted">Sem dados</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-map me-1"></i> Top Cidades</span>
                        </div>
                        <div class="p-2" style="max-height: 200px; overflow-y: auto;">
                            @forelse($topLocations as $i => $loc)
                            <div class="mini-row">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-light text-dark badge-sm">{{ $i + 1 }}</span>
                                    <span>{{ $loc['cidade'] }}</span>
                                    <span class="text-muted">{{ $loc['uf'] }}</span>
                                </div>
                                <span class="badge bg-primary badge-sm">{{ number_format($loc['count']) }}</span>
                            </div>
                            @empty
                            <div class="text-center text-muted py-2">Sem dados</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-fire me-1"></i> Top CEPs</span>
                        </div>
                        <div class="p-2" style="max-height: 200px; overflow-y: auto;">
                            @forelse($topCeps as $i => $cep)
                            <div class="mini-row">
                                <div>
                                    <span class="fw-mono">{{ substr($cep['cep'], 0, 5) }}-{{ substr($cep['cep'], 5) }}</span>
                                    <span class="text-muted">{{ $cep['cidade'] }}</span>
                                </div>
                                <span class="badge bg-warning badge-sm text-dark">{{ number_format($cep['views']) }}</span>
                            </div>
                            @empty
                            <div class="text-center text-muted py-2">Sem dados</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="row g-2">
                <div class="col-6">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-key me-1"></i> API Keys</span>
                            <span class="badge bg-light text-dark badge-sm">{{ count($apiKeysStatus) }}</span>
                        </div>
                        <div class="p-2" style="max-height: 140px; overflow-y: auto;">
                            @forelse($apiKeysStatus as $key)
                            <div class="mini-row">
                                <div>
                                    <div class="fw-medium">{{ $key['provider'] }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">...{{ $key['key_preview'] }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge badge-sm bg-{{ $key['status'] === 'online' ? 'success' : ($key['status'] === 'cooldown' ? 'warning' : 'danger') }}">
                                        {{ strtoupper($key['status']) }}
                                    </span>
                                    <button class="btn btn-outline-secondary btn-xs btn-reset" data-id="{{ $key['id'] }}"><i class="fas fa-redo"></i></button>
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-2">Nenhuma</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-tachometer-alt me-1"></i> Rate Limits</span>
                        </div>
                        <div class="p-2" style="max-height: 140px; overflow-y: auto;">
                            @foreach($rateLimits as $limit)
                            <div class="mb-1">
                                <div class="d-flex justify-content-between" style="font-size: 0.7rem;">
                                    <span>{{ $limit['name'] }}</span>
                                    <span class="text-muted">{{ $limit['used'] }}/{{ $limit['max'] }}</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar-sm bg-{{ $limit['is_limited'] ? 'danger' : 'primary' }}" style="width: {{ min($limit['percentage'], 100) }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-chart-pie me-1"></i> Modelos</span>
                        </div>
                        <div class="p-2">
                            <div class="chart-container-sm">
                                <canvas id="modelChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-dollar-sign me-1"></i> Custos</span>
                        </div>
                        <div class="p-2">
                            <div class="mini-row">
                                <span class="text-muted">Custo/Dia</span>
                                <span class="text-success fw-medium">${{ $costProjection['cost_per_day'] }}</span>
                            </div>
                            <div class="mini-row">
                                <span class="text-muted">Proj. Mês</span>
                                <span class="text-warning fw-medium">${{ $costProjection['projected_monthly_cost'] }}</span>
                            </div>
                            <div class="mini-row">
                                <span class="text-muted">Tokens/Mês</span>
                                <span>{{ number_format($costProjection['projected_monthly_tokens']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-server me-1"></i> Sistema</span>
                        </div>
                        <div class="p-2">
                            <div class="d-flex gap-3 flex-wrap">
                                <div><span class="text-muted">App</span> <strong>v{{ $systemInfo['app_version'] }}</strong></div>
                                <div><span class="text-muted">PHP</span> <strong>v{{ $systemInfo['php_version'] }}</strong></div>
                                <div><span class="text-muted">Cache</span> <strong>{{ $systemInfo['cache_driver'] }}</strong></div>
                                <div><span class="text-muted">Queue</span> <strong>{{ $systemInfo['queue_driver'] }}</strong></div>
                                <div><span class="text-muted">Debug</span> <span class="badge bg-{{ $systemInfo['debug_mode'] ? 'danger' : 'success' }} badge-sm">{{ $systemInfo['debug_mode'] ? 'ON' : 'OFF' }}</span></div>
                                @if(!empty($cacheMetrics['stats']))<div><span class="text-muted">Redis</span> <strong>{{ $cacheMetrics['stats']['used_memory'] ?? 'N/A' }}</strong></div>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fas fa-history me-1"></i> Logs ({{ $recentLogs->count() }})</span>
                        </div>
                        <div class="table-responsive" style="max-height: 120px;">
                            <table class="table table-sm table-hover mb-0">
                                <tbody>
                                    @foreach($recentLogs as $log)
                                    <tr>
                                        <td><strong>{{ $log->created_at->format('H:i:s') }}</strong></td>
                                        <td><span class="badge bg-light text-dark badge-sm">{{ $log->agent_name }}</span></td>
                                        <td>{{ $log->model }}</td>
                                        <td class="text-muted">{{ $log->provider }}</td>
                                        <td class="text-end">
                                            @if($log->status === 'success')
                                                <i class="fas fa-check-circle text-success"></i>
                                            @else
                                                <i class="fas fa-times-circle text-danger" title="{{ Str::limit($log->error_message, 40) }}"></i>
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
                    <button class="btn btn-outline-primary btn-sm btn-action-admin" data-action="clear-cache"><i class="fas fa-broom me-1"></i>Limpar Cache</button>
                    <button class="btn btn-outline-warning btn-sm btn-action-admin" data-action="clear-cooldowns"><i class="fas fa-clock me-1"></i>Limpar Cooldowns</button>
                    <button class="btn btn-outline-info btn-sm btn-action-admin" data-action="restart-queue"><i class="fas fa-redo me-1"></i>Reiniciar Fila</button>
                    <button class="btn btn-outline-danger btn-sm btn-action-admin" data-action="clear-failed"><i class="fas fa-trash me-1"></i>Limpar Falhas</button>
                    <hr class="my-1">
                    <a href="{{ route('admin.export') }}?period={{ $period }}" class="btn btn-success btn-sm"><i class="fas fa-download me-1"></i>Exportar CSV</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('requestsChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode(array_keys($dailyRequests)) !!}.map(d => d.split('-').slice(1).join('/')),
        datasets: [{ data: {!! json_encode(array_values($dailyRequests)) !!}, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 2 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('modelChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(collect($modelUsage)->take(5)->pluck('model')->toArray()) !!},
        datasets: [{ data: {!! json_encode(collect($modelUsage)->take(5)->pluck('count')->toArray()) !!}, backgroundColor: ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981'], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 3, font: { size: 9 } } } } }
});

document.querySelectorAll('.btn-reset').forEach(b => b.addEventListener('click', async () => { if(await fetch(`/admin/api-keys/${b.dataset.id}/reset`, {method:'POST'}).then(r=>r.ok)) location.reload(); }));
document.querySelectorAll('.btn-action-admin').forEach(b => b.addEventListener('click', async () => { if(!confirm('Executar?')) return; let r = await fetch('/admin/action/'+b.dataset.action, {method:'POST'}).then(r=>r.json()); alert(r.message); if(r.success) location.reload(); }));
setTimeout(() => location.reload(), 60000);
</script>

</body>
</html>
