<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEP Scanner - Raio-X Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f1f5f9; font-family: system-ui, sans-serif; }
        .navbar { background: #0f172a; padding: 0.5rem 1rem; }
        .panel { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .panel-header { padding: 0.6rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 600; font-size: 0.85rem; }
        table { font-size: 0.8rem; }
        th { font-size: 0.7rem; text-transform: uppercase; color: #64748b; background: #f8fafc !important; }
        .stat-box { background: white; border-radius: 10px; padding: 1rem; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .stat-value { font-size: 1.5rem; font-weight: 700; }
        .mini-row { display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; }
        .mini-row:last-child { border-bottom: none; }
        .badge-sm { font-size: 0.65rem; padding: 0.2rem 0.4rem; }
        .state-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.dashboard') }}" class="text-white text-decoration-none">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="text-white fw-bold">CEP Scanner</div>
                <div class="text-white-50" style="font-size: 0.7rem;">Alimentação do banco de dados</div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light btn-sm">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container-fluid p-3">
    <div class="row g-3 mb-3">
        <div class="col-md-2">
            <div class="stat-box">
                <div class="stat-value text-primary">{{ number_format($stats['total_ceps']) }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">Total CEPs</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-box">
                <div class="stat-value text-warning">{{ number_format($stats['pending_ceps']) }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">Pendentes</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-box">
                <div class="stat-value text-success">{{ number_format($stats['completed_ceps']) }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">Completados</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-box">
                <div class="stat-value">{{ number_format($stats['total_scanned']) }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">Escaneados</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-box">
                <div class="stat-value text-success">{{ number_format($stats['total_success']) }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">Sucessos</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-box">
                <div class="stat-value text-danger">{{ number_format($stats['total_failed']) }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">Falhas</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="panel mb-3">
                <div class="panel-header">
                    <i class="fas fa-terminal me-2"></i>Como Usar
                </div>
                <div class="p-3">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.8rem;">Estado para escanear:</label>
                        <select class="form-select form-select-sm" id="stateSelect">
                            <option value="">Todos os estados</option>
                            <option value="SP">SP - São Paulo</option>
                            <option value="RJ">RJ - Rio de Janeiro</option>
                            <option value="MG">MG - Minas Gerais</option>
                            <option value="ES">ES - Espírito Santo</option>
                            <option value="PR">PR - Paraná</option>
                            <option value="SC">SC - Santa Catarina</option>
                            <option value="RS">RS - Rio Grande do Sul</option>
                            <option value="BA">BA - Bahia</option>
                            <option value="PE">PE - Pernambuco</option>
                            <option value="CE">CE - Ceará</option>
                            <option value="GO">GO - Goiás</option>
                            <option value="DF">DF - Distrito Federal</option>
                            <option value="PA">PA - Pará</option>
                            <option value="AM">AM - Amazonas</option>
                            <option value="MA">MA - Maranhão</option>
                            <option value="PB">PB - Paraíba</option>
                            <option value="AL">AL - Alagoas</option>
                            <option value="PI">PI - Piauí</option>
                            <option value="RN">RN - Rio Grande do Norte</option>
                            <option value="SE">SE - Sergipe</option>
                            <option value="RO">RO - Rondônia</option>
                            <option value="AC">AC - Acre</option>
                            <option value="AP">AP - Amapá</option>
                            <option value="TO">TO - Tocantins</option>
                            <option value="MT">MT - Mato Grosso</option>
                            <option value="MS">MS - Mato Grosso do Sul</option>
                            <option value="RR">RR - Roraima</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.8rem;">Limite de CEPs:</label>
                        <select class="form-select form-select-sm" id="limitSelect">
                            <option value="50">50 CEPs</option>
                            <option value="100" selected>100 CEPs</option>
                            <option value="200">200 CEPs</option>
                            <option value="500">500 CEPs</option>
                            <option value="1000">1000 CEPs</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.8rem;">Delay entre requisições:</label>
                        <select class="form-select form-select-sm" id="delaySelect">
                            <option value="500">500ms (Rápido - pode bloquear)</option>
                            <option value="1000">1000ms (Moderado)</option>
                            <option value="2000" selected>2000ms (Recomendado)</option>
                            <option value="3000">3000ms (Conservative)</option>
                        </select>
                    </div>
                    <div class="alert alert-info py-2" style="font-size: 0.8rem;">
                        <i class="fas fa-info-circle me-1"></i>
                        Execute via terminal SSH:<br>
                        <code id="commandPreview">php artisan cep:scan --limit=100 --delay=2000</code>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <i class="fas fa-chart-bar me-2"></i>Por Estado
                </div>
                <div class="p-3">
                    @forelse($stateStats as $stat)
                    <div class="mini-row">
                        <span class="state-badge bg-{{ $stat->state == 'SP' ? 'primary' : ($stat->state == 'RJ' ? 'danger' : 'secondary') }} text-white">
                            {{ $stat->state }}
                        </span>
                        <span>{{ number_format($stat->success) }} CEPs</span>
                        <span class="text-muted">{{ $stat->sessions }} sessões</span>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">Nenhum scan realizado ainda</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel mb-3">
                <div class="panel-header">
                    <i class="fas fa-history me-2"></i>Sessões Recentes
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Status</th>
                                <th>Processados</th>
                                <th>Sucessos</th>
                                <th>Falhas</th>
                                <th>Taxa</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                            <tr>
                                <td>
                                    @if($session->state)
                                        <span class="state-badge bg-primary text-white">{{ $session->state }}</span>
                                    @else
                                        <span class="text-muted">Todos</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $session->status == 'completed' ? 'success' : ($session->status == 'running' ? 'warning' : 'secondary') }} badge-sm">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                </td>
                                <td>{{ number_format($session->processed) }}</td>
                                <td class="text-success">{{ number_format($session->success) }}</td>
                                <td class="text-danger">{{ number_format($session->failed) }}</td>
                                <td>{{ $session->success_rate }}%</td>
                                <td class="text-muted">{{ $session->created_at->format('d/m H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Nenhuma sessão registrada</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <i class="fas fa-list me-2"></i>Logs Recentes
                </div>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>CEP</th>
                                <th>Endereço</th>
                                <th>Cidade</th>
                                <th>Status</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                            <tr>
                                <td><code>{{ substr($log->cep, 0, 5) }}-{{ substr($log->cep, 5) }}</code></td>
                                <td class="text-truncate" style="max-width: 200px;">{{ $log->logradouro ?? '-' }}</td>
                                <td>{{ $log->cidade ?? '-' }} <span class="text-muted">{{ $log->uf }}</span></td>
                                <td>
                                    @if($log->status == 'success')
                                        <i class="fas fa-check-circle text-success"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger" title="{{ $log->error_message }}"></i>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $log->created_at->format('H:i:s') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Nenhum log ainda</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const stateSelect = document.getElementById('stateSelect');
const limitSelect = document.getElementById('limitSelect');
const delaySelect = document.getElementById('delaySelect');
const commandPreview = document.getElementById('commandPreview');

function updateCommand() {
    let cmd = 'php artisan cep:scan';
    if (stateSelect.value) cmd += ' --state=' + stateSelect.value;
    cmd += ' --limit=' + limitSelect.value;
    cmd += ' --delay=' + delaySelect.value;
    commandPreview.textContent = cmd;
}

stateSelect.addEventListener('change', updateCommand);
limitSelect.addEventListener('change', updateCommand);
delaySelect.addEventListener('change', updateCommand);

setTimeout(() => location.reload(), 30000);
</script>

</body>
</html>
