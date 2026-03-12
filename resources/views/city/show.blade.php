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
    </style>
</head>
<body>

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
                    <div class="stats-badge py-3 px-4">
                        <span class="d-block small opacity-75">ESCORE DE VIZINHANÇA MÉDIO</span>
                        <span class="h2 fw-black text-white m-0">{{ $city->stats_cache['avg_score'] ?? '0.0' }} <small style="font-size: 0.5em; opacity: 0.7;">pts</small></span>
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
                <div class="card-pro">
                    <div class="metric-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <h4 class="h6 text-secondary text-uppercase fw-bold mb-1">Infraestrutura Total</h4>
                    <p class="h3 fw-black mb-0">{{ number_format($city->stats_cache['total_pois'] ?? 0, 0, ',', '.') }}</p>
                    <small class="text-muted">Pontos de interesse catalogados</small>
                </div>
            </div>

            <!-- História e Resumo -->
            <div class="col-lg-8">
                <div class="card-pro">
                    <h3 class="fw-black mb-4"><i class="fa-solid fa-scroll me-2 text-primary"></i> Contexto Cultural e Geográfico</h3>
                    <div class="editorial-text drop-cap">
                        {!! nl2br(e($city->history_extract ?: 'Aguardando processamento da IA...')) !!}
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
                </div>
            </div>

            <!-- Território Mapeado (Abaixo do Texto) -->
            <div class="col-12">
                <div class="card-pro bg-white" style="background: white !important;">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <h3 class="fw-black mb-1">
                                <i class="fa-solid fa-ranking-star me-2 text-primary"></i> 
                                Ranking de Qualidade de Vida por Bairro
                            </h3>
                            <p class="text-muted mb-0">
                                Bairros ordenados pelo <strong>Escore de Vizinhança</strong> (média real dos CEPs mapeados).
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-light text-dark border p-2 px-3 rounded-pill">
                                <i class="fa-solid fa-sync fa-spin me-2 text-primary"></i> Atualizado em Real-Time
                            </span>
                        </div>
                    </div>

                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
                        @foreach($city->stats_cache['neighborhood_list'] ?? [] as $item)
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
