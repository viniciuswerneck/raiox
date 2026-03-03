<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Raio-X de {{ $report->cidade }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons & Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #0f172a;
            --accent: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-body: #f8fafc;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
        }

        h1, h2, h3, h4, h5, .font-display { font-family: 'Outfit', sans-serif; }

        /* Premium Hero Section */
        .hero-premium {
            background: #020617;
            position: relative;
            padding: 80px 0 140px;
            overflow: hidden;
            color: white;
        }

        .hero-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.4;
            filter: brightness(0.6) contrast(1.2);
            z-index: 0;
        }

        .hero-gradient {
            position: absolute;
            bottom: 0; left: 0; width: 100%; height: 60%;
            background: linear-gradient(to top, #020617, transparent);
            z-index: 1;
        }

        .hero-content { position: relative; z-index: 2; }

        /* Card System (Symmetry) */
        .card-territory {
            background: white;
            border: 1px solid rgba(0,0,0,0.04);
            border-radius: 24px;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.04);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .card-territory:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.1);
        }

        /* Metric Widgets */
        .metric-v2 {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-ico-v2 {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .label-v2 {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .val-v2 { font-weight: 900; font-size: 1.5rem; line-height: 1.1; }

        /* Custom Badges */
        .badge-premium {
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.7rem;
            font-weight: 900;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Map styling */
        #map {
            height: 580px;
            border-radius: 24px;
            z-index: 10;
            border: 4px solid white;
            box-shadow: 0 20px 40px -20px rgba(0,0,0,0.2);
        }

        .section-tag {
            background: rgba(79, 70, 229, 0.05);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .poi-item {
            padding: 16px;
            border-radius: 16px;
            margin-bottom: 12px;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            background: #fdfdfd;
        }

        .poi-item:hover {
            background: white;
            border-color: var(--primary-light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        /* History Dropcap */
        .history-text::first-letter {
            float: left;
            font-size: 3.5rem;
            line-height: 0.8;
            padding-right: 12px;
            padding-top: 4px;
            font-weight: 900;
            color: var(--primary);
            font-family: 'Outfit';
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
        
        $commerces = array_filter($pois, function($p) use ($mobility, $health_edu) {
            $isShop = isset($p['tags']['shop']);
            $isFood = in_array($p['tags']['amenity'] ?? '', ['restaurant', 'cafe', 'bar', 'fast_food', 'pub', 'ice_cream', 'food_court']);
            $isInOther = in_array($p['id'], array_column($mobility, 'id')) || in_array($p['id'], array_column($health_edu, 'id'));
            return ($isShop || $isFood) && !$isInOther;
        });

        $translations = [
            'supermarket' => 'Supermercado', 'restaurant' => 'Restaurante', 'pharmacy' => 'Farmácia', 'cafe' => 'Café/Padaria',
            'bakery' => 'Padaria', 'school' => 'Escola', 'bank' => 'Banco', 'hospital' => 'Hospital', 'bus_stop' => 'Parada de Ônibus',
            'bicycle_parking' => 'Estac. Bicicletas', 'convenience' => 'Conveniência', 'clothes' => 'Loja de Roupas', 'mall' => 'Shopping',
            'fuel' => 'Posto Combustível', 'bar' => 'Bar/Pub', 'fast_food' => 'Fast Food', 'university' => 'Universidade', 
            'clinic' => 'Clínica', 'dentist' => 'Dentista', 'pub' => 'Pub/Bar', 'beauty' => 'Salão de Beleza', 'department_store' => 'Loja de Depto',
            'place_of_worship' => 'Igreja/Templo', 'cinema' => 'Cinema', 'theatre' => 'Teatro', 'library' => 'Biblioteca', 
            'post_office' => 'Correios', 'park' => 'Parque/Lazer', 'gym' => 'Academia', 'sports_centre' => 'Centro Esportivo', 
            'playground' => 'Playground', 'ice_cream' => 'Sorveteria', 'food_court' => 'Praça de Alimentação', 
            'hardware' => 'Material de Construção', 'electronics' => 'Eletrônicos', 'furniture' => 'Móveis', 'optician' => 'Ótica', 
            'books' => 'Livraria', 'car_repair' => 'Oficina Mecânica', 'car_wash' => 'Lava Rápido', 'pet_shop' => 'Pet Shop',
            'veterinary' => 'Veterinária', 'hairdresser' => 'Cabeleireiro', 'laundry' => 'Lavanderia'
        ];

        // Status Logic
        $aqiLabel = 'BOM'; $aqiColor = 'text-green-500';
        if($aqi > 20) { $aqiLabel = 'MODERADO'; $aqiColor = 'text-warning'; }
        if($aqi > 40) { $aqiLabel = 'RUIM'; $aqiColor = 'text-danger'; }

        $safetyColor = 'text-success'; $safetyBg = 'bg-success-subtle';
        if($report->safety_level == 'MODERADO') { $safetyColor = 'text-warning'; $safetyBg = 'bg-warning-subtle'; }
        if($report->safety_level == 'BAIXO') { $safetyColor = 'text-danger'; $safetyBg = 'bg-danger-subtle'; }
    @endphp

    <!-- HERO SECTION -->
    <header class="hero-premium shadow-2xl">
        @if($wiki['image'] ?? null)
            <img src="{{ $wiki['image'] }}" class="hero-overlay" alt="{{ $report->cidade }}">
        @endif
        <div class="hero-gradient"></div>
        
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 me-3 opacity-75 hover-opacity-100">
                            <i class="fa-solid fa-arrow-left me-2"></i>Início
                        </a>
                        <span class="badge bg-indigo-500 rounded-pill px-3 py-2 fw-black uppercase tracking-widest" style="font-size: 0.6rem;">Análise Territorial Ativa</span>
                    </div>
                    <h4 class="text-white-50 font-display fw-bold mb-1 uppercase tracking-widest">Informações de {{ $report->bairro ?: 'Município' }}</h4>
                    <h1 class="display-1 fw-black mb-3 text-white tracking-tighter">{{ $report->cidade }} <span class="text-indigo-400 opacity-75">{{ $report->uf }}</span></h1>
                    <p class="h5 fw-medium opacity-75 leading-relaxed max-w-2xl mb-0">
                        {{ $report->logradouro ? $report->logradouro . ' • ' : '' }}{{ $report->bairro }} 
                        <span class="mx-2 opacity-25">|</span> 
                        CEP {{ $report->cep }}
                    </p>
                </div>
                <div class="col-lg-4 text-center text-lg-end mt-5 mt-lg-0">
                    <div class="d-inline-block glass-panel p-4 rounded-5 border-white border-opacity-10">
                        <div class="label-v2 text-white opacity-50 mb-1">População Estimada</div>
                        <div class="h2 fw-black mb-0 font-display">{{ number_format($report->populacao, 0, ',', '.') }}</div>
                        <div class="text-indigo-400 small fw-bold mt-1">Dados IBGE 2024</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: -60px; position: relative; z-index: 10;">
        
        <!-- TOP METRICS GRID (4 Slots) -->
        <div class="row g-4 mb-5">
            <div class="col-sm-6 col-lg-3">
                <div class="card-territory metric-v2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="metric-ico-v2 bg-indigo-50 text-indigo-600"><i class="fa-solid fa-person-walking"></i></div>
                        <div class="h5 font-display fw-black text-indigo-600">{{ $report->walkability_score }}</div>
                    </div>
                    <div>
                        <div class="label-v2">Caminhabilidade</div>
                        <div class="val-v2">{{ $report->walkability_score == 'A' ? 'Excelente' : ($report->walkability_score == 'B' ? 'Básica' : 'Limitada') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-territory metric-v2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="metric-ico-v2 bg-amber-50 text-warning"><i class="fa-solid fa-cloud-sun"></i></div>
                        <div class="h5 font-display fw-black @if($aqi > 40) text-danger @else text-amber-600 @endif">{{ $aqi }} <small class="fw-bold opacity-50" style="font-size: 10px;">AQI</small></div>
                    </div>
                    <div>
                        <div class="label-v2">Qualidade do Ar</div>
                        <div class="val-v2">{{ $aqiLabel }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-territory metric-v2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="metric-ico-v2 bg-emerald-50 text-emerald-600"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div class="small fw-bold text-emerald-600">Mensal</div>
                    </div>
                    <div>
                        <div class="label-v2">Renda Média</div>
                        <div class="val-v2">R$ {{ number_format($report->average_income, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-territory metric-v2 {{ $safetyBg }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="metric-ico-v2 bg-white {{ $safetyColor }} shadow-sm"><i class="fa-solid fa-shield-halved"></i></div>
                        <div class="badge-premium bg-white {{ $safetyColor }} border shadow-sm">{{ $report->safety_level }}</div>
                    </div>
                    <div>
                        <div class="label-v2 {{ $safetyColor }}">Segurança</div>
                        <div class="val-v2 {{ $safetyColor }}">Nível Local</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN LAYOUT (Symmetry) -->
        <div class="row g-5 mb-5 align-items-stretch">
            <div class="col-lg-8">
                <div class="card-territory h-100 overflow-hidden">
                    <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-map-location-dot h3 text-indigo-600 me-3 mb-0"></i>
                            <div>
                                <h3 class="fw-black mb-0 h5">Mapa de Vizinhança</h3>
                                <p class="small text-muted mb-0">Localização exata e pontos de interesse identificados</p>
                            </div>
                        </div>
                        <div class="section-tag mb-0">RAIO 10KM</div>
                    </div>
                    <div id="map"></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-territory d-flex flex-column h-100">
                    <div class="p-4 border-bottom">
                        <h4 class="fw-black mb-0"><i class="fa-solid fa-compass me-2 text-indigo-600"></i>Pontos de Apoio</h4>
                    </div>
                    
                    <!-- POI Groups -->
                    <div class="p-4 overflow-auto custom-scrollbar" style="max-height: 520px;">
                        <!-- Healthcare/Education -->
                        <div class="mb-4">
                            <span class="label-v2 block mb-3">Infraestrutura Crítica</span>
                            @forelse(array_slice($health_edu, 0, 8) as $h)
                                <div class="poi-item d-flex align-items-center">
                                    <div class="bg-indigo-50 text-indigo-600 rounded-circle d-flex align-items-center justify-content-center me-3" style="width:32px; height:32px; flex-shrink:0;">
                                        <i class="fa-solid fa-location-dot fa-xs"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark small text-truncate">{{ $h['tags']['name'] ?? 'Local de Apoio' }}</div>
                                        <div class="text-muted" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">{{ $translations[$h['tags']['amenity'] ?? ''] ?? 'Serviço' }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="small text-muted opacity-50">Sem dados mapeados.</p>
                            @endforelse
                        </div>

                        <!-- Commerce -->
                        <div>
                            <span class="label-v2 block mb-3">Comércios e Alimentação</span>
                            @forelse(array_slice($commerces, 0, 10) as $c)
                                <div class="poi-item d-flex align-items-center">
                                    <div class="bg-emerald-50 text-emerald-600 rounded-circle d-flex align-items-center justify-content-center me-3" style="width:32px; height:32px; flex-shrink:0;">
                                        <i class="fa-solid fa-bag-shopping fa-xs"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark small text-truncate">{{ $c['tags']['name'] ?? 'Comércio Local' }}</div>
                                        <div class="text-muted" style="font-size: 9px; font-weight: 800; text-transform: uppercase;">{{ $translations[$c['tags']['shop'] ?? $c['tags']['amenity'] ?? ''] ?? 'Venda' }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="small text-muted opacity-50">Sem dados mapeados.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HISTORY & ANALYSIS SECTION -->
        <section class="mb-5">
            <div class="card-territory p-4 p-md-5">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7">
                        <div class="section-tag"><i class="fa-solid fa-scroll me-2"></i>Análise por Inteligência Artificial</div>
                        <h2 class="display-5 fw-black mb-4">A Essência de {{ $report->cidade }}</h2>
                        
                        <div class="history-text text-muted lh-lg" style="text-align: justify; font-size: 1.05rem;">
                            {{ $report->history_extract }}
                        </div>

                        <div class="mt-4 pt-4 border-top">
                            <div class="d-flex align-items-center bg-slate-50 p-4 rounded-4">
                                <i class="fa-solid fa-shield-halved h3 text-indigo-600 me-4 mb-0"></i>
                                <div>
                                    <div class="label-v2">Nota da Segurança Pública</div>
                                    <p class="mb-0 fw-bold text-dark">{{ $report->safety_description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 text-center">
                        @if($wiki['image'] ?? null)
                            <div class="position-relative d-inline-block">
                                <img src="{{ $wiki['image'] }}" class="img-fluid rounded-5 shadow-2xl border border-white border-8" 
                                     style="max-height: 480px; width: auto; object-fit: cover; transform: rotate(1deg);" alt="{{ $report->cidade }}">
                                <div class="position-absolute bottom-0 end-0 bg-indigo-600 p-3 rounded-circle shadow-lg m-(-4) animate-pulse border border-white border-4">
                                    <i class="fa-brands fa-wikipedia-w text-white h3 mb-0"></i>
                                </div>
                            </div>
                        @else
                            <div class="bg-slate-100 p-5 rounded-5 border border-dashed text-slate-300">
                                <i class="fa-solid fa-camera fa-6x"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- FOOTER -->
    <footer class="bg-white py-5 border-top">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <span class="text-xl font-black tracking-tighter text-dark uppercase italic">{{ config('app.name') }}<span class="text-indigo-500">.</span>territory</span>
                    <p class="text-muted small mt-2">Tecnologia avançada de cruzamento de dados urbanos e demográficos.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="d-flex justify-content-center justify-content-md-end gap-4 mb-3">
                        <small class="text-slate-400 fw-black uppercase tracking-widest" style="font-size: 9px;">ViaCEP</small>
                        <small class="text-slate-400 fw-black uppercase tracking-widest" style="font-size: 9px;">Overpass OSM</small>
                        <small class="text-slate-400 fw-black uppercase tracking-widest" style="font-size: 9px;">Open-Meteo</small>
                        <small class="text-slate-400 fw-black uppercase tracking-widest" style="font-size: 9px;">Gemini 2.5</small>
                    </div>
                    <p class="text-muted small mb-0">© {{ date('Y') }} - Desenvolvido para Análise B2C Premium.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Leaflet Configuration -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = {{ $report->lat }};
            const lng = {{ $report->lng }};
            const pois = @json($report->pois_json ?? []);
            const translations = @json($translations);

            // Premium Map Style (Muted but colorful markers)
            const map = L.map('map', { 
                scrollWheelZoom: false,
                attributionControl: false
            }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                maxZoom: 19
            }).addTo(map);

            // Add Central Marker
            const centralIcon = L.divIcon({
                className: 'custom-div-icon',
                html: "<div style='background-color:#4f46e5; width:24px; height:24px; border-radius:50%; border:4px solid white; box-shadow: 0 0 15px rgba(79,70,229,0.5);'></div>",
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
            L.marker([lat, lng], {icon: centralIcon}).addTo(map).bindPopup("<b class='font-display'>Ponto de Referência</b><br>Equidistante à análise.");

            // Add POIs
            pois.forEach(poi => {
                if(poi.lat && poi.lon) {
                    const tag = poi.tags.amenity || poi.tags.shop || poi.tags.highway;
                    const name = poi.tags.name || translations[tag] || 'Estabelecimento';
                    
                    const marker = L.circleMarker([poi.lat, poi.lon], {
                        radius: 6,
                        fillColor: "#6366f1",
                        color: "#fff",
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(map);

                    marker.bindPopup(`<small class='fw-bold text-indigo-600 uppercase tracking-widest' style='font-size:9px'>${translations[tag] || tag || 'POI'}</small><br><b class='font-display'>${name}</b>`);
                }
            });
        });
    </script>
</body>
</html>
