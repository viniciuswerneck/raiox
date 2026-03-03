<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Raio-X de {{ $report->cidade }}</title>
    
    <!-- Bootstrap 5.3 & Font Awesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        :root {
            --bs-body-font-family: 'Inter', sans-serif;
            --bs-primary: #4F46E5;
            --bs-primary-rgb: 79, 70, 229;
            --accent-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }

        body { 
            background-color: #f8fafc; 
            color: #1e293b; 
            letter-spacing: -0.01em;
        }
        
        .hero-section {
            background: #0f172a;
            position: relative;
            padding: 100px 0 160px;
            overflow: hidden;
            color: white;
        }
        
        .hero-bg-img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.25;
            filter: grayscale(100%) brightness(0.5);
            z-index: 0;
        }
        
        .hero-content { position: relative; z-index: 1; }

        .card-premium {
            border: none;
            border-radius: 28px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            background: white;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .metric-card {
            min-height: 160px;
            padding: 24px;
            border: 1px solid rgba(0,0,0,0.04);
        }

        .metric-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .nav-pills-premium {
            background: #f1f5f9;
            padding: 6px;
            border-radius: 16px;
            display: flex;
            gap: 4px;
        }

        .nav-pills-premium .nav-link {
            border-radius: 12px;
            color: #64748b;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 10px 15px;
            flex: 1;
            text-align: center;
        }

        .nav-pills-premium .nav-link.active {
            background: white;
            color: var(--bs-primary);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .poi-scroll {
            max-height: 480px;
            overflow-y: auto;
            scrollbar-width: none;
            padding-right: 4px;
        }
        .poi-scroll::-webkit-scrollbar { display: none; }

        .badge-aqi { font-weight: 800; font-size: 0.7rem; letter-spacing: 0.05em; }
        
        .walk-score-badge {
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1;
        }

        #map {
            height: 540px;
            border-radius: 24px;
            z-index: 10;
        }

        .section-header {
            font-size: 0.7rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 8px;
        }

        .drop-cap::first-letter {
            float: left;
            font-size: 4rem;
            line-height: 0.7;
            padding: 0.4rem 0.8rem 0 0;
            font-weight: 900;
            color: var(--bs-primary);
            text-transform: uppercase;
        }

        .fw-black { font-weight: 900; }

        .btn-premium {
            background: var(--accent-gradient);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 16px;
            font-weight: 700;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body>

    @php
        $wiki = $report->wiki_json ?? [];
        $climate = $report->climate_json['current_weather'] ?? null;
        $aqi = $report->air_quality_index;
        $pois = $report->pois_json ?? [];
        $ibgeRaw = $report->raw_ibge_data ?? [];
        
        $mobility = array_filter($pois, fn($p) => ($p['tags']['highway'] ?? '') === 'bus_stop' || ($p['tags']['amenity'] ?? '') === 'bicycle_parking' || ($p['tags']['amenity'] ?? '') === 'fuel');
        $health_edu = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['pharmacy', 'hospital', 'school', 'bank', 'university', 'clinic', 'dentist', 'place_of_worship', 'library', 'post_office']));
        
        // Comércios: tudo que é shop ou amenities de alimentação/serviços que não entraram nas categorias acima
        $commerces = array_filter($pois, function($p) use ($mobility, $health_edu) {
            $isShop = isset($p['tags']['shop']);
            $isFood = in_array($p['tags']['amenity'] ?? '', ['restaurant', 'cafe', 'bar', 'fast_food', 'pub', 'ice_cream', 'food_court']);
            $isInOther = in_array($p['id'], array_column($mobility, 'id')) || in_array($p['id'], array_column($health_edu, 'id'));
            return ($isShop || $isFood) && !$isInOther;
        });

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
            'veterinary' => 'Veterinária', 'hairdresser' => 'Cabeleireiro', 'laundry' => 'Lavanderia'
        ];

        // AQI Labels
        $aqiClass = 'text-success'; $aqiBg = 'bg-success-subtle'; $aqiText = 'BOM';
        if($aqi > 20) { $aqiClass = 'text-warning'; $aqiBg = 'bg-warning-subtle'; $aqiText = 'MODERADO'; }
        if($aqi > 40) { $aqiClass = 'text-danger'; $aqiBg = 'bg-danger-subtle'; $aqiText = 'RUIM'; }

        // Walk Score
        $walkColor = 'text-success'; $walkBg = 'bg-success-subtle'; $walkLabel = 'Excelente Infraestrutura';
        if($report->walkability_score == 'B') { $walkColor = 'text-info'; $walkBg = 'bg-info-subtle'; $walkLabel = 'Infraestrutura Básica'; }
        if($report->walkability_score == 'C') { $walkColor = 'text-muted'; $walkBg = 'bg-light'; $walkLabel = 'Dependente de Carro'; }
    @endphp

    <!-- HEADER: Hero Section -->
    <header class="hero-section">
        @if($wiki['image'] ?? null)
            <img src="{{ $wiki['image'] }}" class="hero-bg-img" alt="{{ $report->cidade }}">
        @endif
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white opacity-75 text-decoration-none fw-bold small uppercase"><i class="fa-solid fa-house-chimney me-2"></i>Nova Busca</a></li>
                            <li class="breadcrumb-item active text-white fw-bold small uppercase" aria-current="page">Dashboard Regional</li>
                        </ol>
                    </nav>
                    <h1 class="display-2 fw-black mb-3">{{ $report->cidade }} <span class="text-primary">{{ $report->uf }}</span></h1>
                    <p class="h4 fw-medium text-white-50 mb-0 lh-base">
                        @if($report->logradouro)
                            {{ $report->logradouro }} • {{ $report->bairro }}
                        @else
                            {{ $report->bairro ?: 'Região Central' }}
                        @endif
                    </p>
                </div>
                <div class="col-lg-5 text-lg-end mt-5 mt-lg-0">
                    <div class="d-inline-flex flex-column align-items-center p-4 rounded-5" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
                        <span class="text-white-50 small fw-black text-uppercase mb-2" style="letter-spacing: 0.2em;">CEP de Referência</span>
                        <span class="display-4 font-monospace fw-black text-white mb-0">{{ $report->cep }}</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: -60px; position: relative; z-index: 10;">
        <!-- Top Metrics Row -->
        <div class="row g-4 mb-5">
            <!-- Walkability -->
            <div class="col-md-4">
                <div class="card card-premium metric-card {{ $walkBg }}">
                    <div class="d-flex justify-content-between">
                        <div class="metric-icon bg-white text-primary rounded-3 shadow-sm border border-light">
                            <i class="fa-solid fa-person-walking"></i>
                        </div>
                        <div class="walk-score-badge {{ $walkColor }}">{{ $report->walkability_score }}</div>
                    </div>
                    <div>
                        <div class="section-header mb-1">Índice de Caminhabilidade</div>
                        <h5 class="fw-bold mb-0 {{ $walkColor }}">{{ $walkLabel }}</h5>
                    </div>
                </div>
            </div>

            <!-- Climate & Air -->
            <div class="col-md-4">
                <div class="card card-premium metric-card">
                    <div class="d-flex justify-content-between">
                        <div class="metric-icon bg-amber-50 text-warning" style="background-color: #fff9db;">
                             <i class="fa-solid fa-cloud-sun"></i>
                        </div>
                        @if($climate)
                            <div class="text-end">
                                <span class="h2 fw-black mb-0">{{ round($climate['temperature']) }}°C</span>
                                <small class="d-block text-muted fw-bold">VENTO: {{ $climate['windspeed'] }}km/h</small>
                            </div>
                        @endif
                    </div>
                    <div>
                        <div class="section-header mb-1">Qualidade do Ar</div>
                        @if($aqi)
                            <span class="badge badge-aqi rounded-pill px-3 py-2 {{ $aqiBg }} {{ $aqiClass }}">{{ $aqiText }} ({{ $aqi }} AQI)</span>
                        @else
                            <span class="text-muted small">Indisponível</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Socioeconomic -->
            <div class="col-md-4">
                <div class="card card-premium metric-card bg-dark text-white">
                    <div class="d-flex justify-content-between">
                        <div class="metric-icon bg-secondary border border-secondary bg-opacity-25">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <div class="text-end">
                            <span class="h4 fw-bold mb-0 text-primary">R$ {{ number_format($report->average_income, 0, ',', '.') }}</span>
                            <small class="d-block text-white-50 fw-bold">RENDA MÉDIA</small>
                        </div>
                    </div>
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="section-header text-white-50 mb-0">Saneamento Básico</span>
                            <span class="small fw-bold">{{ $report->sanitation_rate }}%</span>
                        </div>
                        <div class="progress bg-secondary bg-opacity-50" style="height: 6px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $report->sanitation_rate }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- Left Side: Map & Address -->
            <div class="col-lg-8">
                <div class="card card-premium overflow-hidden">
                    <div class="card-body p-4 p-md-5 border-bottom border-light bg-white">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="bg-primary bg-opacity-10 p-4 rounded-4 text-primary">
                                    <i class="fa-solid fa-location-dot fa-2x"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="section-header mb-0">Ponto Central Registrado</div>
                                <h3 class="fw-black mb-0">{{ $report->logradouro ?: 'Ponto Central' }}, {{ $report->bairro ?: 'Centro' }}</h3>
                                <p class="mb-0 text-muted fw-medium">{{ $ibgeRaw['microrregiao']['nome'] ?? '' }} / {{ $report->cidade }}</p>
                            </div>
                        </div>
                    </div>
                    <div id="map"></div>
                </div>
            </div>

            <!-- Right Side: POI Profile -->
            <div class="col-lg-4">
                <div class="card card-premium p-4 h-100 bg-white">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4 text-primary me-3">
                            <i class="fa-solid fa-compass fa-xl"></i>
                        </div>
                        <div>
                            <h4 class="fw-black mb-0">Explorar Região</h4>
                            <p class="text-muted small fw-bold mb-0">RAIO DE 10KM ANALISADO</p>
                        </div>
                    </div>

                    <!-- Infrastructure Section -->
                    <div class="mb-4">
                        <span class="section-header d-block mb-3 px-1"><i class="fa-solid fa-hospital-user me-2"></i>Infraestrutura Crítica</span>
                        <div class="row g-2 mb-3">
                            @foreach(['pharmacy' => ['fa-pills', 'Farm.'], 'hospital' => ['fa-hospital', 'Hosp.'], 'school' => ['fa-graduation-cap', 'Esc.'], 'bank' => ['fa-building-columns', 'Ban.']] as $key => $info)
                                @php $cnt = count(array_filter($health_edu, fn($h) => ($h['tags']['amenity'] ?? '') === $key)); @endphp
                                <div class="col-3">
                                    <div class="p-2 bg-light rounded-4 text-center border border-white shadow-sm">
                                        <div class="h5 fw-black mb-0 text-primary">{{ $cnt }}</div>
                                        <div class="text-muted fw-bold" style="font-size: 8px; text-transform: uppercase;">{{ $info[1] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Scrollable Area for Mobility and Commerce -->
                    <div class="poi-scroll" style="max-height: 800px;">
                        <!-- Mobility -->
                        <div class="mb-4">
                            <span class="section-header d-block mb-3 px-1"><i class="fa-solid fa-bus-simple me-2"></i>Mobilidade e Transporte</span>
                            @forelse(array_slice($mobility, 0, 15) as $m)
                                @php 
                                    $mName = $m['tags']['name'] ?? $m['tags']['addr:street'] ?? 'Sem Nome';
                                    $mType = $translations[$m['tags']['highway'] ?? $m['tags']['amenity'] ?? ''] ?? 'Acesso';
                                @endphp
                                <div class="d-flex align-items-center p-3 rounded-4 mb-2 border border-light bg-light bg-opacity-25">
                                    <div class="rounded-circle bg-white shadow-sm text-primary d-flex align-items-center justify-content-center me-3" style="width: 36px; height: 36px; flex-shrink: 0;">
                                        <i class="fa-solid fa-bus-simple fa-sm"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark small text-truncate">{{ $mName }}</div>
                                        <div class="text-muted" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">{{ $mType }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center p-4 bg-light rounded-4 mb-3">
                                    <p class="text-muted small mb-0">Nenhum ponto de transporte identificado.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Commerce -->
                        <div>
                            <span class="section-header d-block mb-3 px-1"><i class="fa-solid fa-shop me-2"></i>Comércios e Serviços</span>
                            @forelse(array_slice($commerces, 0, 30) as $c)
                                <div class="p-3 rounded-4 mb-2 border border-light bg-white shadow-sm">
                                    <div class="fw-bold text-dark small text-truncate mb-1">{{ $c['tags']['name'] ?? 'Comércio Local' }}</div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-black text-uppercase" style="font-size: 9px;">{{ $translations[$c['tags']['shop'] ?? $c['tags']['amenity'] ?? ''] ?? 'Serviço' }}</span>
                                        @if(isset($c['tags']['addr:street']))
                                            <span class="text-muted fs-small fst-italic" style="font-size: 9px;">{{ $c['tags']['addr:street'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center p-4 bg-light rounded-4 text-muted small">Sem dados comerciais mapeados.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>

        @if($report->history_extract)
        @php
            $wikiSource = $wiki['source'] ?? 'cidade';
            $wikiTerm   = $wiki['term'] ?? null;
            $wikiUrl    = $wiki['desktop_url'] ?? null;
            $historyTitle = $wikiSource === 'bairro'
                ? 'História do Bairro'
                : 'História de ' . $report->cidade;
        @endphp
        <!-- LOCAL HISTORY -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card card-premium p-4 p-md-5 bg-white">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <h2 class="fw-black mb-0">
                                    <i class="fa-solid fa-scroll me-2 text-primary"></i>{{ $historyTitle }}
                                </h2>
                                <span class="badge rounded-pill px-3 py-2 fw-bold"
                                    style="font-size: 11px; letter-spacing: 0.05em; background: {{ $wikiSource === 'bairro' ? 'rgba(79,70,229,0.1)' : 'rgba(100, 116, 139, 0.1)' }}; color: {{ $wikiSource === 'bairro' ? '#4F46E5' : '#64748b' }};">
                                    <i class="fa-brands fa-wikipedia-w me-1"></i>
                                    {{ $wikiSource === 'bairro' ? 'Bairro' : 'Município' }}
                                </span>
                            </div>
                            <p class="lead drop-cap text-muted lh-lg" style="text-align: justify;">
                                {{ $report->history_extract }}
                            </p>
                            @if($wikiUrl)
                                <a href="{{ $wikiUrl }}" target="_blank" rel="noopener"
                                    class="btn btn-sm btn-outline-primary mt-2 rounded-pill px-4 fw-bold">
                                    <i class="fa-brands fa-wikipedia-w me-2"></i>Ler mais na Wikipedia
                                </a>
                            @endif
                        </div>
                        <div class="col-lg-4 d-none d-lg-block text-center">
                            <i class="fa-solid fa-landmark fa-8x text-light opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- FOOTER -->
    <footer class="bg-white py-5 border-top">
        <div class="container text-center">
            <h5 class="fw-black text-primary mb-3">{{ config('app.name') }}</h5>
            <div class="row justify-content-center g-4 mt-2">
                <div class="col-auto border-end pe-4"><small class="text-muted fw-bold">VIA-CEP</small></div>
                <div class="col-auto border-end pe-4"><small class="text-muted fw-bold">OSM DATA</small></div>
                <div class="col-auto border-end pe-4"><small class="text-muted fw-bold">OPEN-METEO</small></div>
                <div class="col-auto"><small class="text-muted fw-bold">IBGE INFRA</small></div>
            </div>
            <p class="mt-5 text-muted small fw-medium">© {{ date('Y') }} - Desenvolvido para Análise Regional B2C.</p>
        </div>
    </footer>

    <!-- Leaflet Configuration -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = {{ $report->lat }};
            const lng = {{ $report->lng }};
            const pois = @json($report->pois_json ?? []);
            const translations = @json($translations);

            // Light Map Style
            const map = L.map('map', { 
                scrollWheelZoom: false,
                attributionControl: false
            }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {}).addTo(map);

            // Custom Main Icon
            const mainIcon = L.divIcon({
                className: 'custom-pin',
                html: `<div style="width: 20px; height: 20px; background-color: #4F46E5; border: 4px solid white; border-radius: 50%; box-shadow: 0 0 10px rgba(0,0,0,0.2);"></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            L.marker([lat, lng], { icon: mainIcon }).addTo(map);

            pois.forEach(poi => {
                if (!poi.lat || !poi.lon) return;
                const rawType = poi.tags.amenity || poi.tags.shop || poi.tags.highway || 'Comércio';
                const type = translations[rawType] || rawType;
                
                let color = '#f97316';
                if (['bus_stop', 'fuel'].includes(rawType)) color = '#4F46E5';
                if (['pharmacy', 'hospital', 'clinic'].includes(rawType)) color = '#ef4444';
                if (['school', 'university'].includes(rawType)) color = '#8b5cf6';

                L.circleMarker([poi.lat, poi.lon], {
                    color: 'white',
                    fillColor: color,
                    fillOpacity: 0.8,
                    radius: 5,
                    weight: 2
                }).addTo(map).bindPopup(`<div class="p-2"><b class="d-block mb-1 text-dark">${poi.tags.name || 'Local'}</b><span class="text-muted small fw-bold text-uppercase">${type}</span></div>`);
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
