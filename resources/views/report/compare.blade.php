<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparativo Premium: {{ $reportA->cep }} vs {{ $reportB->cep }} | Raio-X AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #f59e0b;
            --bg-body: #f8fafc;
            --dark: #0f172a;
            --card-radius: 24px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--dark);
            overflow-x: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 80px 0 140px;
            color: white;
            text-align: center;
            position: relative;
        }

        .vs-circle {
            width: 80px;
            height: 80px;
            background: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 900;
            font-size: 24px;
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.4);
            margin: -40px auto;
            position: relative;
            z-index: 10;
            border: 4px solid white;
        }

        .container-main {
            margin-top: -80px;
            position: relative;
            z-index: 20;
            padding-bottom: 80px;
        }

        .comparison-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.02);
            height: 100%;
            transition: all 0.3s;
        }

        .comparison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.08);
        }

        .vibe-tag {
            display: inline-block;
            padding: 6px 16px;
            background: #eef2ff;
            color: var(--primary);
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .metric-value {
            font-size: 4.5rem;
            font-weight: 900;
            letter-spacing: -4px;
            line-height: 1;
            margin: 15px 0;
            color: var(--primary);
        }

        .chart-container {
            background: white;
            border-radius: var(--card-radius);
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            margin: 40px 0;
            text-align: center;
        }

        .map-box {
            height: 250px;
            border-radius: 20px;
            margin-top: 25px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            z-index: 1;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            height: 100%;
            border: 1px solid #f1f5f9;
        }

        .price-badge {
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--dark);
            display: block;
        }

        .delta-badge {
            font-size: 0.75rem;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 99px;
        }

        .delta-plus { background: #d1fae5; color: #065f46; }
        .delta-minus { background: #fee2e2; color: #991b1b; }

        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 12px 20px;
            border-radius: 14px;
            text-decoration: none;
            backdrop-filter: blur(10px);
            font-weight: 600;
            transition: all 0.3s;
            z-index: 1001;
        }

        .radar-label {
            font-weight: 800;
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
    </style>
    <!-- Chart.js & Leaflet -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

    <header class="header-section">
        <a href="javascript:history.back()" class="back-btn"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
        <div class="container">
            <span class="metric-label text-white-50" style="letter-spacing: 5px;">Comparativo de Inteligência</span>
            <h1 class="header-title mt-2">Duelo de Microterritórios</h1>
            <p class="opacity-75 lead fw-medium">{{ $reportA->cidade }}/{{ $reportA->uf }} ⚔️ {{ $reportB->cidade }}/{{ $reportB->uf }}</p>
        </div>
    </header>

    <div class="container container-main">
        <div class="row g-4 align-items-stretch">
            <!-- Region A -->
            <div class="col-lg-5">
                <div class="comparison-card">
                    <span class="radar-label">CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $reportA->cep) }}</span>
                    <h2 class="h4 fw-black mt-1 mb-0">{{ $reportA->bairro }}</h2>
                    <div class="vibe-tag">{{ $reportA->territorial_classification }}</div>

                    <div class="metric-value">{{ $comparison->comparison_data['metrics_a']['total_score'] }}</div>
                    <span class="radar-label">Score Territorial AI</span>

                    <div id="mapA" class="map-box"></div>
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('report.show', $reportA->cep) }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                            Ver Relatório Completo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Chart / VS Section -->
            <div class="col-lg-2 d-flex flex-column align-items-center justify-content-center">
                <div class="vs-circle">VS</div>
            </div>

            <!-- Region B -->
            <div class="col-lg-5">
                <div class="comparison-card">
                    <span class="radar-label">CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $reportB->cep) }}</span>
                    <h2 class="h4 fw-black mt-1 mb-0">{{ $reportB->bairro }}</h2>
                    <div class="vibe-tag" style="background: #fffbeb; color: #d97706;">{{ $reportB->territorial_classification }}</div>

                    <div class="metric-value" style="color: var(--accent);">{{ $comparison->comparison_data['metrics_b']['total_score'] }}</div>
                    <span class="radar-label">Score Territorial AI</span>

                    <div id="mapB" class="map-box"></div>

                    <div class="text-center mt-4">
                        <a href="{{ route('report.show', $reportB->cep) }}" class="btn btn-outline-warning btn-sm rounded-pill px-4" style="color: #d97706; border-color: #d97706;">
                            Ver Relatório Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Radar Chart Section -->
        <div class="chart-container reveal">
            <h3 class="h4 fw-black mb-4">Equilíbrio de Atributos</h3>
            <div style="max-width: 500px; margin: 0 auto;">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <!-- Contexto Imobiliário e Qualidade de Vida -->
        <div class="row g-4 mt-2">
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="fw-black mb-4"><i class="fa-solid fa-house-chimney me-2 text-primary"></i>Custo imobiliário (m²)</h5>
                    <div class="row align-items-center">
                        <div class="col-5 text-center">
                            <span class="radar-label">{{ $reportA->bairro }}</span>
                            <span class="price-badge">{{ $reportA->real_estate_json['preco_m2'] ?? '?' }}</span>
                        </div>
                        <div class="col-2 text-center">
                            <i class="fa-solid fa-right-left opacity-25"></i>
                        </div>
                        <div class="col-5 text-center">
                            <span class="radar-label">{{ $reportB->bairro }}</span>
                            <span class="price-badge">{{ $reportB->real_estate_json['preco_m2'] ?? '?' }}</span>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded-4 small">
                        <i class="fa-solid fa-circle-info me-2 text-primary"></i>
                        Poder de compra local: Renda média de 
                        <strong>R$ {{ number_format($reportA->average_income, 0, ',', '.') }}</strong> (A) vs 
                        <strong>R$ {{ number_format($reportB->average_income, 0, ',', '.') }}</strong> (B).
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card" style="border-left: 5px solid #10b981;">
                    <h5 class="fw-black mb-4"><i class="fa-solid fa-leaf me-2 text-success"></i>Bem-estar Ambiental</h5>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="small fw-bold">Qualidade de Ar (AQI)</span>
                        <div>
                            <span class="badge {{ ($reportA->air_quality_index ?? 100) < ($reportB->air_quality_index ?? 100) ? 'bg-success' : 'bg-secondary' }}">{{ $reportA->air_quality_index }} (A)</span>
                            <span class="badge {{ ($reportB->air_quality_index ?? 100) < ($reportA->air_quality_index ?? 100) ? 'bg-success' : 'bg-secondary' }}">{{ $reportB->air_quality_index }} (B)</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small fw-bold">Temperatura Atual</span>
                        <div>
                            <span class="fw-black">{{ $reportA->climate_json['current']['temperature'] ?? '?' }}°C</span> vs
                            <span class="fw-black">{{ $reportB->climate_json['current']['temperature'] ?? '?' }}°C</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Verdict -->
        <div class="analysis-card reveal mt-4" style="border-left: 5px solid var(--primary);">
            <h4 class="fw-black text-primary mb-3"><i class="fa-solid fa-robot me-2"></i>Veredito da Inteligência Artificial</h4>
            <div class="lead text-secondary" style="text-align: justify; line-height: 1.8; font-size: 1.1rem;">
                {!! nl2br(e($comparison->analysis_text)) !!}
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('home') }}" class="btn btn-dark rounded-pill px-5 py-3 fw-bold shadow-lg">
                Fazer nova pesquisa
            </a>
        </div>
    </div>

    <script>
        // Init Radar Chart
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Infra', 'Mobilidade', 'Lazer', 'Comércio'],
                datasets: [
                    {
                        label: '{{ $reportA->bairro }}',
                        data: [
                            {{ $comparison->comparison_data['metrics_a']['infra'] }},
                            {{ $comparison->comparison_data['metrics_a']['mobility'] }},
                            {{ $comparison->comparison_data['metrics_a']['leisure'] }},
                            {{ $comparison->comparison_data['metrics_a']['commerce'] }}
                        ],
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: '#6366f1',
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1'
                    },
                    {
                        label: '{{ $reportB->bairro }}',
                        data: [
                            {{ $comparison->comparison_data['metrics_b']['infra'] }},
                            {{ $comparison->comparison_data['metrics_b']['mobility'] }},
                            {{ $comparison->comparison_data['metrics_b']['leisure'] }},
                            {{ $comparison->comparison_data['metrics_b']['commerce'] }}
                        ],
                        backgroundColor: 'rgba(245, 158, 11, 0.2)',
                        borderColor: '#f59e0b',
                        borderWidth: 3,
                        pointBackgroundColor: '#f59e0b'
                    }
                ]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: { display: false }
                    }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Init Maps
        const mapA = L.map('mapA', { zoomControl: false, scrollWheelZoom: false }).setView([{{ $reportA->lat }}, {{ $reportA->lng }}], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapA);
        L.marker([{{ $reportA->lat }}, {{ $reportA->lng }}]).addTo(mapA);

        const mapB = L.map('mapB', { zoomControl: false, scrollWheelZoom: false }).setView([{{ $reportB->lat }}, {{ $reportB->lng }}], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapB);
        L.marker([{{ $reportB->lat }}, {{ $reportB->lng }}]).addTo(mapB);
    </script>
</body>
</html>
