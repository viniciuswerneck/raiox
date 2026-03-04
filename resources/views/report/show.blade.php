<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Raio-X de {{ $report->cidade }}</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
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

        h1, h2, h3, h4, .font-heading {
            font-family: var(--font-heading);
            font-weight: 800;
        }

        /* Hero Imersivo */
        .hero-section {
            position: relative;
            min-height: 480px;
            background-color: var(--dark);
            color: white;
            padding: 80px 0 140px;
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
            opacity: 0.5;
            z-index: 0;
            transition: transform 10s ease;
        }

        .hero-section:hover .hero-bg-img {
            transform: scale(1.1);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .cep-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 99px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        /* Dashboard Bento Grid Style */
        .dashboard-container {
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }

        .card-pro {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: var(--card-radius);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            padding: 24px;
        }

        .card-pro:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 255, 255, 0.8);
        }

        .metric-icon-pro {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1);
        }

        /* Status Pills */
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Mapa e POIs */
        #map-container {
            height: 520px;
            border-radius: var(--card-radius);
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 15px 35px -10px rgba(15, 23, 42, 0.15);
        }

        #map { height: 100%; width: 100%; }

        .poi-drawer {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
            scrollbar-width: thin;
        }

        .poi-item {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #f1f5f9;
            transition: transform 0.2s ease;
        }

        .poi-item:hover {
            transform: scale(1.02);
            border-color: var(--primary);
        }

        /* Typography Decorations */
        .editorial-text {
            line-height: 1.8;
            font-size: 1.1rem;
            color: #475569;
            text-align: justify;
        }

        .drop-cap::first-letter {
            float: left;
            font-size: 4.5rem;
            line-height: 0.8;
            padding-right: 12px;
            font-weight: 900;
            color: var(--primary);
            font-family: var(--font-heading);
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reveal { animation: fadeInUp 0.8s ease backwards; }

        /* Categorias do Mapa */
        .map-category-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 99px;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .map-category-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 8px 15px -3px rgba(99, 102, 241, 0.3);
        }

        /* Print Adjustments */
        @media print {
            .no-print, .btn, .cep-badge, .breadcrumb, footer, .map-category-btn, #map-container .leaflet-control-container {
                display: none !important;
            }
            .hero-section {
                padding: 40px 0 !important;
                min-height: auto !important;
                background-color: #0f172a !important;
                -webkit-print-color-adjust: exact;
            }
            .card-pro {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
                background: white !important;
            }
            body { background: white !important; }
            .dashboard-container { margin-top: 20px !important; }
            .hero-bg-img { display: none !important; }
            .hero-bg-overlay { background: #0f172a !important; }
        }
    </style>
</head>
<body id="pdf-content">

    @php
        $wiki = $report->wiki_json ?? [];
        $climate = $report->climate_json['current_weather'] ?? null;
        $aqi = $report->air_quality_index;
        $pois = $report->pois_json ?? [];
        $ibgeRaw = $report->raw_ibge_data ?? [];
        
        $translations = [
            'supermarket' => 'Supermercado', 'restaurant' => 'Restaurante', 'pharmacy' => 'Farmácia', 'cafe' => 'Café/Padaria',
            'bakery' => 'Padaria', 'school' => 'Escola', 'bank' => 'Banco', 'hospital' => 'Hospital',
            'bus_stop' => 'Parada de Ônibus', 'bicycle_parking' => 'Estac. Bicicletas', 'convenience' => 'Conveniência',
            'clothes' => 'Loja de Roupas', 'mall' => 'Shopping', 'fuel' => 'Posto Combustível', 'bar' => 'Bar/Pub',
            'fast_food' => 'Fast Food', 'university' => 'Universidade', 'clinic' => 'Clínica', 'dentist' => 'Dentista',
            'pub' => 'Pub/Bar', 'beauty' => 'Salão de Beleza', 'department_store' => 'Loja de Depto',
            'place_of_worship' => 'Igreja/Templo', 'cinema' => 'Cinema', 'theatre' => 'Teatro',
            'library' => 'Biblioteca', 'post_office' => 'Correios', 'park' => 'Parque/Lazer',
            'gym' => 'Academia', 'sports_centre' => 'Centro Esportivo', 'playground' => 'Playground',
            'ice_cream' => 'Sorveteria', 'food_court' => 'Praça de Alimentação', 'hardware' => 'Material de Construção',
            'electronics' => 'Eletrônicos', 'furniture' => 'Móveis', 'optician' => 'Ótica', 'books' => 'Livraria',
            'car_repair' => 'Oficina Mecânica', 'car_wash' => 'Lava Rápido', 'pet_shop' => 'Pet Shop',
            'veterinary' => 'Veterinária', 'hairdresser' => 'Cabeleireiro', 'laundry' => 'Lavanderia',
            'police' => 'Polícia / Delegacia', 'fire_station' => 'Bombeiros', 'townhall' => 'Prefeitura / Poupatempo',
            'public_service' => 'Serviço Público', 'marketplace' => 'Feira Livre / Mercado', 'monument' => 'Monumento Histórico',
            'memorial' => 'Memorial', 'museum' => 'Museu', 'arts_centre' => 'Centro Cultural', 'theatre' => 'Teatro',
            'attraction' => 'Atração Turística', 'artwork' => 'Obra de Arte / Estátua', 'gallery' => 'Galeria de Arte',
            'station' => 'Estação de Trem / Metrô', 'kindergarten' => 'Creche / Instituição Infantil',
            'childcare' => 'Espaço Infantil', 'doctors' => 'Unidade Básica (UBS) / Médicos',
            'doityourself' => 'Ferramentas/Construção', 'shoes' => 'Sapatos'
        ];

        // Categorização Organizada
        $health = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['pharmacy', 'hospital', 'clinic', 'dentist', 'doctors', 'veterinary']));
        
        $education_faith = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['school', 'university', 'kindergarten', 'childcare', 'place_of_worship']));
        
        $commerce = array_filter($pois, fn($p) => isset($p['tags']['shop']) || in_array($p['tags']['amenity'] ?? '', ['fuel', 'restaurant', 'cafe', 'fast_food', 'bakery', 'bar', 'pub', 'marketplace', 'bank']));
        
        $services_leisure = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['police', 'fire_station', 'post_office', 'townhall', 'public_service', 'cinema', 'theatre', 'arts_centre', 'library']) || in_array($p['tags']['leisure'] ?? '', ['park', 'gym', 'sports_centre', 'playground']) || isset($p['tags']['historic']));

        // Theme colors for score
        $walkColor = match($report->walkability_score) {
            'A' => '#10b981', 'B' => '#6366f1', default => '#64748b'
        };
        $walkLabel = match($report->walkability_score) {
            'A' => 'Excelente Escopo', 'B' => 'Funcionalidade Básica', default => 'Dependente de Veículo'
        };

        // AQI Logic
        $aqiRes = [
            'level' => 'Bom', 'color' => 'success', 'desc' => 'Qualidade ideal para atividades ao ar livre.'
        ];
        if($aqi > 20) $aqiRes = ['level' => 'Moderado', 'color' => 'warning', 'desc' => 'Pessoas sensíveis podem ser afetadas.'];
        if($aqi > 40) $aqiRes = ['level' => 'Ruim', 'color' => 'danger', 'desc' => 'Risco moderado para toda a população.'];
    @endphp

    <!-- HERO SECTION -->
    <section class="hero-section">
        @if($wiki['image'] ?? null)
            <img src="{{ $wiki['image'] }}" class="hero-bg-img" alt="{{ $report->cidade }}">
        @endif
        <div class="hero-bg-overlay"></div>
        
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-8 reveal">
                    <div class="cep-badge no-print">
                        <i class="fa-solid fa-location-crosshairs text-primary"></i>
                        @php $fCep = preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $report->cep); @endphp
                        <span>REGIÃO DO CEP {{ $fCep }}</span>
                    </div>
                    <h1 class="display-1 text-white mb-2">
                        {{ $report->cidade }} <span style="color: var(--primary)">{{ $report->uf }}</span>
                    </h1>
                    @php
                        $formattedCep = preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $report->cep);
                    @endphp
                    <p class="h3 fw-light text-white-50 opacity-75 text-nowrap">
                        {{ $report->logradouro ?: $report->bairro }} • <span class="fw-medium text-white">CEP {{ $formattedCep }}</span>
                    </p>
                    <div class="mt-5 d-flex gap-3 no-print">
                        <a href="{{ route('home') }}" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold">
                            <i class="fa-solid fa-arrow-left me-2"></i>Nova Busca
                        </a>
                        <button id="download-pdf" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-lg shadow-primary">
                            <i class="fa-solid fa-file-pdf me-2"></i>Baixar Relatório PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container dashboard-container">
        <!-- TOP METRICS GRID -->
        <div class="row g-4 mb-5">
            <!-- Walkability (Bento Large) -->
            <div class="col-lg-4 reveal" style="animation-delay: 0.1s">
                <div class="card-pro d-flex flex-column justify-content-between overflow-hidden position-relative">
                    <div>
                        <div class="metric-icon-pro bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-person-walking"></i>
                        </div>
                        <h4 class="mb-1">Caminhabilidade</h4>
                        <p class="text-muted small">Mobilidade ativa e acesso peatonal.</p>
                    </div>
                    <div class="text-center py-4">
                        <div style="font-size: 6rem; line-height: 1; font-weight: 900; color: {{ $walkColor }}">
                            {{ $report->walkability_score }}
                        </div>
                        <span class="status-pill" style="background: {{ $walkColor }}15; color: {{ $walkColor }}">
                            {{ $walkLabel }}
                        </span>
                    </div>
                    <div style="position: absolute; bottom: -20px; right: -20px; opacity: 0.05; font-size: 8rem;">
                        <i class="fa-solid fa-shoe-prints"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="row g-4 h-100">
                    <!-- Air & Climate -->
                    <div class="col-md-6 reveal" style="animation-delay: 0.2s">
                        <div class="card-pro">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="metric-icon-pro bg-amber-100 text-amber-600">
                                    <i class="fa-solid fa-wind"></i>
                                </div>
                                @if($climate)
                                    <div class="text-end">
                                        <div class="h2 mb-0 fw-black text-dark">{{ round($climate['temperature']) }}°C</div>
                                        <div class="badge bg-light text-muted fw-bold">Tempo Real</div>
                                    </div>
                                @endif
                            </div>
                            <h5 class="fw-bold">Qualidade do Ar</h5>
                            <div class="mt-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="h4 mb-0 fw-bold">{{ $aqi }}</span>
                                    <span class="status-pill bg-{{ $aqiRes['color'] }} text-white">{{ $aqiRes['level'] }}</span>
                                </div>
                                <p class="small text-muted mb-0 leading-tight">{{ $aqiRes['desc'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Economics -->
                    <div class="col-md-6 reveal" style="animation-delay: 0.3s">
                        <div class="card-pro bg-dark text-white border-0 shadow-xl" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="metric-icon-pro bg-white bg-opacity-10 text-primary">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <div class="text-end">
                                    <div class="h2 mb-0 fw-black text-primary">R${{ number_format($report->average_income, 0, ',', '.') }}</div>
                                    <div class="small fw-bold opacity-50 uppercase">Renda Média</div>
                                </div>
                            </div>
                            <h5 class="fw-bold">Saneamento & Infra</h5>
                            <div class="mt-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small opacity-75">Cobertura de Esgoto</span>
                                    <span class="small fw-bold">{{ $report->sanitation_rate }}%</span>
                                </div>
                                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                                    <div class="progress-bar bg-primary" style="width:{{ $report->sanitation_rate }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Safety (Small) -->
                    @php
                        $sColor = match($report->safety_level) {
                            'ALTA' => 'success', 'MODERADO' => 'warning', default => 'danger'
                        };
                    @endphp
                    <div class="col-12 reveal" style="animation-delay: 0.4s">
                        <div class="card-pro d-flex align-items-center justify-content-between p-3" style="background: var(--glass);">
                            <div class="d-flex align-items-center gap-3">
                                <div class="metric-icon-pro bg-{{ $sColor }}-subtle text-{{ $sColor }} mb-0">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">Índice de Segurança</h6>
                                    <span class="text-muted small">{{ $report->safety_description ?: 'Baseado em dados estatísticos regionais.' }}</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $sColor }} rounded-pill px-3 py-2 fw-black" style="font-size: 0.9rem;">
                                    {{ $report->safety_level ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MIDDLE SECTION: MAP & INFRASTRUCTURE -->
        <div class="row g-4 mb-5">
            <div class="col-xl-8">
                <div id="map-print-section" class="card-pro p-0 overflow-hidden bg-white border-0 shadow-lg" style="height: 520px;">
                    <div class="p-4 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 bg-white border-bottom no-print">
                        <div>
                            <h4 class="mb-0">Mapeamento Territorial</h4>
                            <p class="text-muted small mb-0">Visualização interativa de pontos próximos ao CEP.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="map-category-btn active" data-filter="all"><i class="fa-solid fa-layer-group"></i>Tudo</span>
                            <span class="map-category-btn" data-filter="saude"><i class="fa-solid fa-house-medical text-success"></i>Saúde/Farmácias</span>
                            <span class="map-category-btn" data-filter="ensino"><i class="fa-solid fa-graduation-cap text-primary"></i>Educação/Templos</span>
                            <span class="map-category-btn" data-filter="comercio"><i class="fa-solid fa-store text-amber-600"></i>Comércio</span>
                            <span class="map-category-btn" data-filter="servicos"><i class="fa-solid fa-landmark-flag text-dark"></i>Serviços/Lazer</span>
                        </div>
                    </div>
                    <div id="map-container" style="height: 440px; position: relative;">
                        <!-- Custom Map Style Controls -->
                        <div class="position-absolute d-flex gap-2 no-print" style="top: 15px; right: 15px; z-index: 1000;">
                            <button class="btn btn-light btn-sm fw-bold shadow-sm border map-style-btn" data-style="clara"><i class="fa-regular fa-sun text-warning me-1"></i>Clara</button>
                            <button class="btn btn-dark btn-sm fw-bold shadow-sm border map-style-btn" data-style="escura"><i class="fa-solid fa-moon text-light me-1"></i>Escura</button>
                            <button class="btn btn-primary btn-sm fw-bold shadow-sm border map-style-btn" data-style="satelite"><i class="fa-solid fa-satellite text-white me-1"></i>Satélite</button>
                        </div>
                        
                        <div id="map" style="height: 100%;"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card-pro h-100 d-flex flex-column">
                    <div class="mb-4 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-black">Infraestrutura</h5>
                        <div class="text-primary fw-bold small">RAIO 10KM</div>
                    </div>
                    
                    <!-- Quick Stats Grid -->
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="card p-2 border-0 bg-success bg-opacity-10 text-center rounded-4">
                                <div class="h5 fw-black text-success mb-0">{{ count($health) }}</div>
                                <div class="text-success opacity-75" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">Saúde & Fármacias</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-2 border-0 bg-primary bg-opacity-10 text-center rounded-4">
                                <div class="h5 fw-black text-primary mb-0">{{ count($education_faith) }}</div>
                                <div class="text-primary opacity-75" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">Educação/Apoio</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-2 border-0 bg-amber-100 text-center rounded-4">
                                <div class="h5 fw-black text-amber-600 mb-0">{{ count($commerce) }}</div>
                                <div class="text-amber-600 opacity-75" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">Comércio Local</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-2 border-0 bg-dark bg-opacity-10 text-center rounded-4">
                                <div class="h5 fw-black text-dark mb-0">{{ count($services_leisure) }}</div>
                                <div class="text-dark opacity-75" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">Lazer & Serviços</div>
                            </div>
                        </div>
                    </div>

                    <div class="poi-drawer flex-grow-1" style="max-height: 320px;">
                        <!-- Escolas e Apoio Comunitário -->
                        @if(count($education_faith) > 0)
                            <div class="mb-3">
                                <h6 class="text-muted fw-bold mb-2 small uppercase tracking-tighter">Ensino & Templos</h6>
                                @foreach(array_slice($education_faith, 0, 4) as $p)
                                    @php $isSchool = in_array($p['tags']['amenity'] ?? '', ['school', 'university', 'kindergarten', 'childcare']); @endphp
                                    <div class="poi-item d-flex align-items-center mb-2 px-3 py-2 border-0 shadow-sm" style="background: white;">
                                        <i class="fa-solid @if($isSchool) fa-graduation-cap @else fa-church @endif text-primary opacity-50 me-3"></i>
                                        <div class="text-truncate small fw-bold text-dark">{{ $p['tags']['name'] ?? ($isSchool ? 'Instituição de Ensino/Creche' : 'Templo/Igreja') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Comércio e Lojas -->
                        @if(count($commerce) > 0)
                            <div class="mb-3">
                                <h6 class="text-muted fw-bold mb-2 small uppercase tracking-tighter">Comércio & Conveniência</h6>
                                @foreach(array_slice($commerce, 0, 4) as $p)
                                    @php 
                                        $isFood = in_array($p['tags']['amenity'] ?? '', ['restaurant', 'cafe', 'fast_food', 'bakery']); 
                                        $isFuel = ($p['tags']['amenity'] ?? '') === 'fuel';
                                    @endphp
                                    <div class="poi-item d-flex align-items-center mb-2 px-3 py-2 border-0 shadow-sm" style="background: white;">
                                        <i class="fa-solid @if($isFood) fa-utensils text-amber-600 @elseif($isFuel) fa-gas-pump text-dark @else fa-store text-amber-600 @endif opacity-50 me-3"></i>
                                        <div class="text-truncate small fw-bold text-dark">{{ $p['tags']['name'] ?? ($isFuel ? 'Posto de Gasolina' : 'Comércio') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Saúde Total -->
                        @if(count($health) > 0)
                            <div class="mb-3">
                                <h6 class="text-muted fw-bold mb-2 small uppercase tracking-tighter">Hospitais, Clínicas e Farmácias</h6>
                                @foreach(array_slice($health, 0, 4) as $p)
                                    @php $isPharmacy = ($p['tags']['amenity'] ?? '') === 'pharmacy'; @endphp
                                    <div class="poi-item d-flex align-items-center mb-2 px-3 py-2 border-0 shadow-sm" style="background: white;">
                                        <i class="fa-solid @if($isPharmacy) fa-pills @else fa-house-medical @endif text-success opacity-50 me-3"></i>
                                        <div class="text-truncate small fw-bold text-dark">{{ $p['tags']['name'] ?? 'Unidade de Saúde' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- HISTORY SECTION -->
        @if($report->history_extract)
            <div class="row mb-5 reveal">
                <div class="col-12">
                    <div class="card-pro p-5 border-0 shadow-lg overflow-hidden position-relative">
                        <div class="row align-items-center">
                            <div class="col-lg-8 position-relative" style="z-index: 2">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="bg-primary text-white p-3 rounded-4 shadow-sm">
                                        <i class="fa-solid fa-book-open fa-lg"></i>
                                    </div>
                                    <h2 class="mb-0">Legado e Cultura Regional</h2>
                                </div>
                                <div class="editorial-text drop-cap mb-4">
                                    {{ $report->history_extract }}
                                </div>
                                @if($wiki['desktop_url'] ?? null)
                                    <a href="{{ $wiki['desktop_url'] }}" target="_blank" class="btn btn-dark rounded-pill px-4 py-2 fw-bold text-uppercase no-print" style="font-size: 12px;">
                                        <i class="fa-brands fa-wikipedia-w me-2"></i>Ver Fonte Original
                                    </a>
                                @endif
                            </div>
                            <div class="col-lg-4 d-none d-lg-block">
                                <div class="position-relative">
                                    @if($wiki['image'] ?? null)
                                        <img src="{{ $wiki['image'] }}" class="img-fluid rounded-4 shadow-2xl border-4 border-white rotate-2" alt="História">
                                    @else
                                        <div class="bg-light p-5 rounded-4 text-center">
                                            <i class="fa-solid fa-landmark fa-8x opacity-10"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- FOOTER -->
    <footer class="bg-dark text-white-50 py-5 mt-5">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-md-4 text-center text-md-start">
                    <h3 class="text-white mb-1">{{ config('app.name') }}</h3>
                    <p class="small mb-0 opacity-50">Inteligência de Dados Territoriais</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <span class="small fw-bold border-bottom border-secondary">IBGE</span>
                        <span class="small fw-bold border-bottom border-secondary">OSM</span>
                        <span class="small fw-bold border-bottom border-secondary">GOOGLE AI</span>
                    </div>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <p class="small mb-0 fw-bold">© {{ date('Y') }} - Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = {{ $report->lat }};
            const lng = {{ $report->lng }};
            const pois = @json($report->pois_json ?? []);
            const translations = @json($translations);

            // Base Map Layers
            const baseLayers = {
                "Clara": L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 19 }),
                "Escura": L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 19 }),
                "Satélite": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 })
            };

            const map = L.map('map', { 
                scrollWheelZoom: false,
                attributionControl: false,
                zoomControl: true,
                layers: [baseLayers["Clara"]]
            }).setView([lat, lng], 15);

            // Custom Map Style Controls
            let currentMapStyle = 'clara';
            document.querySelectorAll('.map-style-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const style = this.getAttribute('data-style');
                    if (style === currentMapStyle) return;

                    // Remove current layer
                    if (currentMapStyle === 'clara') map.removeLayer(baseLayers["Clara"]);
                    if (currentMapStyle === 'escura') map.removeLayer(baseLayers["Escura"]);
                    if (currentMapStyle === 'satelite') map.removeLayer(baseLayers["Satélite"]);

                    // Add new layer
                    if (style === 'clara') baseLayers["Clara"].addTo(map);
                    if (style === 'escura') baseLayers["Escura"].addTo(map);
                    if (style === 'satelite') baseLayers["Satélite"].addTo(map);
                    
                    currentMapStyle = style;
                });
            });

            // Custom Main Marker
            const pulseIcon = L.divIcon({
                className: 'main-pulse-pin',
                html: `<div style="position:relative; width:40px; height:40px;">
                    <div style="position:absolute; width:100%; height:100%; background:rgba(99, 102, 241, 0.4); border-radius:50%; animation:pulse 2s infinite;"></div>
                    <div style="position:absolute; width:12px; height:12px; background:#4F46E5; border:3px solid white; border-radius:50%; top:14px; left:14px; box-shadow:0 0 10px rgba(0,0,0,0.3);"></div>
                </div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });

            L.marker([lat, lng], { icon: pulseIcon }).addTo(map);

            const poiLayers = L.layerGroup().addTo(map);
            const poiData = [];

            // Filterable POIs
            pois.forEach(poi => {
                if (!poi.lat || !poi.lon) return;
                const rawType = poi.tags.amenity || poi.tags.shop || poi.tags.highway || poi.tags.historic || 'Comércio';
                const type = translations[rawType] || rawType;
                
                let color = '#64748b'; // slate (default)
                let category = 'comercio';

                // Categorias para o Mapa
                if (['hospital', 'pharmacy', 'clinic', 'dentist', 'doctors', 'veterinary'].includes(rawType)) {
                    color = '#10b981'; // success
                    category = 'saude';
                } else if (['school', 'university', 'kindergarten', 'childcare', 'place_of_worship'].includes(rawType)) {
                    color = '#6366f1'; // primary
                    category = 'ensino';
                } else if (['police', 'fire_station', 'bank', 'post_office', 'townhall', 'public_service', 'library', 'arts_centre', 'museum', 'theatre', 'cinema', 'park', 'playground', 'sports_centre', 'gym'].includes(rawType) || poi.tags.historic) {
                    color = '#0f172a'; // dark
                    category = 'servicos';
                } else {
                    color = '#d97706'; // amber-600
                    category = 'comercio'; // fuel, restaurant, cafe, shop, bakery, marketplace, fast_food, etc
                }

                const marker = L.circleMarker([poi.lat, poi.lon], {
                    color: 'white',
                    fillColor: color,
                    fillOpacity: 0.9,
                    radius: 7,
                    weight: 2
                }).bindPopup(`
                    <div class="p-2">
                        <div class="fw-bold mb-1 text-dark">${poi.tags.name || 'Local Estabelecido'}</div>
                        <div class="badge bg-light text-primary text-uppercase" style="font-size: 10px">${type}</div>
                    </div>
                `);

                poiData.push({ marker, category });
                marker.addTo(poiLayers);
            });

            // Filter Logic
            document.querySelectorAll('.map-category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    document.querySelectorAll('.map-category-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    poiLayers.clearLayers();
                    poiData.forEach(item => {
                        if (filter === 'all' || item.category === filter) {
                            item.marker.addTo(poiLayers);
                        }
                    });
                });
            });

            // PDF Export Logic
            document.getElementById('download-pdf').addEventListener('click', function() {
                const btn = this;
                const originalText = btn.innerHTML;
                const noPrintElements = document.querySelectorAll('.no-print');
                
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Gerando PDF...';
                btn.disabled = true;

                // Oculta elementos que não devem sair no PDF
                noPrintElements.forEach(el => el.style.visibility = 'hidden');

                const opt = {
                    margin:       [10, 5, 10, 5],
                    filename:     `Relatorio-RaioX-${@json($report->cidade)}-${@json($report->cep)}.pdf`,
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { 
                        scale: 2,
                        useCORS: true, 
                        logging: false,
                        letterRendering: true
                    },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
                };

                html2pdf().set(opt).from(document.body).save().then(() => {
                    // Restaura a visibilidade
                    noPrintElements.forEach(el => el.style.visibility = 'visible');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        });
    </script>
    
    <style>
        @keyframes pulse {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
