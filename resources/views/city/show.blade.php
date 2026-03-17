<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panorama Territorial de {{ $city->name }} - {{ $city->uf }} | {{ config('app.name') }}</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #0f172a;
            --glass: rgba(255, 255, 255, 0.75);
            --card-radius: 24px;
            --font-main: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: var(--font-main);
            overflow-x: hidden;
        }

        h1, h2, h3, h4 { font-family: var(--font-heading); font-weight: 800; }

        /* NAVBAR & BREADCRUMBS */
        .nav-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1050;
            height: 72px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "\f105";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 10px;
            color: var(--secondary);
            opacity: 0.5;
        }

        .breadcrumb-custom .breadcrumb-item a {
            color: var(--secondary);
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .breadcrumb-custom .breadcrumb-item.active {
            color: var(--dark);
            font-weight: 800;
        }

        /* OMNISEARCH */
        .omnisearch-trigger {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 16px;
            color: #64748b;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 250px;
        }

        .omnisearch-trigger:hover {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        }

        .omnisearch-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            padding: 10vh 1rem;
        }

        .omnisearch-card {
            background: white;
            border-radius: 28px;
            max-width: 700px;
            margin: 0 auto;
            overflow: hidden;
            box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.5);
            transform: translateY(-20px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .omnisearch-active .omnisearch-card { transform: translateY(0); }
        .omnisearch-active { overflow: hidden; }

        .hero-section {
            padding-top: 152px; /* Aumentado para acomodar a navbar fixa */
        }

        .hero-section {
            position: relative;
            min-height: 400px;
            background-color: var(--dark);
            color: white;
            padding: 80px 0 120px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-bg-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(0deg, rgba(15, 23, 42, 1) 0%, rgba(15, 23, 42, 0.4) 60%, rgba(15, 23, 42, 0.2) 100%);
            z-index: 1;
        }

        .hero-bg-img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.6;
            z-index: 0;
        }

        .dashboard-container {
            margin-top: -100px;
            position: relative;
            z-index: 10;
        }

        .card-pro {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: var(--card-radius);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
            padding: 32px;
            height: 100%;
        }

        .stats-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .editorial-text { line-height: 1.7; color: #475569; text-align: justify; }
        .drop-cap::first-letter {
            float: left; font-size: 4rem; line-height: 0.8;
            padding-right: 12px; font-weight: 900; color: var(--primary);
        }

        .report-link {
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 15px;
            background: white;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .report-link:hover {
            transform: translateY(-3px);
            border-color: var(--primary);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .shadow-pro { box-shadow: 0 20px 40px -15px rgba(0,0,0,0.1); }
        .bg-white-10 { background: rgba(255,255,255,0.1); }
        .glass { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
        .bg-primary-10 { background: rgba(99, 102, 241, 0.1); }
        .bg-primary-10 { background: rgba(99, 102, 241, 0.1); }
        .poi-card-clickable { cursor: pointer; transition: all 0.2s; }
        .poi-card-clickable:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; border-color: var(--primary) !important; }
        .modal-glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.5); }
        .poi-item { padding: 16px; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
        .poi-item:last-child { border-bottom: none; }
        .poi-item:hover { background: #f8fafc; }

        /* Banner Style */
        .intelligence-banner {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            margin-top: 20px; /* Removido o negativo para não cobrir o título */
            z-index: 5;
            transition: transform 0.3s ease;
        }
        .intelligence-banner:hover {
            transform: translateY(-5px);
        }
        .intelligence-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), #10b981);
        }
        .pulse-icon {
            animation: pulse-primary 2s infinite;
        }
        @keyframes pulse-primary {
            0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
            100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }
    </style>
</head>
<body>

    <!-- NAV BAR & BREADCRUMBS -->
    <nav class="nav-glass no-print">
        <div class="container-fluid px-lg-5 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <a href="{{ route('home') }}" class="text-decoration-none">
                    <img src="{{ asset('favicon.png') }}" alt="Logo" style="height: 32px; width: 32px; object-fit: cover; border-radius: 8px;">
                </a>
                
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb breadcrumb-custom mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $city->name }}</li>
                    </ol>
                </nav>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="omnisearch-trigger" onclick="openOmnisearch()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span class="d-none d-lg-inline">Buscar outro CEP ou bairro...</span>
                    <span class="ms-auto d-none d-lg-inline text-muted small" style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px;">Ctrl K</span>
                </div>
                <a href="{{ route('ranking.index') }}" class="btn btn-dark btn-sm rounded-pill px-3 font-heading d-none d-md-flex align-items-center gap-2" style="font-size: 11px;">
                    <i class="fa-solid fa-ranking-star"></i> EXPLORAR RANKINGS
                </a>
            </div>
        </div>
    </nav>

    <!-- OMNISEARCH OVERLAY -->
    <div id="omnisearch" class="omnisearch-overlay" onclick="closeOmnisearch(event)">
        <div class="omnisearch-card" onclick="event.stopPropagation()">
            <div class="p-4 border-bottom bg-light d-flex align-items-center gap-3">
                <i class="fa-solid fa-magnifying-glass fs-4 text-primary"></i>
                <input type="text" id="omni-input" placeholder="Digite o CEP ou nome do bairro..." class="form-control border-0 bg-transparent fs-4 fw-bold p-0 shadow-none" autocomplete="off">
                <button class="btn btn-link text-muted p-0" onclick="closeOmnisearch(event)"><i class="fa-solid fa-xmark fs-4"></i></button>
            </div>
            <div id="omni-results" class="p-2 overflow-auto" style="max-height: 400px; min-height: 100px;">
                <div class="p-4 text-center text-muted small uppercase fw-bold tracking-widest">Digite para buscar...</div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center small text-muted">
                <span>Dica: Tente buscar por "Jardim Paulista" ou "01415-000"</span>
                <span>ESC para fechar</span>
            </div>
        </div>
    </div>

    <section class="hero-section">
        <img src="{{ $city->image_url ?: 'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?q=80&w=1200&auto=format&fit=crop' }}" class="hero-bg-img" alt="{{ $city->name }}">
        <div class="hero-bg-overlay"></div>
        <div class="container relative z-2 text-center text-md-start">
            
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="stats-badge mb-3 d-inline-block">
                        <i class="fa-solid fa-city me-2"></i> PANORAMA URBANO
                    </span>
                    <h1 class="display-3 fw-black text-white mb-2">{{ $city->name }}</h1>
                    <p class="lead text-white-50 mb-0">
                        {{ $city->uf }} • Brasil • {{ number_format($city->population ?: 0, 0, ',', '.') }} habitantes (IBGE)
                    </p>
                </div>
                <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                    <div class="stats-badge py-3 px-4 d-inline-block text-start" style="min-width: 200px;">
                        <span class="d-block small opacity-75">ESCORE DE VIZINHANÇA MÉDIO</span>
                        <span class="h2 fw-black text-white m-0">{{ $city->stats_cache['avg_score'] ?? '0.0' }} <small style="font-size: 0.5em; opacity: 0.7;">pts</small></span>
                    </div>

                    <div class="mt-3">
                        <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link da cidade copiado! 🚀')" class="btn btn-sm btn-outline-light rounded-pill px-3 glass" style="font-size: 11px;">
                            <i class="fa-solid fa-share-nodes me-2"></i> Compartilhar Cidade
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container dashboard-container">
        <!-- Dynamic Data Notice -->
        <div class="row mb-5 justify-content-center">
            <div class="col-lg-11">
                <div class="intelligence-banner p-4">
                    <div class="d-flex align-items-center flex-column flex-sm-row text-center text-sm-start">
                        <div class="rounded-circle pulse-icon bg-primary text-white d-flex align-items-center justify-content-center mb-3 mb-sm-0 me-sm-4" style="width: 56px; height: 56px; flex-shrink: 0;">
                            <i class="fa-solid fa-brain fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-black mb-1 text-dark" style="letter-spacing: -0.5px;">Inteligência Territorial Gerada por IA</h5>
                            <p class="text-muted mb-0 lh-sm">
                                Este panorama é dinâmico e evolui a cada pesquisa. Atualmente, analisamos <strong>{{ $city->stats_cache['total_mapped_ceps'] ?? 0 }} CEPs</strong> em {{ $city->name }}. 
                            </p>
                            <div class="mt-2 d-flex align-items-center">
                                <span class="text-primary fw-bold small me-2"><i class="fa-solid fa-handshake-angle me-1"></i> Quer colaborar?</span>
                                <a href="{{ route('home') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" style="font-size: 11px;">
                                    PESQUISE SEU CEP <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="ms-sm-auto mt-3 mt-sm-0">
                            <div class="badge bg-primary-10 text-primary rounded-pill px-3 py-2 border border-primary-10">
                                <i class="fa-solid fa-bolt me-1"></i> Modo Preditivo Ativo
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Estatísticas Rápidas -->
            <div class="col-md-4">
                <div class="card-pro">
                    <div class="metric-icon"><i class="fa-solid fa-wallet"></i></div>
                    <h4 class="h6 text-secondary text-uppercase fw-bold mb-1">Renda Média Mapeada</h4>
                    <p class="h3 fw-black mb-0">R$ {{ number_format($city->stats_cache['avg_income'] ?? 0, 2, ',', '.') }}</p>
                    <small class="text-muted">Média baseada em {{ $city->stats_cache['total_mapped_ceps'] ?? 0 }} CEPs</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-pro">
                    <div class="metric-icon"><i class="fa-solid fa-building"></i></div>
                    <h4 class="h6 text-secondary text-uppercase fw-bold mb-1">Perfil de Uso</h4>
                    <p class="h3 fw-black mb-0">{{ $city->stats_cache['predominant_class'] ?? 'Misto' }}</p>
                    <small class="text-muted">Tendência predominante na cidade</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-pro h-100">
                    <div class="metric-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <h4 class="h6 text-secondary text-uppercase fw-bold mb-1">Infraestrutura Total</h4>
                    <p class="h3 fw-black mb-0">{{ number_format($city->stats_cache['total_pois'] ?? 0, 0, ',', '.') }}</p>
                    <small class="text-muted">Estabelecimentos únicos mapeados</small>
                </div>
            </div>

            <!-- Serviços Essenciais (Atrativos do Usuário) -->
            @php
                $essentials = $city->stats_cache['essentials'] ?? [];
            @endphp
            <div class="col-12 mt-2">
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border poi-card-clickable" onclick="showPOICategory('Farmácias')">
                            <i class="fa-solid fa-pills text-danger mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['pharmacies'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Farmácias</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border poi-card-clickable" onclick="showPOICategory('Postos')">
                            <i class="fa-solid fa-gas-pump text-warning mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['gas_stations'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Postos</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border poi-card-clickable" onclick="showPOICategory('Mercados')">
                            <i class="fa-solid fa-cart-shopping text-success mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['markets'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Mercados</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border poi-card-clickable" onclick="showPOICategory('Saúde')">
                            <i class="fa-solid fa-hospital text-primary mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['health'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Saúde</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border poi-card-clickable" onclick="showPOICategory('Educação')">
                            <i class="fa-solid fa-graduation-cap text-info mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['education'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Educação</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border poi-card-clickable" onclick="showPOICategory('Gastronomia')">
                            <i class="fa-solid fa-utensils text-secondary mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $city->stats_cache['top_conveniencias']['Gastronomia'] ?? ($city->stats_cache['top_conveniencias']['Gastronomia/Bares'] ?? 0) }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Gastronomia</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Mix de Uso -->
            <div class="col-lg-8 mt-4">
                <div class="card-pro bg-white p-4" style="background: white !important;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="h6 text-secondary text-uppercase fw-bold mb-0">Mix Urbanístico (Perfil de Uso)</h4>
                        <span class="small text-muted">Proporção por Classificação Mapeada</span>
                    </div>
                    <div class="progress rounded-pill bg-light" style="height: 32px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
                        @php
                            $usageColors = [
                                'Residencial Alto Padrão' => '#4f46e5',
                                'Residencial Nobre' => '#6366f1',
                                'Residencial Médio' => '#818cf8',
                                'Residencial Popular' => '#a5b4fc',
                                'Comercial Central' => '#f59e0b',
                                'Turístico Premium' => '#ec4899',
                                'Zona de Expansão / Rural' => '#10b981'
                            ];
                        @endphp
                        @foreach($city->stats_cache['usage_percentages'] ?? [] as $label => $pct)
                            <div class="progress-bar transition-all" 
                                 role="progressbar" 
                                 style="width: {{ $pct }}%; background-color: {{ $usageColors[$label] ?? '#cbd5e1' }}; border-right: 2px solid white;" 
                                 aria-valuenow="{{ $pct }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"
                                 title="{{ $label }}: {{ $pct }}%">
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach($city->stats_cache['usage_percentages'] ?? [] as $label => $pct)
                            <div class="d-flex align-items-center me-3" style="font-size: 11px;">
                                <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: {{ $usageColors[$label] ?? '#cbd5e1' }};"></span>
                                <span class="fw-medium">{{ $label }}</span>
                                <span class="text-muted ms-1">({{ $pct }}%)</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Comparativo Regional (Novo) -->
            <div class="col-lg-4 mt-4">
                <div class="card-pro bg-white p-4" style="background: white !important;">
                    <h4 class="h6 text-secondary text-uppercase fw-bold mb-3">Comparativo Regional</h4>
                    
                    @php
                        $cityScore = $city->stats_cache['avg_score'] ?? 0;
                        $stateScore = $city->stats_cache['state_avg_score'] ?? 0;
                        $diff = $cityScore - $stateScore;
                        $isBetter = $diff >= 0;
                    @endphp

                    <div class="d-flex align-items-center mb-4">
                        <div class="me-3">
                            <div class="h2 fw-black mb-0 {{ $isBetter ? 'text-success' : 'text-primary' }}">
                                {{ $isBetter ? '+' : '' }}{{ number_format($diff, 1) }}
                            </div>
                            <div class="small text-muted fw-bold">vs média de {{ $city->uf }}</div>
                        </div>
                        <div class="ms-auto h1 opacity-25">
                            <i class="fa-solid {{ $isBetter ? 'fa-arrow-trend-up text-success' : 'fa-arrow-trend-down text-danger' }}"></i>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Escore Municipal ({{ $city->name }})</span>
                            <span class="fw-bold">{{ $cityScore }} pts</span>
                        </div>
                        <div class="progress rounded-pill bg-light mb-3" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: {{ $cityScore }}%"></div>
                        </div>

                        <div class="d-flex justify-content-between small mb-1">
                            <span>Média Estadual ({{ $city->uf }})</span>
                            <span class="fw-bold">{{ $stateScore }} pts</span>
                        </div>
                        <div class="progress rounded-pill bg-light" style="height: 6px;">
                            <div class="progress-bar bg-secondary" style="width: {{ $stateScore }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- História e Resumo -->
            <div class="col-lg-8">
                <div class="card-pro">
                    <h3 class="fw-black mb-4"><i class="fa-solid fa-scroll me-2 text-primary"></i> Contexto Cultural e Geográfico</h3>
                    <div class="editorial-text drop-cap mb-5">
                        {!! nl2br(e($city->history_extract ?: 'Aguardando processamento da IA...')) !!}
                    </div>

                    <hr class="opacity-10 my-5">

                    <!-- Destaques da Infraestrutura (Novo) -->
                    <h3 class="fw-black mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-store me-2 text-primary"></i> 
                        Conveniências & Serviços Mapeados
                    </h3>
                    <div class="row g-3">
                        @php
                            $iconMap = [
                                'Educação' => 'graduation-cap',
                                'Educação Infantil' => 'child-reaching',
                                'Universidades' => 'building-columns',
                                'Faculdades' => 'building-columns',
                                'Bancos' => 'building-columns',
                                'Caixas Eletrônicos' => 'money-bill-transfer',
                                'Farmácias' => 'pills',
                                'Saúde (Hospitais)' => 'hospital',
                                'Clínicas Médicas' => 'stethoscope',
                                'Médicos/Consultórios' => 'user-doctor',
                                'Odontologia' => 'tooth',
                                'Pet/Veterinários' => 'paw',
                                'Gastronomia' => 'utensils',
                                'Cafeterias' => 'mug-hot',
                                'Lanches/Fast Food' => 'burger',
                                'Lazer Noturno (Bares)' => 'martini-glass',
                                'Pubs/Bares' => 'beer-mug-empty',
                                'Casas Noturnas' => 'music',
                                'Postos de Combustível' => 'gas-pump',
                                'Saúde & Bem-Estar' => 'heart-pulse',
                                'Academias' => 'dumbbell',
                                'Lazer & Áreas Verdes' => 'leaf',
                                'Praças Públicas' => 'couch',
                                'Templos/Igrejas' => 'church',
                                'Religião' => 'hands-praying',
                                'Padarias/Confeitarias' => 'bread-slice',
                                'Mecânicos/Oficinas' => 'wrench',
                                'Lava Rápido' => 'faucet-detergent',
                                'Estacionamento' => 'square-p',
                                'Cultura/Bibliotecas' => 'book',
                                'Cultura/Teatros' => 'masks-theater',
                                'Cultura/Cinemas' => 'film',
                                'Segurança Pública' => 'shield-halved',
                                'Bombeiros' => 'fire-extinguisher',
                                'Serviços Postais' => 'envelope',
                                'Serviços Públicos' => 'building',
                                'Supermercados' => 'cart-shopping',
                                'Lojas de Conveniência' => 'shop',
                                'Shopping Center' => 'bag-shopping',
                                'Lojas de Departamento' => 'building-store',
                                'Moda/Vestuário' => 'shirt',
                                'Lojas de Calçados' => 'shoe-prints',
                                'Beleza & Estética' => 'sparkles',
                                'Salão de Beleza' => 'scissors',
                                'Óticas' => 'glasses',
                                'Joalherias' => 'gem',
                                'Lojas de Variedades' => 'boxes-packing',
                                'Material de Construção' => 'trowel-bricks',
                                'Ferragens/DIY' => 'hammer',
                                'Móveis & Decoração' => 'chair',
                                'Papelaria' => 'pen-nib',
                                'Pet Shop' => 'dog',
                                'Brinquedos' => 'gamepad',
                                'Eletrônicos' => 'tv',
                                'Lojas de Celular' => 'mobile-screen-button',
                                'Bicicletarias' => 'bicycle',
                                'Lavanderias' => 'soap',
                                'Floriculturas' => 'flower-up',
                                'Açougues' => 'drumstick-bite',
                                'Hospedagem (Hoteis)' => 'hotel',
                                'Hospedagem (Moteis)' => 'bed',
                                'Cultura/Museus' => 'monument',
                                'Pontos Turísticos' => 'camera',
                                'Mirantes/Turismo' => 'binoculars',
                                'Esportes/Estádios' => 'volleyball',
                                'Centros Esportivos' => 'medal',
                                'Lazer Aquático' => 'water'
                            ];
                        @endphp
                        @forelse($city->stats_cache['top_conveniencias'] ?? [] as $type => $count)
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 rounded-4 bg-light border d-flex align-items-center h-100 transition-all hover-white shadow-sm poi-card-clickable" onclick="showPOICategory('{{ $type }}')">
                                    <div class="rounded-3 bg-primary-10 p-2 me-3 text-primary shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-{{ $iconMap[$type] ?? 'location-dot' }}" style="font-size: 16px;"></i>
                                    </div>
                                    <div class="text-truncate">
                                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">{{ $type }}</div>
                                        <div class="fw-bold h6 mb-0">{{ $count }} <small class="text-muted fw-normal">unid.</small></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12"><p class="text-muted small">Ainda coletando dados detalhados de infraestrutura...</p></div>
                        @endforelse
                    </div>

                    <div class="mt-4 text-center">
                        <p class="small text-muted italic">
                            <i class="fa-solid fa-circle-info me-1"></i> 
                            Estes dados são parciais e baseados na cobertura atual da nossa rede. 
                            <strong>Quanto mais buscas por CEP forem realizadas na cidade, maior será a precisão desta análise.</strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Relatórios Recentes e Rankings -->
            <div class="col-lg-4">
                <div class="card-pro">
                    <h3 class="fw-black h5 mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> CEPs Recentes
                    </h3>
                    <div class="space-y-3">
                        @forelse($recentReports as $rep)
                            <a href="{{ url('/cep/' . $rep->cep) }}" class="report-link mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">{{ $rep->bairro ?: 'Centro' }}</div>
                                        <div class="small text-muted">CEP {{ $rep->cep }}</div>
                                    </div>
                                    <div class="badge bg-indigo-100 text-indigo-700 rounded-pill">
                                        {{ $rep->general_score }} pts
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-muted small">Nenhum CEP mapeado nesta cidade ainda.</p>
                        @endforelse
                    </div>
                    
                    @if($city->ibge_code)
                        <hr class="my-4">
                        <div class="p-3 rounded-4 bg-light border">
                            <h4 class="h6 fw-bold mb-2 text-uppercase small text-muted">Dados Oficiais (IBGE)</h4>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small">IDHM:</span>
                                <span class="small fw-bold">{{ $city->idhm ?: 'N/A' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="small">Saneamento:</span>
                                <span class="small fw-bold">{{ $city->sanitation_rate }}%</span>
                            </div>
                        </div>
                    @endif

                    <!-- Radar de Atributos Municipais (Novo) -->
                    <div class="mt-4 p-4 rounded-4 bg-dark text-white shadow-pro">
                        <h4 class="h6 fw-bold mb-4 text-uppercase small opacity-75 d-flex align-items-center">
                            <i class="fa-solid fa-microscope me-2 text-primary"></i> 
                            Análise Técnica Municipal
                        </h4>
                        
                        @php
                            $radar = $city->stats_cache['radar'] ?? [];
                        @endphp

                        <div class="space-y-4">
                            @foreach([
                                'Segurança' => ['val' => $radar['safety'] ?? 50, 'icon' => 'shield-halved', 'color' => 'success'],
                                'Caminhabilidade' => ['val' => $radar['walkability'] ?? 50, 'icon' => 'person-walking', 'color' => 'info'],
                                'Qualidade do Ar' => ['val' => $radar['air_quality'] ?? 50, 'icon' => 'wind', 'color' => 'warning'],
                                'Saneamento' => ['val' => $radar['sanitation'] ?? 50, 'icon' => 'droplet', 'color' => 'primary']
                            ] as $label => $data)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><i class="fa-solid fa-{{ $data['icon'] }} me-2 text-{{ $data['color'] }}"></i> {{ $label }}</span>
                                        <span class="fw-bold">{{ $data['val'] }}%</span>
                                    </div>
                                    <div class="progress bg-white-10" style="height: 4px;">
                                        <div class="progress-bar bg-{{ $data['color'] }}" style="width: {{ $data['val'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-white-50 mt-3 mb-0" style="font-size: 10px; line-height: 1.4;">
                            * Dados baseados em mapeamentos georreferenciados proprietários corrigidos por API técnica.
                        </p>
                    </div>

                    <!-- Search CTA -->
                    <div class="mt-4 p-4 rounded-4 border bg-gradient text-center" style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white;">
                        <i class="fa-solid fa-magnifying-glass-location h2 mb-3"></i>
                        <h5 class="fw-black mb-2">Não achou seu bairro?</h5>
                        <p class="small opacity-90 mb-3">Consulte seu CEP agora e ajude a mapear {{ $city->name }}!</p>
                        <a href="/" class="btn btn-white w-100 rounded-pill fw-bold text-primary py-2">Consultar CEP Grátis</a>
                    </div>
                </div>
            </div>

            <!-- Território Mapeado (Ranking Completo) -->
            <div class="col-12">
                <div class="card-pro bg-white" style="background: white !important;">
                    
                    @php
                        $podium = array_slice($city->stats_cache['neighborhood_list'] ?? [], 0, 3);
                        $rest = array_slice($city->stats_cache['neighborhood_list'] ?? [], 3);
                    @endphp

                    @if(!empty($podium))
                        <!-- Podio de Bairros (Destaques) -->
                        <div class="row g-4 mb-5">
                            <div class="col-12 text-center mb-2">
                                <span class="badge bg-primary-10 text-primary px-3 py-2 rounded-pill fw-bold text-uppercase" style="font-size: 11px;">🏆 O Podio da Cidade</span>
                            </div>
                            @foreach($podium as $index => $item)
                                <div class="col-md-4">
                                    <div class="p-4 rounded-5 border text-center transition-all hover-white shadow-pro h-100 position-relative bg-white">
                                        <div class="position-absolute top-0 start-50 translate-middle">
                                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center border border-white border-4 shadow" style="width: 50px; height: 50px; font-size: 20px;">
                                                @if($index == 0) 🥇 @elseif($index == 1) 🥈 @else 🥉 @endif
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <h4 class="fw-black mb-1 h5 text-truncate px-2">{{ $item['name'] }}</h4>
                                            <div class="text-muted small mb-3">
                                                @if($item['mapped'] ?? true)
                                                    #{{ $index + 1 }} do Ranking
                                                @else
                                                    Descoberto via Satélite
                                                @endif
                                            </div>
                                            <div class="h3 fw-black {{ ($item['avg_score'] ?? 0) >= 80 ? 'text-success' : 'text-primary' }} mb-0">
                                                {{ $item['avg_score'] ?: '--' }} <span style="font-size: 12px; opacity: 0.6;">pts</span>
                                            </div>
                                            <div class="small fw-bold opacity-50 text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Escore Territorial</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr class="opacity-10 my-5">
                    @endif

                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <h3 class="fw-black mb-1">
                                <i class="fa-solid fa-ranking-star me-2 text-primary"></i> 
                                Território Mapeado e em Descoberta ({{ $city->name }})
                            </h3>
                            <p class="text-muted mb-0">
                                Veja o panorama completo dos bairros identificados em nosso ecossistema.
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-light text-dark border p-2 px-3 rounded-pill">
                                <i class="fa-solid fa-sync fa-spin me-2 text-primary"></i> {{ count($city->stats_cache['neighborhood_list'] ?? []) }} Bairros Identificados
                            </span>
                        </div>
                    </div>

                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
                        @forelse($rest ?? [] as $item)
                            <div class="col">
                                <div class="p-3 h-100 rounded-4 bg-light border transition-all hover-white shadow-sm" style="cursor: default;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <i class="fa-solid fa-location-dot {{ ($item['mapped'] ?? true) ? 'text-primary' : 'text-muted opacity-50' }}" style="font-size: 12px;"></i>
                                        @php
                                            $score = $item['avg_score'] ?? 0;
                                            $colorStyle = $score >= 80 
                                                ? 'background: #dcfce7; color: #16a34a;' 
                                                : ($score >= 50 ? 'background: #fff3cd; color: #856404;' : ($score > 0 ? 'background: #fee2e2; color: #dc2626;' : 'background: #f1f5f9; color: #64748b;'));
                                        @endphp
                                        <span class="badge rounded-pill px-3 py-2" style="font-size: 11px; font-weight: 800; {{ $colorStyle }}">
                                            {{ $score > 0 ? $score . ' pts' : 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="fw-bold text-truncate" title="{{ $item['name'] }}" style="font-size: 13px;">
                                        {{ $item['name'] }}
                                    </div>
                                    <div class="small text-muted" style="font-size: 10px;">
                                        @if($item['mapped'] ?? true)
                                            Escore Territorial
                                        @else
                                            Aguardando CEP
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 py-5 text-center">
                                <i class="fa-solid fa-map-location-dot fs-1 text-muted opacity-25 mb-3"></i>
                                <p class="text-muted">Ainda estamos descobrindo os limites territoriais desta cidade.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-5 mt-5 border-top bg-white">
        <div class="container text-center">
            <p class="text-muted small">&copy; {{ date('Y') }} {{ config('app.name') }} - Inteligência Territorial Gerada por IA</p>
        </div>
    </footer>

    <!-- POI Modal -->
    <div class="modal fade" id="poiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h3 class="modal-title fw-black h4 mb-0" id="poiModalTitle">Gastronomia</h3>
                        <p class="text-muted small mb-0" id="poiModalSubtitle">Estabelecimentos mapeados em {{ $city->name }}</p>
                    </div>
                    <button type="button" class="btn-close rounded-circle border shadow-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 pt-3">
                    <div id="poiLoader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2">Buscando detalhes geográficos...</p>
                    </div>
                    <div id="poiList" class="d-none">
                        <!-- Itens virão via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function showPOICategory(category) {
            const modal = new bootstrap.Modal(document.getElementById('poiModal'));
            const title = document.getElementById('poiModalTitle');
            const list = document.getElementById('poiList');
            const loader = document.getElementById('poiLoader');
            
            title.innerText = category;
            list.classList.add('d-none');
            loader.classList.remove('d-none');
            modal.show();

            try {
                const response = await fetch(`/cidade/{{ $city->slug }}/pois?category=${encodeURIComponent(category)}`);
                const data = await response.json();
                
                list.innerHTML = '';
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        const html = `
                            <div class="poi-item d-flex align-items-center">
                                <div class="rounded-circle bg-primary-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; flex-shrink: 0;">
                                    <i class="fa-solid fa-shop"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-bold text-dark text-truncate">${item.name}</div>
                                    <div class="small text-muted text-truncate">${item.street} ${item.number} ${item.neighborhood ? ' - ' + item.neighborhood : ''}</div>
                                </div>
                                ${item.phone ? `
                                    <a href="tel:${item.phone.replace(/\D/g, '')}" class="btn btn-primary btn-sm rounded-pill ms-2">
                                        <i class="fa-solid fa-phone"></i>
                                    </a>
                                ` : ''}
                                <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(item.name + ' ' + item.street + ' ' + '{{ $city->name }}')}" target="_blank" class="btn btn-outline-light border btn-sm rounded-pill text-dark ms-2">
                                    <i class="fa-solid fa-location-arrow"></i>
                                </a>
                            </div>
                        `;
                        list.insertAdjacentHTML('beforeend', html);
                    });
                } else {
                    list.innerHTML = '<div class="p-5 text-center text-muted">Nenhum detalhe adicional encontrado para esta categoria.</div>';
                }
                
                loader.classList.add('d-none');
                list.classList.remove('d-none');
            } catch (error) {
                console.error('Error fetching POIs:', error);
                list.innerHTML = '<div class="p-5 text-center text-danger">Erro ao carregar os dados. Tente novamente.</div>';
                loader.classList.add('d-none');
                list.classList.remove('d-none');
            }
        }

        // OMNISEARCH LOGIC
        function openOmnisearch() {
            const el = document.getElementById('omnisearch');
            el.style.display = 'block';
            setTimeout(() => {
                document.body.classList.add('omnisearch-active');
                document.getElementById('omni-input').focus();
            }, 10);
        }

        function closeOmnisearch(e) {
            if (e && e.key === 'Escape') {
                document.body.classList.remove('omnisearch-active');
                setTimeout(() => document.getElementById('omnisearch').style.display = 'none', 300);
            } else if (e) {
                document.body.classList.remove('omnisearch-active');
                setTimeout(() => document.getElementById('omnisearch').style.display = 'none', 300);
            }
        }

        // Shortcut Ctrl + K
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openOmnisearch();
            }
        });

        // Search Input Logic
        const omniInput = document.getElementById('omni-input');
        const omniResults = document.getElementById('omni-results');

        if (omniInput) {
            let debounceTimer;
            omniInput.addEventListener('input', (e) => {
                const q = e.target.value.trim();
                
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(async () => {
                    if (q.length < 2) {
                        omniResults.innerHTML = '<div class="p-4 text-center text-muted small uppercase fw-bold tracking-widest">Digite para buscar...</div>';
                        return;
                    }

                    try {
                        const response = await fetch(`/suggestions?q=${encodeURIComponent(q)}`);
                        const data = await response.json();
                        
                        if (data.length === 0) {
                            omniResults.innerHTML = '<div class="p-4 text-center text-muted">Nenhum resultado encontrado.</div>';
                            return;
                        }

                        omniResults.innerHTML = data.map(item => `
                            <a href="/cep/${item.cep.replace(/\D/g, '')}" class="d-flex align-items-center gap-3 p-3 text-decoration-none hover:bg-light rounded-3 transition-all border-bottom">
                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3" style="width: 40px; text-align: center;">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div>
                                    <div class="text-dark fw-bold">${item.details.road || item.details.neighborhood || 'Localização'}</div>
                                    <div class="text-muted small">${item.details.city} - ${item.details.state} • ${item.cep}</div>
                                </div>
                            </a>
                        `).join('');
                    } catch (err) {
                        console.error(err);
                    }
                }, 300);
            });
        }
    </script>
</body>
</html>
