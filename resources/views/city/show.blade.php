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
    </style>
</head>
<body>

    <section class="hero-section">
        <img src="{{ $city->image_url ?: 'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?q=80&w=1200&auto=format&fit=crop' }}" class="hero-bg-img" alt="{{ $city->name }}">
        <div class="hero-bg-overlay"></div>
        <div class="container relative z-2 text-center text-md-start">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/" class="text-white-50 text-decoration-none">Brasil</a></li>
                    <li class="breadcrumb-item"><a href="#" class="text-white-50 text-decoration-none">{{ $city->uf }}</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $city->name }}</li>
                </ol>
            </nav>

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
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border">
                            <i class="fa-solid fa-pills text-danger mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['pharmacies'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Farmácias</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border">
                            <i class="fa-solid fa-gas-pump text-warning mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['gas_stations'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Postos</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border">
                            <i class="fa-solid fa-cart-shopping text-success mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['markets'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Mercados</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border">
                            <i class="fa-solid fa-hospital text-primary mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['health'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Saúde</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border">
                            <i class="fa-solid fa-graduation-cap text-info mb-2 h4"></i>
                            <div class="h5 fw-black mb-0">{{ $essentials['education'] ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Educação</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card-pro text-center p-3 h-100 bg-white shadow-sm border">
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
                                <div class="p-3 rounded-4 bg-light border d-flex align-items-center h-100 transition-all hover-white shadow-sm">
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
                                            <div class="text-muted small mb-3">#{{ $index + 1 }} do Ranking</div>
                                            <div class="h3 fw-black {{ $item['avg_score'] >= 80 ? 'text-success' : 'text-primary' }} mb-0">
                                                {{ $item['avg_score'] }} <span style="font-size: 12px; opacity: 0.6;">pts</span>
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
                                Ranking Completo de Bairros ({{ $city->name }})
                            </h3>
                            <p class="text-muted mb-0">
                                Demais bairros ordenados pelo <strong>Escore de Vizinhança</strong> mapeado.
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-light text-dark border p-2 px-3 rounded-pill">
                                <i class="fa-solid fa-sync fa-spin me-2 text-primary"></i> Atualizado em Real-Time
                            </span>
                        </div>
                    </div>

                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
                        @foreach($rest ?? [] as $item)
                            <div class="col">
                                <div class="p-3 h-100 rounded-4 bg-light border transition-all hover-white shadow-sm" style="cursor: default;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <i class="fa-solid fa-location-dot text-primary-50" style="font-size: 12px;"></i>
                                        @php
                                            $colorStyle = $item['avg_score'] >= 80 
                                                ? 'background: #dcfce7; color: #16a34a;' 
                                                : ($item['avg_score'] >= 50 ? 'background: #fff3cd; color: #856404;' : 'background: #fee2e2; color: #dc2626;');
                                        @endphp
                                        <span class="badge rounded-pill px-3 py-2" style="font-size: 12px; font-weight: 800; {{ $colorStyle }}">
                                            {{ $item['avg_score'] }} pts
                                        </span>
                                    </div>
                                    <div class="fw-bold text-truncate" title="{{ $item['name'] }}" style="font-size: 14px;">
                                        {{ $item['name'] }}
                                    </div>
                                    <div class="small text-muted" style="font-size: 11px;">Escore Territorial</div>
                                </div>
                            </div>
                        @endforeach
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

</body>
</html>
