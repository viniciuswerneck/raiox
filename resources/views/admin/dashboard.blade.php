<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle IA - Raio-X</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --dark-bg: #0f172a;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .navbar-top {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .card-stats {
            background: white;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .card-stats:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .bg-indigo { background: #e0e7ff; color: #4338ca; }
        .bg-purple { background: #f3e8ff; color: #7e22ce; }
        .bg-green { background: #dcfce7; color: #15803d; }
        .bg-amber { background: #fef3c7; color: #b45309; }

        .table-custom {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .table-custom th {
            background: #f1f5f9;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 1rem;
        }

        .table-custom td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            vertical-align: middle;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-online { background: #dcfce7; color: #15803d; }
        .status-cooldown { background: #fef3c7; color: #b45309; }
        .status-offline { background: #fee2e2; color: #b91c1c; }

        .modal-content {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 24px;
        }
        .module-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .module-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

<nav class="navbar-top mb-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-dark rounded-3 text-white">
                <i class="fa-solid fa-bolt-lightning"></i>
            </div>
            <h5 class="mb-0 fw-bold">Admin IA Telemetry</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#aboutModal">
                <i class="fa-solid fa-circle-info me-1"></i>Sobre o Sistema
            </button>
            <span class="badge bg-primary rounded-pill px-3">{{ now()->format('d/m/Y H:i') }}</span>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none small fw-bold">
                    <i class="fa-solid fa-right-from-bracket me-1"></i>Sair
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Modal Sobre a Arquitetura -->
<div class="modal fade" id="aboutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-microchip me-2"></i>Territory Engine v3.0 <span class="badge bg-primary ms-2" style="font-size: 10px; vertical-align: middle;">NEURAL CORE</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-white-50 mb-4">O **Territory Engine** representa o ápice da inteligência geo-analítica, fundindo big-data proprietário com processamento cognitivo de última geração para decodificar o DNA de qualquer território em segundos.</p>
                
                <div class="row">
                    <!-- Camada de Gathering -->
                    <div class="col-md-4">
                        <div class="small fw-bold text-primary mb-3 text-uppercase" style="letter-spacing: 2px;">Data Gathering Cluster</div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-satellite"></i></div>
                            <h6>Geo & POI Discovery</h6>
                            <p class="small text-white-50 mb-0">`GeoAgent` & `POIAgent`. Fusão de fontes OSM, Nominatim e ViaCEP para geolocalização precisa e mapeamento de infraestrutura comercial/lazer.</p>
                        </div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-cloud-sun"></i></div>
                            <h6>Environmental Data</h6>
                            <p class="small text-white-50 mb-0">`ClimaAgent`. Ingestão de métricas climáticas históricas e em tempo real para análise de qualidade de vida e resiliência ambiental.</p>
                        </div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-landmark"></i></div>
                            <h6>Socio-History Mesh</h6>
                            <p class="small text-white-50 mb-0">`WikiAgent` & `SocioAgent`. Extração de DNA histórico e indicadores socioeconômicos (renda/demografia) via Wikipedia e bases IBGE.</p>
                        </div>
                    </div>

                    <!-- Camada de Inteligência -->
                    <div class="col-md-4">
                        <div class="small fw-bold text-primary mb-3 text-uppercase" style="letter-spacing: 2px;">Cognitive Intelligence Layer</div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-brain"></i></div>
                            <h6>Neural Router Service</h6>
                            <p class="small text-white-50 mb-0">`LlmRouter`. Orquestração exaustiva de modelos (DeepSeek, Gemini, Llama) com lógica de auto-healing, fallback e balanceamento de carga.</p>
                        </div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-database"></i></div>
                            <h6>Knowledge RAG Agent</h6>
                            <p class="small text-white-50 mb-0">`KnowledgeAgent`. Sistema de Recuperação Aumentada por Geração (RAG) que injeta contexto proprietário no processo de análise.</p>
                        </div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                            <h6>Compare & Scoring</h6>
                            <p class="small text-white-50 mb-0">`CompareAgent`. Algoritmo matemático que pondera milhares de POIs para gerar scores de Mobilidade, Lazer e Infraestrutura.</p>
                        </div>
                    </div>

                    <!-- Camada de Orquestração -->
                    <div class="col-md-4">
                        <div class="small fw-bold text-primary mb-3 text-uppercase" style="letter-spacing: 2px;">Orchestration & Trust</div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <h6>Integrity Sentinel</h6>
                            <p class="small text-white-50 mb-0">`IntegrityAgent`. Auditoria passiva em tempo real com reparação autônoma de dados e re-hidratação de contextos incompletos.</p>
                        </div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-vial-circle-check"></i></div>
                            <h6>Calibration Guard</h6>
                            <p class="small text-white-50 mb-0">`AactService`. Camada de confiança que valida narrativas generativas contra indicadores econômicos reais para eliminar alucinações.</p>
                        </div>
                        <div class="module-card">
                            <div class="module-icon"><i class="fa-solid fa-diagram-project"></i></div>
                            <h6>Pipeline Coordinator</h6>
                            <p class="small text-white-50 mb-0">`PipelineCoordinator`. O regente da orquestra que gerencia a execução assíncrona paralela e sincronização de estados.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25">
                    <h6 class="fw-bold mb-2 small text-uppercase" style="letter-spacing: 1px;">Neural Flow Protocol</h6>
                    <p class="small mb-0">Raw Query &rarr; Context Gathering Mesh &rarr; Knowledge Injection &rarr; Neural Synthesis &rarr; Parametric Calibration &rarr; Final Integrity Audit.</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-4" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5">
    
    <!-- STATS ROW -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-stats">
                <div class="stat-icon bg-indigo"><i class="fa-solid fa-microchip"></i></div>
                <div class="text-muted small fw-medium">Requisições (7 Dias)</div>
                <div class="fs-3 fw-bold">{{ number_format($stats->total_requests ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="stat-icon bg-purple"><i class="fa-solid fa-coins"></i></div>
                <div class="text-muted small fw-medium">Tokens Consumidos</div>
                <div class="fs-3 fw-bold">{{ number_format($stats->total_tokens ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="stat-icon bg-green"><i class="fa-solid fa-gauge-high"></i></div>
                <div class="text-muted small fw-medium">Tempo Médio Resposta</div>
                <div class="fs-3 fw-bold">{{ number_format($stats->avg_response_time ?? 0) }}ms</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="stat-icon bg-amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="text-muted small fw-medium">Taxa de Erro</div>
                <div class="fs-3 fw-bold">{{ $stats->total_requests > 0 ? number_format(($stats->fail_count / $stats->total_requests) * 100, 1) : 0 }}%</div>
            </div>
        </div>
    </div>

    <!-- LIFETIME STATS ROW -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-stats">
                <div class="d-flex align-items-center mb-2">
                    <i class="fa-solid fa-map-location-dot text-primary me-2"></i>
                    <span class="text-muted small fw-medium">Relatórios Gerados</span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="fs-4 fw-bold">{{ number_format($totalReports) }}</div>
                    <div class="small fw-bold text-success">+{{ $reportsToday }} hoje</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="d-flex align-items-center mb-2">
                    <i class="fa-solid fa-bolt text-warning me-2"></i>
                    <span class="text-muted small fw-medium">Duelos Realizados</span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="fs-4 fw-bold">{{ number_format($totalDuels) }}</div>
                    <div class="small fw-bold text-success">+{{ $duelsToday }} hoje</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="d-flex align-items-center mb-2">
                    <i class="fa-solid fa-server text-info me-2"></i>
                    <span class="text-muted small fw-medium">Tokens Total (Histórico)</span>
                </div>
                <div class="fs-4 fw-bold">{{ number_format($totalTokensEver) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats border-primary bg-primary bg-opacity-10">
                <div class="d-flex align-items-center mb-2">
                    <i class="fa-solid fa-sack-dollar text-primary me-2"></i>
                    <span class="text-primary small fw-bold">Custo Est. LLM (API)</span>
                </div>
                <div class="fs-4 fw-black text-primary">$ {{ number_format($estimatedCostUsd, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- CHART -->
        <div class="col-lg-8">
            <div class="chart-container shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Volume de Requisições (7 Dias)</h6>
                </div>
                <div style="height: 220px;">
                    <canvas id="requestsChart"></canvas>
                </div>
            </div>

            <!-- RECENT LOGS -->
            <div class="table-custom shadow-sm mb-4">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Logs Recentes da Orquestração</h6>
                    <span class="small text-muted">Últimas 15 operações</span>
                </div>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Agente</th>
                            <th>Modelo / Provedor</th>
                            <th>Tempo</th>
                            <th>Tokens</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLogs as $log)
                        <tr>
                            <td class="text-muted">{{ $log->created_at->format('H:i:s') }}</td>
                            <td><span class="badge bg-light text-dark">{{ $log->agent_name }}</span></td>
                            <td>
                                <div class="fw-medium">{{ $log->model }}</div>
                                <div class="small text-muted text-uppercase">{{ $log->provider }}</div>
                            </td>
                            <td>{{ $log->response_time_ms }}ms</td>
                            <td>{{ number_format($log->total_tokens) }}</td>
                            <td>
                                @if($log->status === 'success')
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                @else
                                    <i class="fa-solid fa-circle-xmark text-danger" title="{{ $log->error_message }}"></i>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="col-lg-4">
            <!-- API KEYS -->
            <div class="card-stats border-0 mb-4">
                <h6 class="fw-bold mb-3">Status das API Keys</h6>
                @foreach($apiKeys as $key)
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom last-border-0">
                    <div>
                        <div class="fw-bold small">{{ $key->email ?? $key->provider }}</div>
                        <div class="text-muted" style="font-size: 10px;">{{ $key->provider }} • Final ...{{ substr($key->key, -4) }}</div>
                    </div>
                    <span class="status-badge status-{{ $key->status }}">{{ strtoupper($key->status) }}</span>
                </div>
                @endforeach
            </div>

            <!-- MODELS CONFIGURED -->
            <div class="card-stats border-0">
                <h6 class="fw-bold mb-3">Rotas e Modelos Ativos</h6>
                @foreach($allModels as $profile => $models)
                <div class="mb-3">
                    <div class="small fw-bold text-uppercase text-muted mb-2" style="font-size: 10px; letter-spacing: 0.1em;">{{ $profile }}</div>
                    @foreach($models as $m)
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fa-solid fa-microchip text-muted" style="font-size: 12px;"></i>
                        <span class="small">{{ $m['model'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            <!-- SYSTEM INFO -->
            <div class="card-stats border-0 mt-4">
                <h6 class="fw-bold mb-3">Infraestrutura</h6>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom last-border-0">
                    <span class="small fw-medium text-muted">Versão App</span>
                    <span class="small fw-bold">{{ $appVersion }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom last-border-0">
                    <span class="small fw-medium text-muted">Laravel Framework</span>
                    <span class="small fw-bold">v{{ $laravelVersion }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom last-border-0">
                    <span class="small fw-medium text-muted">PHP Version</span>
                    <span class="small fw-bold">v{{ $phpVersion }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom last-border-0">
                    <span class="small fw-medium text-muted">Timezone do Sistema</span>
                    <span class="small fw-bold">{{ config('app.timezone') }}</span>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('requestsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($chartData)) !!}.map(d => {
                let p = d.split('-');
                return p.length === 3 ? `${p[2]}/${p[1]}` : d;
            }),
            datasets: [{
                label: 'Requisições',
                data: {!! json_encode(array_values($chartData)) !!},
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
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

</body>
</html>
