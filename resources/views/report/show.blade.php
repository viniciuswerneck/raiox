<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raio-X de {{ $report->bairro ?: $report->cidade }} - {{ $report->cidade }}/{{ $report->uf }} | {{ config('app.name') }}</title>
    <meta name="description" content="Relatório detalhado sobre o bairro {{ $report->bairro }} em {{ $report->cidade }}. Veja índices de segurança, caminhabilidade, qualidade do ar e infraestrutura urbana.">
    <meta name="keywords" content="viver em {{ $report->cidade }}, bairro {{ $report->bairro }}, segurança {{ $report->cidade }}, caminhabilidade {{ $report->bairro }}, qualidade do ar {{ $report->cidade }}">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="Raio-X Territorial: {{ $report->bairro ?: $report->cidade }} - {{ $report->cidade }}">
    <meta property="og:description" content="Confira a análise completa de infraestrutura e qualidade de vida desta região.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url('/hero_background_city_1772568797393.png') }}">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    

    
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
            margin-top: -120px;
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
            padding: 32px;
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
            line-height: 1.6;
            font-size: 1rem;
            color: #475569;
            text-align: left;
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

        /* ==================== RESPONSIVE MOBILE ==================== */
        @media (max-width: 767.98px) {
            .hero-section {
                min-height: auto;
                padding: 60px 0 100px;
            }

            .hero-section h1.display-1 {
                font-size: 2.8rem !important;
            }

            .dashboard-container {
                margin-top: -50px;
            }

            .card-pro {
                padding: 16px;
            }

            #map-print-section {
                height: auto !important;
            }

            #map-container {
                height: 320px !important;
            }

            .map-category-btn {
                padding: 7px 12px;
                font-size: 11px;
            }

            .poi-drawer {
                max-height: 280px;
            }

            .editorial-text {
                font-size: 1rem;
            }

            .drop-cap::first-letter {
                font-size: 3.5rem;
            }
        }

        /* ==================== GAMIFICATION & SCORES ==================== */
        .score-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(25px);
            border-radius: var(--card-radius);
            padding: 32px 42px;
            color: #1e293b;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
            margin-top: 15px;
            margin-bottom: 10px;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s;
        }

        .score-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.12);
        }

        .score-circle-container {
            position: relative;
            width: 170px;
            height: 170px;
            margin: 0 auto;
        }

        .score-number {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
        }

        .score-val {
            font-size: 4rem;
            font-weight: 900;
            line-height: 0.8;
            letter-spacing: -2px;
            margin-bottom: 2px;
        }

        .neon-progress {
            height: 10px;
            background: #f1f5f9;
            border-radius: 99px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .neon-bar {
            height: 100%;
            border-radius: 99px;
            transition: width 1.8s cubic-bezier(0.2, 0, 0, 1);
        }

        .badge-medal {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px 20px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-bottom: 10px;
        }

        .badge-medal:hover {
            transform: translateX(8px);
            background: white;
            border-color: var(--primary);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .badge-icon {
            font-size: 18px;
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        /* Float Comparison UI */
        .compare-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: var(--primary);
            color: white;
            width: 65px;
            height: 65px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.5);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid white;
        }

        .compare-fab:hover {
            transform: scale(1.15) rotate(10deg);
            background: var(--primary-dark);
        }

        .compare-panel {
            position: fixed;
            bottom: 110px;
            right: 30px;
            width: 380px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 28px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.15);
            z-index: 999;
            transform: translateY(20px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .compare-panel.active {
            transform: translateY(0);
            opacity: 1;
            pointer-events: all;
        }

        /* PREMIUM LOADER */
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(25px);
            z-index: 9999;
        }

        .ai-pulse {
            animation: ai-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes ai-pulse {
            0%, 100% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            50% { transform: scale(1.05); opacity: 0.9; box-shadow: 0 0 40px 10px rgba(99, 102, 241, 0.4); }
        }

        .orbit {
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <!-- LOADER / QUEUE OVERLAY -->
    @if($report->status !== 'completed' || $report->cidade === 'Localizando...')
    <div id="loader" class="d-flex flex-column align-items-center justify-content-center text-white" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.98); backdrop-filter: blur(25px); z-index: 99999;">
        <div class="relative flex items-center justify-center mb-5" style="width: 200px; height: 200px;">
            <div class="absolute inset-0 orbit opacity-20">
                <div class="absolute top-0 left-1/2 w-4 h-4 bg-indigo-500 rounded-full blur-sm"></div>
                <div class="absolute bottom-0 left-1/2 w-4 h-4 bg-purple-500 rounded-full blur-sm"></div>
            </div>
            <div class="relative w-24 h-24 bg-indigo-600 rounded-full flex items-center justify-center ai-pulse shadow-2xl shadow-indigo-500/50">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.338 2.798H4.136c-1.368 0-2.338-1.798-1.338-2.798L4 15.298" />
                </svg>
            </div>
        </div>
        <div class="text-center space-y-3 px-4" style="max-width: 500px">
            <h3 class="fw-black text-white h2 mb-1">
                @if($report->status === 'pending')
                    Sincronizando Território
                @elseif($report->status === 'processing')
                    Analisando Indicadores com IA
                @else
                    Ops! Algo deu errado
                @endif
            </h3>
            
            @if($report->status === 'failed')
                <p class="text-danger small fw-bold text-uppercase mb-4">{{ $report->error_message ?? 'Erro desconhecido' }}</p>
                <a href="{{ route('home') }}" class="btn btn-outline-light rounded-pill px-4">Tentar outro CEP</a>
            @else
                <p id="queue-text" class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.3em;">
                    @if($report->status === 'pending')
                        Aguardando na fila de satélites...
                    @else
                        Processando dados do IBGE, Clima e Wikipedia...
                    @endif
                </p>
                <div class="progress rounded-pill mx-auto mb-4" style="width: 250px; height: 6px; background: rgba(255,255,255,0.1);">
                    <div id="queue-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: {{ $report->status === 'pending' ? '20%' : '60%' }}"></div>
                </div>
                <div class="p-3 rounded-4 border border-white/10 bg-white/5 backdrop-blur-sm">
                    <p class="small text-white-50 mb-0">
                        <i class="fa-solid fa-clock me-2 text-primary"></i>
                        Isso pode levar até 20 segundos. Não feche esta aba.
                    </p>
                </div>
            @endif
        </div>
    </div>
    <script>
        // Polling para verificar status
        const pollInterval = setInterval(async () => {
            try {
                // Tenta disparar a fila (Simula o Cron localmente)
                fetch('/api/trigger-queue').catch(e => console.warn("Queue trigger skipping..."));

                const response = await fetch('/api/report-status/{{ $report->cep }}');
                const data = await response.json();
                
                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    window.location.reload();
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    window.location.reload();
                }
            } catch (e) {
                console.error("Erro no polling:", e);
            }
        }, 4000); // Aumentado para 4s para dar tempo ao processamento manual
    </script>
    @else
    <div id="loader" class="d-flex flex-column align-items-center justify-content-center text-white" style="display: none !important;">
        <div class="relative flex items-center justify-center mb-5" style="width: 200px; height: 200px;">
            <div class="absolute inset-0 orbit opacity-20">
                <div class="absolute top-0 left-1/2 w-4 h-4 bg-indigo-500 rounded-full blur-sm"></div>
                <div class="absolute bottom-0 left-1/2 w-4 h-4 bg-purple-500 rounded-full blur-sm"></div>
            </div>
            <div class="relative w-24 h-24 bg-indigo-600 rounded-full flex items-center justify-center ai-pulse shadow-2xl shadow-indigo-500/50">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.338 2.798H4.136c-1.368 0-2.338-1.798-1.338-2.798L4 15.298" />
                </svg>
            </div>
        </div>
        <div class="text-center space-y-3">
            <h3 class="fw-black text-white h4 mb-1">Iniciando Duelo Territorial</h3>
            <p id="loader-text" class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.3em;">Cruzando indicadores...</p>
            <div class="progress rounded-pill mx-auto" style="width: 150px; height: 4px; background: rgba(255,255,255,0.1);">
                <div id="progress-bar" class="progress-bar bg-primary" style="width: 10%"></div>
            </div>
        </div>
    </div>
    @endif

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

        // Safety Logic
        $safetyRaw = strtoupper($report->safety_level ?? '');
        $sColor = match(true) {
            str_contains($safetyRaw, 'ALT') => 'success',
            str_contains($safetyRaw, 'MODERAD') || str_contains($safetyRaw, 'MEDI') => 'warning',
            str_contains($safetyRaw, 'BAIX') => 'danger',
            default => 'secondary'
        };
    @endphp

    <!-- HERO SECTION -->
    <section class="hero-section">
        <!-- Floating Logo Overlay -->
        <div class="position-absolute top-0 start-0 p-4 no-print" style="z-index: 100;">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-4 border border-white border-opacity-20 shadow-lg overflow-hidden" style="width: 44px; height: 44px;">
                    <img src="{{ asset('favicon.png') }}" class="w-100 h-100 object-fit-cover" alt="Logo">
                </div>
                <div class="text-white">
                    <div class="fw-black h6 mb-0 text-uppercase italic tracking-tighter" style="font-size: 14px;">{{ config('app.name') }}</div>
                    <div class="small opacity-50 text-uppercase fw-bold" style="font-size: 7px; letter-spacing: 2px;">Terrestrial Intelligence</div>
                </div>
            </div>
        </div>
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
                    <p class="h5 fw-light text-white-50 opacity-75">
                        {{ $report->logradouro ?: $report->bairro }} • <span class="fw-medium text-white">CEP {{ $formattedCep }}</span>
                    </p>
                    <div class="mt-4 d-flex flex-wrap gap-3 no-print">
                        <a href="{{ route('home') }}" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold">
                            <i class="fa-solid fa-arrow-left me-2"></i>Nova Busca
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container dashboard-container">
        <!-- TOP METRICS GRID -->
        <div class="row g-4 mb-5">

            <!-- Caminhabilidade -->
            <div class="col-lg-4 col-md-12 reveal" style="animation-delay: 0.1s">
                <div class="card-pro d-flex flex-column justify-content-between overflow-hidden position-relative h-100">
                    <div class="mb-3">
                        <div class="metric-icon-pro bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-person-walking"></i>
                        </div>
                        <h4 class="mb-1">Caminhabilidade</h4>
                        <p class="text-muted small mb-0">Mobilidade ativa e acesso peatonal.</p>
                    </div>
                    <div class="text-center py-2 flex-grow-1 d-flex flex-column justify-content-center">
                        <div style="font-size: 4rem; line-height: 1; font-weight: 900; color: {{ $walkColor }}">
                            {{ $report->walkability_score }}
                        </div>
                        <div class="mt-2">
                            <span class="status-pill" style="background: {{ $walkColor }}15; color: {{ $walkColor }}">
                                {{ $walkLabel }}
                            </span>
                        </div>
                    </div>
                    <div style="position: absolute; bottom: -15px; right: -15px; opacity: 0.05; font-size: 6rem;">
                        <i class="fa-solid fa-shoe-prints"></i>
                    </div>
                </div>
            </div>
            <!-- Qualidade do Ar -->
            <div class="col-lg-4 col-md-6 reveal" style="animation-delay: 0.2s">
                <div class="card-pro h-100">
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

            <!-- Saneamento & Infra -->
            <div class="col-lg-4 col-md-6 reveal" style="animation-delay: 0.3s">
                <div class="card-pro bg-dark text-white border-0 shadow-xl h-100" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
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

        <!-- SCORE SECTION (GLASS DESIGN) -->
        <div class="row mb-3 reveal">
            <div class="col-12">
                @php
                    // Lógica de Gamificação do Score (0-100)
                    $poisCount = count($pois);
                    $safetyVal = match(true) {
                        str_contains($safetyRaw, 'ALT') => 92,
                        str_contains($safetyRaw, 'MODERAD') || str_contains($safetyRaw, 'MEDI') => 70,
                        str_contains($safetyRaw, 'BAIX') => 45,
                        default => 55
                    };
                    $commerceScore = min(100, (int)($poisCount * 1.5)); 
                    $infraScore = $report->sanitation_rate ?: 50;
                    $cultureScore = $report->history_extract ? min(100, (int)(strlen($report->history_extract) / 20)) : 35;

                    $finalScore = round(($safetyVal * 0.4) + ($commerceScore * 0.3) + ($infraScore * 0.2) + ($cultureScore * 0.1));
                    
                    $tierLabel = match(true) {
                        $finalScore >= 90 => 'Distrito de Elite',
                        $finalScore >= 75 => 'Alto Padrão',
                        $finalScore >= 60 => 'Conforto Urbano',
                        default => 'Região em Ascensão'
                    };
                    $tierColor = match(true) {
                        $finalScore >= 90 => '#10b981',
                        $finalScore >= 75 => '#6366f1',
                        $finalScore >= 60 => '#f59e0b',
                        default => '#64748b'
                    };
                @endphp
                <div class="score-card">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-center mb-5 mb-lg-0 border-end border-light border-opacity-50">
                            <div class="score-circle-container mb-3">
                                <svg width="170" height="170" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="45" fill="none" stroke="#f1f5f9" stroke-width="7"></circle>
                                    <circle cx="50" cy="50" r="45" fill="none" stroke="{{ $tierColor }}" stroke-width="7" stroke-dasharray="{{ ($finalScore/100) * 283 }} 283" stroke-linecap="round" transform="rotate(-90 50 50)" style="filter: drop-shadow(0 0 10px {{ $tierColor }}40)"></circle>
                                </svg>
                                <div class="score-number">
                                    <div class="score-val" style="color: {{ $tierColor }}">{{ $finalScore }}</div>
                                    <div class="text-muted small fw-black text-uppercase tracking-widest">Score Real</div>
                                </div>
                            </div>
                            <span class="status-pill px-4 py-2" style="background: {{ $tierColor }}10; color: {{ $tierColor }}; font-size: 11px; border: 1px solid {{ $tierColor }}20;">
                                <i class="fa-solid fa-medal me-2"></i>{{ $tierLabel }}
                            </span>
                        </div>
                        
                        <div class="col-lg-8 ps-lg-5 text-start">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <h6 class="text-secondary small fw-black mb-4 text-uppercase tracking-widest" style="opacity: 0.8;">Análise de Performance</h6>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-2 small fw-bold">
                                            <span class="text-dark">Segurança & Proteção</span>
                                            <span style="color: #059669">{{ $safetyVal }}%</span>
                                        </div>
                                        <div class="neon-progress">
                                            <div class="neon-bar" style="width: {{ $safetyVal }}%; background: #10b981;"></div>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <div class="d-flex justify-content-between mb-2 small fw-bold">
                                            <span class="text-dark">Capacidade Comercial</span>
                                            <span style="color: #4f46e5">{{ $commerceScore }}%</span>
                                        </div>
                                        <div class="neon-progress">
                                            <div class="neon-bar" style="width: {{ $commerceScore }}%; background: #6366f1;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 class="text-secondary small fw-black mb-4 text-uppercase tracking-widest" style="opacity: 0.8;">Posicionamento</h6>
                                    <div class="d-flex flex-column gap-2">
                                        @php
                                            // Pega a posição desse bairro no ranking da cidade dele
                                            $results = \App\Models\LocationReport::select('bairro', DB::raw('COUNT(*) as searches'))
                                                ->where('cidade', $report->cidade)
                                                ->groupBy('bairro')
                                                ->orderBy('searches', 'desc')
                                                ->get();
                                            
                                            $position = $results->filter(fn($r) => $r->bairro == $report->bairro)->keys()->first() + 1;
                                            $totalBairros = $results->count();
                                        @endphp

                                        <div class="badge-medal" style="background: var(--primary)10; border-color: var(--primary)30;">
                                            <div class="badge-icon" style="color: var(--primary);"><i class="fa-solid fa-ranking-star"></i></div>
                                            <div>
                                                <div class="fw-black small text-primary">#{{ $position }}º Mais Popular</div>
                                                <div class="text-muted" style="font-size: 10px;">{{ $report->cidade }}</div>
                                            </div>
                                        </div>

                                        <a href="{{ route('ranking.index') }}" class="text-primary fw-black uppercase tracking-widest mt-2 text-decoration-none" style="font-size: 10px;">
                                            Ver Ranking Completo <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <h6 class="text-secondary small fw-black mb-4 text-uppercase tracking-widest" style="opacity: 0.8;">Destaques</h6>
                                    <div class="d-flex flex-column gap-2">
                                        @if($safetyVal >= 80)
                                            <div class="badge-medal">
                                                <div class="badge-icon" style="color: #10b981;"><i class="fa-solid fa-shield-halved"></i></div>
                                                <div class="fw-black small text-dark" style="font-size: 10px;">Seguro</div>
                                            </div>
                                        @endif
                                        @if($commerceScore >= 70)
                                            <div class="badge-medal">
                                                <div class="badge-icon" style="color: #6366f1;"><i class="fa-solid fa-store"></i></div>
                                                <div class="fw-black small text-dark" style="font-size: 10px;">Comercial</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MIDDLE SECTION: MAP & INFRASTRUCTURE -->
        <div class="row g-4 mb-5">
            <div class="col-xl-8">
                <div id="map-print-section" class="card-pro p-0 overflow-hidden bg-white border-0 shadow-lg">
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
                    <div id="map-container" style="height: 400px; position: relative;">
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
                        <div class="text-primary fw-bold small">RAIO {{ ($report->search_radius ?? 10000) / 1000 }}KM</div>
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
                                @foreach(array_slice($education_faith, 0, 8) as $p)
                                    @php 
                                        $tag = $p['tags']['amenity'] ?? '';
                                        $icon = match(true) {
                                            in_array($tag, ['school', 'university', 'kindergarten', 'childcare']) => 'graduation-cap',
                                            $tag === 'place_of_worship' => 'church',
                                            default => 'landmark'
                                        };
                                    @endphp
                                    <div class="poi-item d-flex align-items-center mb-2 px-3 py-2 border-0 shadow-sm" style="background: white; cursor: pointer; border-radius: 12px;" onclick="focusPoi('{{ $p['id'] }}')">
                                        <div class="bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; border-radius: 8px;">
                                            <i class="fa-solid fa-{{ $icon }} small"></i>
                                        </div>
                                        <div class="text-truncate small fw-bold text-dark">{{ $p['tags']['name'] ?? 'Instituição' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Comércio e Lazer -->
                        @if(count($commerce) > 0)
                            <div class="mb-3">
                                <h6 class="text-muted fw-bold mb-2 small uppercase tracking-tighter">Comércio & Conveniência</h6>
                                @foreach(array_slice($commerce, 0, 8) as $p)
                                    @php 
                                        $tag = $p['tags']['amenity'] ?? $p['tags']['shop'] ?? '';
                                        $icon = match(true) {
                                            in_array($tag, ['restaurant', 'cafe', 'fast_food', 'bakery']) => 'utensils',
                                            $tag === 'fuel' => 'gas-pump',
                                            in_array($tag, ['supermarket', 'convenience', 'mall']) => 'cart-shopping',
                                            default => 'store'
                                        };
                                    @endphp
                                    <div class="poi-item d-flex align-items-center mb-2 px-3 py-2 border-0 shadow-sm" style="background: white; cursor: pointer; border-radius: 12px;" onclick="focusPoi('{{ $p['id'] }}')">
                                        <div class="bg-amber-100 text-amber-600 d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; border-radius: 8px;">
                                            <i class="fa-solid fa-{{ $icon }} small"></i>
                                        </div>
                                        <div class="text-truncate small fw-bold text-dark">{{ $p['tags']['name'] ?? 'Comércio' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Saúde -->
                        @if(count($health) > 0)
                            <div class="mb-3">
                                <h6 class="text-muted fw-bold mb-2 small uppercase tracking-tighter">Saúde & Farmácias</h6>
                                @foreach(array_slice($health, 0, 8) as $p)
                                    @php 
                                        $tag = $p['tags']['amenity'] ?? '';
                                        $icon = match(true) {
                                            $tag === 'hospital' => 'hospital',
                                            $tag === 'pharmacy' => 'pills',
                                            default => 'house-medical'
                                        };
                                    @endphp
                                    <div class="poi-item d-flex align-items-center mb-2 px-3 py-2 border-0 shadow-sm" style="background: white; cursor: pointer; border-radius: 12px;" onclick="focusPoi('{{ $p['id'] }}')">
                                        <div class="bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; border-radius: 8px;">
                                            <i class="fa-solid fa-{{ $icon }} small"></i>
                                        </div>
                                        <div class="text-truncate small fw-bold text-dark">{{ $p['tags']['name'] ?? 'Saúde' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW BOTTOM SECTION: SECURITY & REAL ESTATE -->
        <div class="row g-4 mb-5">
            <!-- Segurança -->
            <div class="col-lg-6 col-md-12 reveal" style="animation-delay: 0.4s">
                <div class="card-pro overflow-hidden border-0 shadow-sm position-relative h-100" style="background: white;">
                    <!-- Decoração de fundo sutil -->
                    <div style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; background: var(--bs-{{ $sColor }}); opacity: 0.08; border-radius: 50%; filter: blur(40px);"></div>
                    <div style="position: absolute; bottom: -20px; left: -20px; width: 80px; height: 80px; background: var(--bs-{{ $sColor }}); opacity: 0.03; border-radius: 50%; filter: blur(30px);"></div>
                    
                    <div class="d-flex align-items-start gap-3 position-relative" style="z-index: 2;">
                        <div class="metric-icon-pro bg-{{ $sColor }} bg-opacity-10 text-{{ $sColor }} flex-shrink-0 shadow-none border border-{{ $sColor }} border-opacity-10" style="width: 56px; height: 56px; font-size: 26px; border-radius: 18px;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-0 fw-black text-dark" style="letter-spacing: -0.02em;">Índice de Segurança</h5>
                                    <div class="small text-muted opacity-75">Análise regional de proteção</div>
                                </div>
                                <span class="badge bg-{{ $sColor }} rounded-pill px-3 py-2 fw-black shadow-sm" style="font-size: 0.85rem; letter-spacing: 0.03em;">
                                    <i class="fa-solid fa-check-circle me-1 small"></i>{{ $report->safety_level ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="editorial-text small text-muted mb-0" style="line-height: 1.6; text-align: justify; font-size: 0.95rem;">
                                {{ $report->safety_description ?: 'Baseado em dados estatísticos regionais e infraestrutura de vigilância local.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($report->real_estate_json))
            <!-- Mercado Imobiliário -->
            <div class="col-lg-6 col-md-12 reveal" style="animation-delay: 0.5s">
                <div class="card-pro overflow-hidden border-0 shadow-sm h-100 d-flex flex-column" style="background: white;">
                    <!-- Cabeçalho com Tendência -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <div class="metric-icon-pro bg-primary bg-opacity-10 text-primary mb-0 me-3 shadow-none" style="width:48px;height:48px; border-radius: 14px;">
                                <i class="fa-solid fa-house-circle-check" style="font-size: 20px;"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-black text-dark" style="letter-spacing: -0.02em;">Mercado Imobiliário (IA)</h5>
                                <div class="small text-muted opacity-75">Análise preditiva de valores</div>
                            </div>
                        </div>
                        @php
                            $tend = strtoupper($report->real_estate_json['tendencia_valorizacao'] ?? 'ESTÁVEL');
                            $tColor = str_contains($tend, 'ALTA') ? 'success' : (str_contains($tend, 'BAIXA') ? 'danger' : 'warning');
                            $tIcon = str_contains($tend, 'ALTA') ? 'arrow-trend-up' : (str_contains($tend, 'BAIXA') ? 'arrow-trend-down' : 'minus');
                        @endphp
                        <span class="badge bg-{{ $tColor }} bg-opacity-10 text-{{ $tColor }} border border-{{ $tColor }} border-opacity-20 px-3 py-2 rounded-pill">
                            <i class="fa-solid fa-{{ $tIcon }} me-1 small"></i> 
                            <span class="fw-black" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ $tend }}</span>
                        </span>
                    </div>
                    
                    <!-- Preço em Destaque (Largura Total) -->
                    <div class="mb-4">
                        <div class="small fw-bold text-muted mb-2 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.08em;">Preço M² Médio Estimado</div>
                        <div class="h4 mb-0 fw-black text-dark" style="color: #1e293b; line-height: 1.2; font-size: 1.4rem;">
                            {{ $report->real_estate_json['preco_m2'] ?? 'Sob Consulta' }}
                        </div>
                    </div>

                    <!-- Perfil da Região (Seção Inferior) -->
                    <div class="mt-auto pt-3 border-top border-light">
                        <div class="small fw-bold text-muted mb-2 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.08em;">Perfil Comportamental da Região</div>
                        <div class="small text-secondary fw-medium" style="line-height: 1.5; text-align: justify; font-size: 0.9rem;">
                            {{ $report->real_estate_json['perfil_imoveis'] ?? 'Misto' }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

        <!-- HISTORY SECTION -->
        @if($report->history_extract)
            <div class="row mb-5 reveal">
                <div class="col-12">
                    <div class="card-pro border-0 shadow-lg overflow-hidden position-relative">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-primary text-white d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 14px;">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <h2 class="mb-0 fw-black text-dark" style="letter-spacing: -0.02em;">Legado e Cultura Regional</h2>
                        </div>
                        
                        <div class="position-relative">
                            <!-- Imagem History (Newspaper Style Float) -->
                            @if($wiki['image'] ?? null)
                                <div class="float-md-start me-md-4 mb-4 text-center text-md-start" style="max-width: 450px;">
                                    <div class="position-relative">
                                        <img src="{{ $wiki['image'] }}" class="img-fluid rounded-4 shadow-lg mb-2" style="max-height: 400px; object-fit: cover;" alt="História Local">
                                        <div class="position-absolute bottom-0 start-0 w-100 p-2 bg-dark bg-opacity-50 text-white rounded-bottom-4 d-md-none" style="backdrop-filter: blur(5px); font-size: 10px;">
                                            {{ $report->cidade }}
                                        </div>
                                    </div>
                                    <div class="small text-muted italic opacity-75 d-none d-md-block" style="font-size: 11px;">
                                        <i class="fa-solid fa-camera me-1"></i> Registro histórico: {{ $report->cidade }}
                                    </div>
                                </div>
                            @endif
                            
                            <div class="editorial-text drop-cap" style="text-align: justify;">
                                {!! nl2br(e($report->history_extract)) !!}
                                
                                <div class="mt-4 no-print">
                                    @if($wiki['desktop_url'] ?? null)
                                        <a href="{{ $wiki['desktop_url'] }}" target="_blank" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.1em;">
                                            <i class="fa-brands fa-wikipedia-w me-2"></i>Consultar Fonte Wikipedia
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- LEGAL DISCLAIMER -->
    <div class="container mb-5 no-print">
        <div class="p-4 rounded-4 border border-light" style="background: rgba(248, 250, 252, 0.5);">
            <div class="d-flex align-items-start gap-3">
                <i class="fa-solid fa-circle-info text-muted mt-1"></i>
                <div>
                    <h6 class="fw-bold text-dark small mb-1">Aviso de Isenção de Responsabilidade</h6>
                    <p class="text-muted mb-0" style="font-size: 0.8rem; line-height: 1.5; text-align: justify;">
                        As informações apresentadas neste relatório são consolidadas automaticamente a partir de fontes públicas da internet (Wikipedia, OpenStreetMap, Open-Meteo e IBGE) e processadas através de inteligência artificial. Devido à natureza dinâmica e colaborativa destas fontes, os dados podem conter imprecisões ou estar desatualizados. A plataforma não se responsabiliza pela veracidade absoluta das informações ou por decisões tomadas com base nestes dados. Recomendamos sempre a verificação presencial e a consulta a órgãos oficiais.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- COMPARISON MODAL-LIKE UI -->
    <div class="compare-fab no-print" onclick="toggleCompare()" title="Comparar CEPs">
        <i class="fa-solid fa-right-left"></i>
    </div>

    <div id="compare-panel" class="compare-panel no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-black text-dark">⚔️ Iniciar Comparativo</h5>
            <button class="btn btn-link text-muted p-0" onclick="toggleCompare()"><i class="fa-solid fa-times"></i></button>
        </div>
        <p class="small text-muted mb-4 text-justify">Coloque dois bairros lado a lado. Digite o novo CEP para gerar o confronto de scores.</p>
        
        <div class="position-relative mb-4">
            <input type="text" id="v-compare-input" class="form-control rounded-4 border-light bg-light py-3 ps-4 shadow-none" placeholder="Digite o novo CEP..." maxlength="9">
            <button class="btn btn-primary position-absolute end-0 top-0 mt-1 me-1 h-75 rounded-4 px-4 fw-bold" onclick="startComparison()">
                CONFRONTAR
            </button>
        </div>
        
        <div class="d-flex align-items-center gap-2 p-3 bg-indigo-50 rounded-4 border border-indigo-100">
            <div class="bg-white p-2 rounded-circle shadow-sm">💡</div>
            <div class="small text-indigo-900 leading-tight">Compare Segurança, Comércio e IDH de forma instantânea.</div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-dark text-white-50 py-5 mt-5">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-md-4 text-center text-md-start">
                    <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
                        <div class="rounded-3 overflow-hidden" style="width: 38px; height: 38px;">
                            <img src="{{ asset('favicon.png') }}" class="w-100 h-100 object-fit-cover" style="filter: grayscale(1) brightness(1.5);" alt="Footer Logo">
                        </div>
                        <div>
                            <h3 class="text-white mb-0 h6 fw-black text-uppercase tracking-tighter">{{ config('app.name') }}</h3>
                            <p class="small mb-0 opacity-50" style="font-size: 10px;">Data Intelligence</p>
                        </div>
                    </div>
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

            // Custom Main Marker (CEP Point) - Premium Pin
            const pulseIcon = L.divIcon({
                className: 'main-dest-pin',
                html: `<div style="position:relative; width:50px; height:50px; display:flex; align-items:center; justify-content:center;">
                    <div style="position:absolute; width:100%; height:100%; background:rgba(79, 70, 229, 0.25); border-radius:50%; animation:pulse 2s infinite;"></div>
                    <div style="position:absolute; width:60%; height:60%; background:rgba(79, 70, 229, 0.15); border-radius:50%; animation:pulse 2s infinite 0.5s;"></div>
                    <div style="position:relative; width:28px; height:28px; background:#4F46E5; border:3px solid white; border-radius:12px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 15px rgba(79, 70, 229, 0.4); transform: rotate(-45deg) translateY(-2px);">
                        <i class="fa-solid fa-house text-white" style="transform: rotate(45deg); font-size: 12px;"></i>
                    </div>
                </div>`,
                iconSize: [50, 50],
                iconAnchor: [25, 25]
            });

            L.marker([lat, lng], { icon: pulseIcon }).addTo(map);

            const poiLayers = L.layerGroup().addTo(map);
            const poiData = [];

            // Icon Mapping for POIs
            function getPoiStyle(poi) {
                const tag = poi.tags.amenity || poi.tags.shop || poi.tags.highway || '';
                
                let icon = 'location-dot';
                let color = '#64748b';
                let category = 'comercio';

                // Saúde
                if (['hospital', 'clinic', 'doctors'].includes(tag)) {
                    icon = 'hospital'; color = '#10b981'; category = 'saude';
                } else if (tag === 'pharmacy') {
                    icon = 'pills'; color = '#10b981'; category = 'saude';
                }
                // Ensino & Templos
                else if (['school', 'university', 'kindergarten'].includes(tag)) {
                    icon = 'graduation-cap'; color = '#6366f1'; category = 'ensino';
                } else if (tag === 'place_of_worship') {
                    icon = 'church'; color = '#6366f1'; category = 'ensino';
                }
                // Comércio & Alimentação
                else if (['restaurant', 'cafe', 'fast_food', 'bakery'].includes(tag)) {
                    icon = 'utensils'; color = '#d97706'; category = 'comercio';
                } else if (['supermarket', 'convenience', 'mall'].includes(tag)) {
                    icon = 'cart-shopping'; color = '#d97706'; category = 'comercio';
                } else if (tag === 'fuel') {
                    icon = 'gas-pump'; color = '#0f172a'; category = 'comercio';
                }
                // Serviços & Lazer
                else if (['police', 'fire_station'].includes(tag)) {
                    icon = 'shield-halved'; color = '#ef4444'; category = 'servicos';
                } else if (['park', 'playground', 'sports_centre'].includes(tag)) {
                    icon = 'tree'; color = '#15803d'; category = 'servicos';
                } else if (['bank', 'post_office'].includes(tag)) {
                    icon = 'building-columns'; color = '#0f172a'; category = 'servicos';
                }

                return { icon, color, category };
            }

            // Filterable POIs
            pois.forEach(poi => {
                if (!poi.lat || !poi.lon) return;
                
                const style = getPoiStyle(poi);
                const rawType = poi.tags.amenity || poi.tags.shop || poi.tags.highway || poi.tags.historic || 'Comércio';
                const type = translations[rawType] || rawType;

                const poiIcon = L.divIcon({
                    className: 'custom-poi-marker',
                    html: `<div style="background:${style.color}; width:30px; height:30px; border-radius:10px; border:2px solid white; display:flex; align-items:center; justify-content:center; color:white; box-shadow:0 3px 6px rgba(0,0,0,0.2); transform:rotate(45deg);">
                        <i class="fa-solid fa-${style.icon}" style="transform:rotate(-45deg); font-size:12px;"></i>
                    </div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                const marker = L.marker([poi.lat, poi.lon], { icon: poiIcon }).bindPopup(`
                    <div class="p-2">
                        <div class="fw-bold mb-1 text-dark">${poi.tags.name || 'Local Estabelecido'}</div>
                        <div class="badge bg-light text-primary text-uppercase" style="font-size: 10px">${type}</div>
                    </div>
                `);

                poiLayers.addLayer(marker);
                poiData.push({ marker, category: style.category, id: poi.id });
            });

            // Global Focus POI Function
            window.focusPoi = function(id) {
                const item = poiData.find(p => p.id == id);
                if (item) {
                    // Se o item estiver visível e com popup aberto, fecha. Caso contrário, abre.
                    if (map.hasLayer(item.marker) && item.marker.isPopupOpen()) {
                        item.marker.closePopup();
                        // Volta para o ponto central original
                        map.flyTo([lat, lng], 15);
                    } else {
                        // Garante que o item esteja na camada de filtros atual
                        item.marker.addTo(map);
                        
                        // Cria os limites (bounds) incluindo o CEP e o Ponto clicado
                        const bounds = L.latLngBounds([lat, lng], item.marker.getLatLng());
                        
                        // Ajusta o mapa para mostrar AMBOS na tela simultaneamente
                        map.fitBounds(bounds, { 
                            padding: [80, 80], 
                            maxZoom: 17, 
                            animate: true, 
                            duration: 1.2 
                        });
                        
                        item.marker.openPopup();
                    }
                }
            };

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



            // ================== NOVO MODO COMPARATIVO ==================
            window.toggleCompare = function() {
                const panel = document.getElementById('compare-panel');
                panel.classList.toggle('active');
                if (panel.classList.contains('active')) {
                    document.getElementById('v-compare-input').focus();
                }
            };

            window.startComparison = function() {
                const newCepRaw = document.getElementById('v-compare-input').value;
                const newCep = newCepRaw.replace(/\D/g, '');
                
                if (newCep.length === 8) {
                    const currentCep = '{{ $report->cep }}';
                    const loader = document.getElementById('loader');
                    
                    // Show loader
                    loader.setAttribute('style', 'display: flex !important');
                    
                    // Loader Animation Logic
                    const loaderSteps = [
                        { text: "Acessando satélites...", progress: 30 },
                        { text: "Calculando IDHM comparativo...", progress: 60 },
                        { text: "Sincronizando Gemini AI...", progress: 85 },
                        { text: "Quase pronto...", progress: 95 }
                    ];
                    
                    let step = 0;
                    const textEl = document.getElementById('loader-text');
                    const barEl = document.getElementById('progress-bar');
                    
                    setInterval(() => {
                        if (step < loaderSteps.length) {
                            textEl.innerText = loaderSteps[step].text;
                            barEl.style.width = loaderSteps[step].progress + '%';
                            step++;
                        }
                    }, 2500);

                    window.location.href = `/compare/${currentCep}/${newCep}`;
                } else {
                    alert("Por favor, digite um CEP válido com 8 dígitos.");
                }
            };
            
            document.getElementById('v-compare-input').addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '');
                if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5, 8);
                e.target.value = v;
            });
            
            // Fechar painel com a tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const panel = document.getElementById('compare-panel');
                    if (panel && panel.classList.contains('active')) toggleCompare();
                }
            });
        });
    </script>
    
    <style>
        @keyframes pulse {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }

        /* PRINT / PDF FULL FIDELITY */
        @media print {
            .no-print, .btn-pro { display: none !important; }
            .card-pro { 
                break-inside: avoid !important; 
                page-break-inside: avoid !important;
                border: 1px solid rgba(0,0,0,0.05) !important;
                box-shadow: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body { 
                background: #f8fafc !important; 
                color: #1e293b !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .metric-icon-pro { 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .badge-pro { 
                border: 1px solid rgba(0,0,0,0.1) !important;
                background: white !important;
                color: black !important;
            }
            #map { height: 400px !important; }
        }

    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
