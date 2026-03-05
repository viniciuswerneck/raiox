<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparativo: {{ $report1->cep }} vs {{ $report2->cep }} | Raio-X AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <style>
        :root {
            --primary: #059669; /* Emerald - Anti-cliché */
            --primary-dark: #047857;
            --accent: #f97316; /* Orange */
            --bg-body: #f1f5f9;
            --card-radius: 20px;
            --dark: #0f172a;
            --emerald-50: #ecfdf5;
            --slate-100: #f1f5f9;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--dark);
            overflow-x: hidden;
            letter-spacing: -0.01em;
        }

        .compare-header {
            background: linear-gradient(135deg, #022c22 0%, #064e3b 100%);
            padding: 80px 0;
            color: white;
            text-align: center;
            margin-bottom: -60px;
            position: relative;
            overflow: hidden;
        }

        .compare-header::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.83L1.242 55.044l-.83-.83L54.627 0zM59.172 5.044l.83.83L5.43 59.873l-.83-.83L59.172 5.044z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
        }

        .vs-badge {
            background: var(--accent);
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 900;
            font-size: 1.4rem;
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.4);
            display: inline-block;
            margin: 0 20px;
            transform: rotate(-3deg);
            z-index: 2;
            position: relative;
        }

        .container-compare {
            margin-top: 20px;
            padding-bottom: 100px;
            position: relative;
            z-index: 5;
        }

        .card-pro {
            background: white;
            border-radius: var(--card-radius);
            padding: 30px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 20px 40px -20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            height: 100%;
            transition: transform 0.3s ease;
        }

        .card-pro:hover {
            transform: translateY(-5px);
        }

        .glass-score {
            background: white;
            border-radius: var(--card-radius);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .score-val {
            font-size: 5.5rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -4px;
            margin: 15px 0;
        }

        .comparison-label {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.8rem;
            color: #64748b;
            letter-spacing: 2px;
            margin-bottom: 5px;
            display: block;
        }

        .metric-row {
            padding: 18px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .metric-row:last-child { border-bottom: none; }

        .metric-mini {
            background: #f8fafc;
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .metric-mini i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .metric-mini span {
            font-size: 0.7rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
        }

        .metric-mini .val {
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--dark);
            display: block;
        }

        .progress-slim {
            height: 10px;
            background: #f1f5f9;
            border-radius: 99px;
            margin-top: 10px;
            overflow: hidden;
        }

        .progress-bar { border-radius: 99px; }

        .back-btn {
            position: fixed;
            top: 25px;
            left: 25px;
            z-index: 1000;
            background: var(--dark);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            text-decoration: none;
            transition: all 0.3s;
        }

        .back-btn:hover { transform: translateX(-5px); background: var(--primary); }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        @media (max-width: 992px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .compare-header { padding: 60px 15px; }
            .score-val { font-size: 4rem; }
            .vs-badge { margin: 20px 0; display: block; width: fit-content; margin-left: auto; margin-right: auto; }
        }

        .analysis-text {
            font-size: 1rem;
            line-height: 1.6;
            color: #334155;
            text-align: left;
        }

        .reveal {
            animation: move-up 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        @keyframes move-up {
            from { transform: translateY(60px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .winner-crown {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            color: #fbbf24;
            transform: rotate(15deg);
        }

        .tag-winner {
            background: #d1fae5;
            color: #065f46;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 99px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <a href="javascript:history.back()" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>

    <header class="compare-header">
        <div class="container">
            <h6 class="text-white-50 text-uppercase tracking-widest mb-3" style="letter-spacing: 4px;">Duelo de Localizações ⚔️</h6>
            <h1 class="display-3 fw-bold mb-0">
                CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $report1->cep) }}
                <span class="vs-badge">VS</span>
                CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $report2->cep) }}
            </h1>
            <p class="lead opacity-75 mt-3 fw-medium">{{ $report1->cidade }}/{{ $report1->uf }} vs {{ $report2->cidade }}/{{ $report2->uf }}</p>
        </div>
    </header>

    <div class="container container-compare">
        <div class="dashboard-grid">
            
            @php
                $reports = [$report1, $report2];
                $data = [];
                
                foreach($reports as $index => $r) {
                    $pois = is_array($r->pois_json) ? $r->pois_json : [];
                    $poisCount = count($pois);
                    
                    // POI Categories
                    $catSaude = 0; $catEdu = 0; $catFood = 0; $catShop = 0;
                    foreach($pois as $p) {
                        $tags = $p['tags'] ?? [];
                        $amenity = $tags['amenity'] ?? '';
                        $shop = $tags['shop'] ?? '';
                        
                        if (in_array($amenity, ['hospital', 'pharmacy', 'clinic', 'dentist', 'doctors', 'veterinary'])) $catSaude++;
                        if (in_array($amenity, ['school', 'university', 'kindergarten', 'library', 'childcare'])) $catEdu++;
                        if (in_array($amenity, ['restaurant', 'cafe', 'bar', 'fast_food', 'pub'])) $catFood++;
                        if (!empty($shop) || in_array($amenity, ['marketplace', 'mall', 'supermarket', 'convenience'])) $catShop++;
                    }

                    // Safety Level
                    $safetyRaw = strtoupper($r->safety_level ?? '');
                    $safetyVal = match(true) {
                        str_contains($safetyRaw, 'ALT') => 92,
                        str_contains($safetyRaw, 'MODERAD') || str_contains($safetyRaw, 'MEDI') => 70,
                        str_contains($safetyRaw, 'BAIX') => 45,
                        default => 55
                    };
                    
                    // Score Calculation
                    $commerceScore = min(100, (int)($poisCount * 1.5)); 
                    $infraScore = $r->sanitation_rate ?: 50;
                    $cultureScore = $r->history_extract ? min(100, (int)(strlen($r->history_extract) / 20)) : 35;
                    $finalScore = round(($safetyVal * 0.4) + ($commerceScore * 0.3) + ($infraScore * 0.2) + ($cultureScore * 0.1));
                    
                    $data[$index] = [
                        'val' => $finalScore,
                        'safety' => $safetyVal,
                        'commerce' => $commerceScore,
                        'infra' => $infraScore,
                        'pois' => $poisCount,
                        'cat' => [
                            'saude' => $catSaude,
                            'edu' => $catEdu,
                            'food' => $catFood,
                            'shop' => $catShop
                        ],
                        'aqi' => $r->air_quality_index,
                        'temp' => $r->climate_json['current']['temperature'] ?? $r->climate_json['current_weather']['temperature'] ?? 'N/A',
                        'idhm' => $r->idhm ?: ($r->raw_ibge_data['idhm'] ?? null),
                        'pop' => $r->populacao ?: ($r->raw_ibge_data['population'] ?? null),
                        'income' => $r->average_income,
                        'color' => $finalScore >= 75 ? '#059669' : ($finalScore >= 60 ? '#f59e0b' : '#ef4444')
                    ];
                }
            @endphp

            @foreach($reports as $index => $r)
                <div class="column-report reveal" style="animation-delay: {{ $index * 0.2 }}s">
                    <div class="glass-score">
                        @if($data[$index]['val'] > ($data[1-$index]['val'] ?? 0))
                           <div class="winner-crown"><i class="fa-solid fa-crown"></i></div>
                        @endif

                        <span class="comparison-label">Score Raio-X AI</span>
                        <div class="score-val" style="color: {{ $data[$index]['color'] }}">{{ $data[$index]['val'] }}</div>
                        
                        <div class="row g-3 mt-4">
                            <div class="col-4">
                                <div class="metric-mini">
                                    <i class="fa-solid fa-wind"></i>
                                    <span>Ar (AQI)</span>
                                    <div class="val">{{ $data[$index]['aqi'] ?? '?' }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="metric-mini">
                                    <i class="fa-solid fa-temperature-half"></i>
                                    <span>Clima</span>
                                    <div class="val">{{ $data[$index]['temp'] }}{{ is_numeric($data[$index]['temp']) ? '°' : '' }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="metric-mini">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <span>IDHM</span>
                                    <div class="val">{{ number_format($data[$index]['idhm'], 3) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-pro mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <h5 class="fw-bold mb-0"><i class="fa-solid fa-bolt me-2 text-primary"></i>Indicadores Críticos</h5>
                            @if($data[$index]['val'] > ($data[1-$index]['val'] ?? 0))
                                <span class="tag-winner"><i class="fa-solid fa-check me-1"></i>Melhor Escolha</span>
                            @endif
                        </div>
                        
                        <div class="metric-row">
                            <div class="d-flex justify-content-between">
                                <span class="small fw-bold">Segurança & Ordem</span>
                                <span class="small fw-black text-slate-800">{{ $data[$index]['safety'] }}%</span>
                            </div>
                            <div class="progress-slim">
                                <div class="progress-bar" style="width: {{ $data[$index]['safety'] }}%; background: #10b981;"></div>
                            </div>
                        </div>

                        <div class="metric-row">
                            <div class="d-flex justify-content-between">
                                <span class="small fw-bold">Infraestrutura Social</span>
                                <span class="small fw-black text-slate-800">{{ $data[$index]['infra'] }}%</span>
                            </div>
                            <div class="progress-slim">
                                <div class="progress-bar" style="width: {{ $data[$index]['infra'] }}%; background: #06b6d4;"></div>
                            </div>
                        </div>

                        <div class="metric-row">
                            <div class="d-flex justify-content-between">
                                <span class="small fw-bold">Renda Média Mensal</span>
                                <span class="small fw-black text-slate-800">R$ {{ number_format($data[$index]['income'], 0, ',', '.') }}</span>
                            </div>
                            <div class="progress-slim">
                                <div class="progress-bar" style="width: {{ min(100, ($data[$index]['income'] / 12000) * 100) }}%; background: #0f172a;"></div>
                            </div>
                        </div>

                        <div class="metric-row">
                            <div class="d-flex justify-content-between">
                                <span class="small fw-bold">Densidade Populacional (Cidade)</span>
                                <span class="small fw-black text-slate-800">{{ number_format($data[$index]['pop'], 0, ',', '.') }} hab.</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-pro mb-4">
                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-store me-2 text-primary"></i>Conveniência (Raio 10km)</h5>
                        <p class="text-muted small">Detalhamento de estabelecimentos mapeados na região.</p>
                        
                        <div class="categories-grid">
                            <div class="metric-mini">
                                <i class="fa-solid fa-heart-pulse text-danger"></i>
                                <span>Saúde</span>
                                <div class="val">{{ $data[$index]['cat']['saude'] }}</div>
                            </div>
                            <div class="metric-mini">
                                <i class="fa-solid fa-graduation-cap text-info"></i>
                                <span>Educação</span>
                                <div class="val">{{ $data[$index]['cat']['edu'] }}</div>
                            </div>
                            <div class="metric-mini">
                                <i class="fa-solid fa-utensils text-warning"></i>
                                <span>Gastronomia</span>
                                <div class="val">{{ $data[$index]['cat']['food'] }}</div>
                            </div>
                            <div class="metric-mini">
                                <i class="fa-solid fa-cart-shopping text-success"></i>
                                <span>Compras</span>
                                <div class="val">{{ $data[$index]['cat']['shop'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-pro mb-4" style="border-left: 5px solid var(--primary);">
                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-robot me-2 text-primary"></i>Conclusão da IA</h5>
                        <div class="analysis-text">
                            {!! nl2br(e($r->history_extract)) ?: 'Aguardando síntese detalhada...' !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        
        <div class="text-center mt-5">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-5 py-3 fw-bold">
                <i class="fa-solid fa-magnifying-glass me-2"></i>Fazer Nova Pesquisa
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
