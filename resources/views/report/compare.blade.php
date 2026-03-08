<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duelo Territorial: {{ $reportA->bairro ?: $reportA->cidade }} vs {{ $reportB->bairro ?: $reportB->cidade }} | {{ config('app.name') }}</title>
    
    <!-- SEO Meta Tags -->
    @php
        $locationA = trim(($reportA->bairro ? $reportA->bairro . ' - ' : '') . $reportA->cidade);
        $locationB = trim(($reportB->bairro ? $reportB->bairro . ' - ' : '') . $reportB->cidade);
        $desc = "Duelo Territorial: Comparação detalhada de infraestrutura, segurança e qualidade de vida entre {$locationA} e {$locationB}. Descubra qual a melhor região.";
    @endphp
    <meta name="description" content="{{ $desc }}">
    <meta name="keywords" content="duelo de bairros, comparação {{ $locationA }} e {{ $locationB }}, melhores cidades, {{ $reportA->cidade }}, {{ $reportB->cidade }}, segurança pública">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Social Media -->
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="Duelo: {{ $locationA }} vs {{ $locationB }} - {{ config('app.name') }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('hero_background_city_1772568797393.png') }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Duelo: {{ $locationA }} vs {{ $locationB }}">
    <meta name="twitter:description" content="{{ $desc }}">
    <meta name="twitter:image" content="{{ asset('hero_background_city_1772568797393.png') }}">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Article",
      "headline": "Duelo Territorial: {{ $locationA }} vs {{ $locationB }}",
      "description": "{{ $desc }}",
      "url": "{{ url()->current() }}",
      "publisher": {
        "@@type": "Organization",
        "name": "{{ config('app.name') }}",
        "logo": {
          "@@type": "ImageObject",
          "url": "{{ asset('favicon.png') }}"
        }
      }
    }
    </script>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Leaflet & ChartJS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --glass: rgba(255, 255, 255, 0.75);
            --card-radius: 28px;
            --font-main: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: var(--font-main);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: var(--font-heading);
            font-weight: 800;
        }

        /* Hero Battle Section */
        .battle-hero {
            position: relative;
            background: var(--dark);
            padding: 80px 0 140px;
            overflow: hidden;
            color: white;
            text-align: center;
        }

        .battle-hero::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            z-index: 1;
        }

        .hero-content { position: relative; z-index: 2; }

        .vs-badge {
            width: 70px;
            height: 70px;
            background: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 24px;
            font-weight: 900;
            margin: 20px auto;
            border: 5px solid rgba(255,255,255,0.1);
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.4);
            animation: pulse-vs 2s infinite;
        }

        @keyframes pulse-vs {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 20px rgba(245, 158, 11, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }

        /* Comparison Grid */
        .comparison-grid {
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }

        .side-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 40px;
            box-shadow: 0 20px 50px -10px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.8);
            height: 100%;
            transition: transform 0.3s;
        }

        .side-card.winner {
            border: 2px solid var(--primary);
            box-shadow: 0 25px 60px -15px rgba(99, 102, 241, 0.2);
        }

        .metric-score {
            font-size: 5rem;
            font-weight: 900;
            line-height: 1;
            margin: 15px 0;
            letter-spacing: -3px;
            font-family: var(--font-heading);
        }

        .card-a .metric-score { color: var(--primary); }
        .card-b .metric-score { color: var(--accent); }

        .location-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
            color: var(--secondary);
            display: block;
            margin-bottom: 5px;
        }

        /* Bento Metrics */
        .metric-row {
            padding: 20px;
            background: white;
            border-radius: 20px;
            margin-bottom: 20px;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
        }

        .metric-row:hover {
            transform: scale(1.02);
            background: #f8fafc;
        }

        .metric-info { flex: 1; }
        .metric-info h6 { margin: 0; font-weight: 800; color: #64748b; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; }
        .metric-info p { margin: 0; font-weight: 700; color: var(--dark); }

        .metric-vs-center {
            width: 120px;
            text-align: center;
            font-weight: 900;
            font-size: 0.8rem;
            color: var(--secondary);
            position: relative;
        }

        .metric-val-a, .metric-val-b {
            width: 35%;
            padding: 15px;
            border-radius: 14px;
            font-weight: 800;
            text-align: center;
            background: #f8fafc;
        }

        .metric-val-a.is-winner { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .metric-val-b.is-winner { background: rgba(245, 158, 11, 0.1); color: var(--accent); }

        /* Map Mini */
        .mini-map {
            height: 200px;
            border-radius: 18px;
            margin-top: 20px;
            border: 2px solid #f1f5f9;
        }

        /* AI Section */
        .ai-verdict {
            background: var(--dark);
            color: white;
            border-radius: var(--card-radius);
            padding: 50px;
            position: relative;
            overflow: hidden;
            margin-top: 40px;
        }

        .ai-verdict::after {
            content: "\f0d0";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            bottom: -30px;
            right: 20px;
            font-size: 10rem;
            opacity: 0.05;
        }

        .radar-box {
            background: white;
            border-radius: var(--card-radius);
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
        }

        .back-nav {
            position: absolute;
            top: 30px; left: 30px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s;
            z-index: 100;
        }

        .back-nav:hover { color: white; transform: translateX(-5px); }

        @media (max-width: 768px) {
            .metric-vs-center { width: 60px; font-size: 0.6rem; }
            .side-card { padding: 25px; margin-bottom: 20px; }
            .metric-score { font-size: 3.5rem; }
        }
    </style>
</head>
<body>

    <a href="{{ route('home') }}" class="back-nav no-print">
        <i class="fa-solid fa-arrow-left me-2"></i>VOLTAR PARA BUSCA
    </a>

    <!-- BATTLE HERO -->
    <section class="battle-hero">
        <div class="container hero-content">
            <span style="font-size: 0.7rem; letter-spacing: 5px; text-transform: uppercase; font-weight: 900; opacity: 0.5;">Duelo Instrumental de Territórios</span>
            <div class="d-md-flex align-items-center justify-content-center gap-4 mt-3">
                <div class="text-md-end">
                    <h1 class="display-3 mb-0">{{ $reportA->bairro ?: $reportA->cidade }}</h1>
                    <span class="opacity-50 fw-bold">{{ $reportA->cidade }}/{{ $reportA->uf }}</span>
                </div>
                <div class="vs-badge">VS</div>
                <div class="text-md-start">
                    <h1 class="display-3 mb-0">{{ $reportB->bairro ?: $reportB->cidade }}</h1>
                    <span class="opacity-50 fw-bold">{{ $reportB->cidade }}/{{ $reportB->uf }}</span>
                </div>
            </div>
            <div class="mt-5 pt-3 border-top border-white border-opacity-10 d-inline-block px-4">
                <span class="text-white-50 small fw-bold text-uppercase tracking-widest" style="font-size: 0.75rem;">
                    <i class="fa-regular fa-clock me-1"></i> Duelo realizado em {{ $comparison->created_at->format('d/m/Y \à\s H:i') }}
                </span>
            </div>
        </div>
    </section>

    <!-- CONTENT -->
    <div class="container comparison-grid">
        @if($comparison->updated_at->diffInMonths(now()) >= 6)
        <div class="row mb-5 text-center px-4 no-print">
            <div class="col-12 py-3 px-4 bg-warning bg-opacity-25 border border-warning rounded-4 d-inline-flex justify-content-center align-items-center gap-4 mx-auto" style="max-width: 800px; backdrop-filter: blur(10px);">
                <i class="fa-solid fa-clock-rotate-left fa-2x text-warning"></i>
                <div class="text-start">
                    <h5 class="fw-black mb-1">Análise Antiga</h5>
                    <p class="small mb-0 fw-medium opacity-75">Este duelo tem mais de 6 meses. A IA e os dados podem ter evoluído.</p>
                </div>
                <a href="{{ route('report.compare_reprocess', ['cepA' => $reportA->cep, 'cepB' => $reportB->cep]) }}" class="btn btn-warning rounded-pill fw-black px-4 ms-auto shadow-sm">
                    REFAZER DUELO AGORA
                </a>
            </div>
        </div>
        @endif

        <div class="row g-4 align-items-stretch">
            
            @php
                $scoreA = $comparison->comparison_data['metrics_a']['total_score'];
                $scoreB = $comparison->comparison_data['metrics_b']['total_score'];
            @endphp

            <!-- CARD A -->
            <div class="col-lg-5">
                <div class="side-card card-a {{ $scoreA >= $scoreB ? 'winner' : '' }}">
                    @if($scoreA >= $scoreB)
                        <div class="badge bg-primary rounded-pill mb-3"><i class="fa-solid fa-crown me-1"></i>LÍDER TERRITORIAL</div>
                    @endif
                    <span class="location-label">REGIÃO CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $reportA->cep) }}</span>
                    <h2 class="h4 mb-4">{{ $reportA->logradouro ?: 'Centro' }}</h2>
                    
                    <div class="metric-score">{{ $scoreA }}</div>
                    <span class="location-label">SCORE FINAL DE QUALIDADE</span>

                    <div id="mapA" class="mini-map"></div>
                    
                    <a href="{{ route('report.show', $reportA->cep) }}" class="btn btn-outline-primary w-100 mt-4 rounded-pill fw-bold">
                        EXPLORAR REGIÃO A
                    </a>
                </div>
            </div>

            <!-- VS CENTER CHART -->
            <div class="col-lg-2 d-md-flex align-items-center justify-content-center">
                <div class="d-none d-lg-block text-center mt-5">
                    <div class="metric-vs-center">
                        <i class="fa-solid fa-chart-simple fa-2x opacity-25"></i>
                        <p class="mt-2 small opacity-50 fw-black">INDICADORES CHAVE</p>
                    </div>
                </div>
            </div>

            <!-- CARD B -->
            <div class="col-lg-5">
                <div class="side-card card-b {{ $scoreB >= $scoreA ? 'winner' : '' }}">
                    @if($scoreB >= $scoreA)
                        <div class="badge bg-warning text-dark rounded-pill mb-3"><i class="fa-solid fa-crown me-1"></i>LÍDER TERRITORIAL</div>
                    @endif
                    <span class="location-label">REGIÃO CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $reportB->cep) }}</span>
                    <h2 class="h4 mb-4">{{ $reportB->logradouro ?: 'Centro' }}</h2>

                    <div class="metric-score">{{ $scoreB }}</div>
                    <span class="location-label">SCORE FINAL DE QUALIDADE</span>

                    <div id="mapB" class="mini-map"></div>

                    <a href="{{ route('report.show', $reportB->cep) }}" class="btn btn-outline-warning w-100 mt-4 rounded-pill fw-bold" style="color: #d97706; border-color: #d97706;">
                        EXPLORAR REGIÃO B
                    </a>
                </div>
            </div>
        </div>

        <!-- RADAR COMPARISON -->
        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <div class="radar-box text-center">
                    <h4 class="mb-4">Equilíbrio Atributivo</h4>
                    <div style="max-width: 500px; margin: 0 auto;">
                        <canvas id="radarCompare"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- BENTO COMPARISON ROWS -->
        <h4 class="text-center mt-5 mb-4 opacity-50">DETALHAMENTO TÉCNICO</h4>

        <!-- Segurança -->
        <div class="metric-row">
            @php
                $sA = $reportA->safety_level;
                $sB = $reportB->safety_level;
                // Lógica simples de vitória de segurança
                $sA_win = str_contains(strtoupper($sA), 'ALT') && !str_contains(strtoupper($sB), 'ALT');
                $sB_win = str_contains(strtoupper($sB), 'ALT') && !str_contains(strtoupper($sA), 'ALT');
            @endphp
            <div class="metric-val-a {{ $sA_win ? 'is-winner' : '' }}">
                {{ $sA }}
            </div>
            <div class="metric-vs-center">
                <i class="fa-solid fa-shield-halved d-block mb-1 text-primary"></i>
                SEGURANÇA
            </div>
            <div class="metric-val-b {{ $sB_win ? 'is-winner' : '' }}">
                {{ $sB }}
            </div>
        </div>

        <!-- Renda / Econômico -->
        <div class="metric-row">
            <div class="metric-val-a {{ $reportA->average_income >= $reportB->average_income ? 'is-winner' : '' }}">
                R$ {{ number_format($reportA->average_income, 0, ',', '.') }}
            </div>
            <div class="metric-vs-center">
                <i class="fa-solid fa-wallet d-block mb-1 text-success"></i>
                ESTRATO ECONÔMICO
            </div>
            <div class="metric-val-b {{ $reportB->average_income >= $reportA->average_income ? 'is-winner' : '' }}">
                R$ {{ number_format($reportB->average_income, 0, ',', '.') }}
            </div>
        </div>

        <!-- Caminhabilidade -->
        <div class="metric-row">
            @php
                $wA = $reportA->walkability_score;
                $wB = $reportB->walkability_score;
            @endphp
            <div class="metric-val-a {{ (ord($wA) <= ord($wB)) && $wA ? 'is-winner' : '' }}">
                TIER {{ $wA ?: 'C' }}
            </div>
            <div class="metric-vs-center">
                <i class="fa-solid fa-person-walking d-block mb-1 text-info"></i>
                CAMINHABILIDADE
            </div>
            <div class="metric-val-b {{ (ord($wB) <= ord($wA)) && $wB ? 'is-winner' : '' }}">
                TIER {{ $wB ?: 'C' }}
            </div>
        </div>

        <!-- Ar -->
        <div class="metric-row">
            <div class="metric-val-a {{ $reportA->air_quality_index <= $reportB->air_quality_index ? 'is-winner' : '' }}">
                {{ $reportA->air_quality_index }} AQI
            </div>
            <div class="metric-vs-center">
                <i class="fa-solid fa-wind d-block mb-1 text-secondary"></i>
                PUREZA DO AR
            </div>
            <div class="metric-val-b {{ $reportB->air_quality_index <= $reportA->air_quality_index ? 'is-winner' : '' }}">
                {{ $reportB->air_quality_index }} AQI
            </div>
        </div>

        <!-- AI VERDICT -->
        <div class="ai-verdict">
            <div class="row align-items-center">
                <div class="col-md-2 text-center text-md-start mb-4 mb-md-0">
                    <div class="bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center p-3 rounded-4 shadow-lg">
                        <i class="fa-solid fa-wand-magic-sparkles fa-3x"></i>
                    </div>
                </div>
                <div class="col-md-10">
                    <h3 class="fw-black mb-3">Veredito da Inteligência Territorial</h3>
                    <div class="editorial-text opacity-90" style="line-height: 1.8; text-align: justify; font-size: 1.1rem; border-left: 3px solid var(--primary); padding-left: 20px;">
                        {!! nl2br(e($comparison->analysis_text)) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center my-5 py-5">
            <p class="text-muted small fw-bold mb-4">Relatório emitido via Raio-X AI Territorial Intelligence</p>
            <a href="{{ route('home') }}" class="btn btn-dark rounded-pill px-5 py-3 shadow-lg">
                INICIAR NOVA COMPARAÇÃO
            </a>
        </div>

    </div>

    <script>
        // RADAR CHART
        const ctx = document.getElementById('radarCompare').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['INFRA', 'MOBILIDADE', 'LAZER', 'SERVIÇOS', 'COMÉRCIO'],
                datasets: [
                    {
                        label: '{{ $reportA->bairro ?: 'Região A' }}',
                        data: [
                            {{ $comparison->comparison_data['metrics_a']['infra'] }},
                            {{ $comparison->comparison_data['metrics_a']['mobility'] }},
                            {{ $comparison->comparison_data['metrics_a']['leisure'] }},
                            {{ $comparison->comparison_data['metrics_a']['services'] ?? 50 }},
                            {{ $comparison->comparison_data['metrics_a']['commerce'] ?? 60 }}
                        ],
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: '#6366f1',
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1'
                    },
                    {
                        label: '{{ $reportB->bairro ?: 'Região B' }}',
                        data: [
                            {{ $comparison->comparison_data['metrics_b']['infra'] }},
                            {{ $comparison->comparison_data['metrics_b']['mobility'] }},
                            {{ $comparison->comparison_data['metrics_b']['leisure'] }},
                            {{ $comparison->comparison_data['metrics_b']['services'] ?? 40 }},
                            {{ $comparison->comparison_data['metrics_b']['commerce'] ?? 50 }}
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
                    legend: { position: 'bottom', labels: { font: { family: 'Outfit', weight: 'bold' } } }
                }
            }
        });

        // MAPS
        const mapA = L.map('mapA', { zoomControl: false, scrollWheelZoom: false }).setView([{{ $reportA->lat }}, {{ $reportA->lng }}], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapA);
        L.marker([{{ $reportA->lat }}, {{ $reportA->lng }}]).addTo(mapA);

        const mapB = L.map('mapB', { zoomControl: false, scrollWheelZoom: false }).setView([{{ $reportB->lat }}, {{ $reportB->lng }}], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapB);
        L.marker([{{ $reportB->lat }}, {{ $reportB->lng }}]).addTo(mapB);
    </script>
    <footer class="bg-dark text-white-50 py-5 mt-5">
        <div class="container text-center">
            <h5 class="text-white fw-black text-uppercase mb-3">{{ config('app.name') }}</h5>
            <p class="small mb-0">Territory Engine™ | Powered by Werneck &copy; {{ date('Y') }}</p>
        </div>
    </footer>
</body>
</html>
