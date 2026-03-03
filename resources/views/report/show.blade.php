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
        }

        body { background-color: #f3f4f6; color: #1f2937; }
        
        .hero-section {
            background-color: #0f172a;
            position: relative;
            padding: 80px 0 140px;
            overflow: hidden;
            color: white;
        }
        
        .hero-bg-img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.35;
            z-index: 0;
        }
        
        .hero-content { position: relative; z-index: 1; }

        .card-premium {
            border: none;
            border-radius: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            background: white;
        }

        .metric-card {
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 24px;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .poi-scroll {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .poi-scroll::-webkit-scrollbar { width: 4px; }
        .poi-scroll::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

        .badge-aqi { font-weight: 800; font-size: 0.7rem; letter-spacing: 0.05em; border: 1px solid currentColor; }
        
        .walk-score-badge {
            font-size: 2rem;
            font-weight: 900;
        }

        #map {
            height: 500px;
            border-radius: 20px;
            z-index: 10;
        }

        .section-header {
            font-size: 0.65rem;
            font-weight: 900;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 12px;
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
        
        $commerces = array_filter($pois, fn($p) => isset($p['tags']['shop']) || in_array($p['tags']['amenity'] ?? '', ['restaurant', 'cafe', 'bar', 'fast_food', 'pub']));
        $mobility = array_filter($pois, fn($p) => ($p['tags']['highway'] ?? '') === 'bus_stop' || ($p['tags']['amenity'] ?? '') === 'bicycle_parking' || ($p['tags']['amenity'] ?? '') === 'fuel');
        $health_edu = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['pharmacy', 'hospital', 'school', 'bank', 'university', 'clinic', 'dentist', 'place_of_worship', 'library', 'post_office']));

        $translations = [
            'supermarket' => 'Supermercado', 'restaurant' => 'Restaurante', 'pharmacy' => 'Farmácia', 'cafe' => 'Café/Padaria',
            'bakery' => 'Padaria', 'school' => 'Escola', 'bank' => 'Banco', 'hospital' => 'Hospital',
            'bus_stop' => 'Parada de Ônibus', 'bicycle_parking' => 'Estac. Bicicletas', 'convenience' => 'Conveniência',
            'clothes' => 'Loja de Roupas', 'mall' => 'Shopping', 'fuel' => 'Posto Combustível', 'bar' => 'Bar/Pub',
            'fast_food' => 'Fast Food', 'university' => 'Universidade', 'clinic' => 'Clínica', 'dentist' => 'Dentista',
            'pub' => 'Pub/Bar', 'beauty' => 'Salão de Beleza', 'department_store' => 'Loja de Depto',
            'place_of_worship' => 'Igreja/Templo', 'cinema' => 'Cinema', 'theatre' => 'Teatro',
            'library' => 'Biblioteca', 'post_office' => 'Correios', 'park' => 'Parque/Lazer',
            'gym' => 'Academia', 'sports_centre' => 'Centro Esportivo', 'playground' => 'Playground'
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
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-info text-decoration-none fw-bold"><i class="fa-solid fa-arrow-left me-2"></i>Nova Busca</a></li>
                            <li class="breadcrumb-item active text-white-50" aria-current="page">Relatório Regional</li>
                        </ol>
                    </nav>
                    <h1 class="display-3 fw-bolder mb-2">{{ $report->cidade }} <small class="text-primary">{{ $report->uf }}</small></h1>
                    <p class="lead opacity-75 italic mb-0">"{{ $wiki['extract'] ?? 'Análise detalhada da vizinhança baseada no CEP ' . $report->cep }}"</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="d-inline-block text-center p-3 rounded-4" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                        <span class="d-block text-white-50 small fw-bold text-uppercase mb-1">CEP Pesquisado</span>
                        <span class="h2 font-monospace fw-black mb-0">{{ $report->cep }}</span>
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

            <!-- Right Side: POI Lists -->
            <div class="col-lg-4">
                <div class="card card-premium p-4 h-100 bg-white">
                    <h4 class="fw-black mb-4"><i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Perfil Local <small class="text-muted fw-normal fs-6">3km</small></h4>

                    <!-- Mobility -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="section-header mb-0"><i class="fa-solid fa-bus me-2"></i>Mobilidade</span>
                            <span class="badge bg-light text-muted border">{{ count($mobility) }}</span>
                        </div>
                        <div class="poi-scroll pe-2">
                            @forelse(array_slice($mobility, 0, 10) as $m)
                                @php 
                                    $mName = $m['tags']['name'] ?? $m['tags']['addr:street'] ?? 'Sem Nome';
                                    $mType = $translations[$m['tags']['highway'] ?? $m['tags']['amenity'] ?? ''] ?? 'Acesso';
                                @endphp
                                <div class="d-flex align-items-center p-3 rounded-4 mb-2 border border-light bg-light bg-opacity-50">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-center me-3" style="width: 32px; height:32px; font-size: 12px; flex-shrink: 0;">
                                        <i class="fa-solid fa-bus-simple"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark small text-truncate">{{ $mName }}</div>
                                        <div class="text-muted" style="font-size: 10px; font-weight: 700; text-transform: uppercase;">{{ $mType }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 bg-light rounded-4">
                                    <small class="text-muted italic">Nenhum ponto detectado.</small>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Commerce -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="section-header mb-0"><i class="fa-solid fa-shop me-2"></i>Comércio</span>
                            <span class="badge bg-light text-muted border">{{ count($commerces) }}</span>
                        </div>
                        <div class="poi-scroll pe-2">
                            @forelse(array_slice($commerces, 0, 12) as $c)
                                <div class="p-3 rounded-4 mb-2 border border-light bg-white shadow-sm">
                                    <div class="fw-black text-dark small text-truncate mb-1">{{ $c['tags']['name'] ?? 'Comércio Local' }}</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-primary fw-black text-uppercase" style="font-size: 9px; letter-spacing: 0.05em;">{{ $translations[$c['tags']['shop'] ?? $c['tags']['amenity'] ?? ''] ?? 'Serviço' }}</span>
                                        @if(isset($c['tags']['addr:street']))
                                            <span class="text-muted fs-small" style="font-size: 9px;">• {{ $c['tags']['addr:street'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 bg-light rounded-4">
                                    <small class="text-muted italic">Em busca de dados...</small>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Critical Structure -->
                    <div>
                        <span class="section-header d-block mb-3">🏥 Infraestrutura</span>
                        <div class="row g-2">
                            @foreach(['pharmacy' => 'fa-pills', 'hospital' => 'fa-hospital', 'school' => 'fa-graduation-cap', 'bank' => 'fa-building-columns'] as $key => $icon)
                                @php $cnt = count(array_filter($health_edu, fn($h) => ($h['tags']['amenity'] ?? '') === $key)); @endphp
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-4 text-center border">
                                        <i class="fa-solid {{ $icon }} text-muted opacity-50 mb-2"></i>
                                        <div class="h5 fw-black mb-0">{{ $cnt }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
