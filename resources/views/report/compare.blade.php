<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparativo Premium: {{ $reportA->cep }} vs {{ $reportB->cep }} | Raio-X AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #f59e0b;
            --bg-body: #f8fafc;
            --dark: #0f172a;
            --card-radius: 24px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--dark);
            overflow-x: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 80px 0 120px;
            color: white;
            text-align: center;
            position: relative;
        }

        .vs-circle {
            width: 80px;
            height: 80px;
            background: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 900;
            font-size: 24px;
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.4);
            margin: -40px auto;
            position: relative;
            z-index: 10;
            border: 4px solid white;
        }

        .container-main {
            margin-top: -60px;
            position: relative;
            z-index: 20;
            padding-bottom: 80px;
        }

        .comparison-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.02);
            height: 100%;
        }

        .metric-value {
            font-size: 4rem;
            font-weight: 900;
            letter-spacing: -3px;
            line-height: 1;
            margin: 10px 0;
            color: var(--primary);
        }

        .metric-label {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 2px;
            color: #64748b;
        }

        .analysis-card {
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            border-radius: var(--card-radius);
            padding: 40px;
            border: 1px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-top: 40px;
        }

        .progress-custom {
            height: 12px;
            border-radius: 99px;
            background: #e2e8f0;
            margin: 15px 0 25px;
        }

        .delta-badge {
            font-size: 0.75rem;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 99px;
            margin-left: 10px;
        }

        .delta-plus { background: #d1fae5; color: #065f46; }
        .delta-minus { background: #fee2e2; color: #991b1b; }

        .radar-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }

        .radar-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
            text-align: center;
        }

        .radar-icon {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .radar-val {
            font-size: 24px;
            font-weight: 900;
            display: block;
        }

        .radar-txt {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 800;
            color: #64748b;
        }

        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 12px 20px;
            border-radius: 14px;
            text-decoration: none;
            backdrop-filter: blur(10px);
            font-weight: 600;
            transition: all 0.3s;
        }

        .header-title {
            font-size: 3.5rem;
            font-weight: 900;
            letter-spacing: -2px;
        }

        @media (max-width: 992px) {
            .header-title { font-size: 2.2rem; }
            .metric-value { font-size: 3rem; }
        }
    </style>
</head>
<body>

    <header class="header-section">
        <a href="javascript:history.back()" class="back-btn"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
        <div class="container">
            <span class="metric-label text-white-50" style="letter-spacing: 5px;">Comparativo de Inteligência</span>
            <h1 class="header-title mt-2">Duelo de Microterritórios</h1>
            <p class="opacity-75 lead fw-medium">{{ $reportA->cidade }}/{{ $reportA->uf }} ⚔️ {{ $reportB->cidade }}/{{ $reportB->uf }}</p>
        </div>
    </header>

    <div class="container container-main">
        <div class="row g-4 align-items-stretch">
            <!-- Region A -->
            <div class="col-lg-5">
                <div class="comparison-card">
                    <span class="metric-label">CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $reportA->cep) }}</span>
                    <h2 class="h4 fw-black mt-1 mb-0">{{ $reportA->bairro }}</h2>
                    <p class="small text-muted mb-4">{{ $reportA->territorial_classification }}</p>

                    <div class="metric-value">{{ $comparison->comparison_data['metrics_a']['total_score'] }}</div>
                    <span class="metric-label">Score Territorial AI</span>

                    <div class="radar-box">
                        <div class="radar-item">
                            <i class="fa-solid fa-hotel radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_a']['infra'] }}</span>
                            <span class="radar-txt">Infra</span>
                        </div>
                        <div class="radar-item">
                            <i class="fa-solid fa-bus radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_a']['mobility'] }}</span>
                            <span class="radar-txt">Mobilidade</span>
                        </div>
                        <div class="radar-item">
                            <i class="fa-solid fa-tree radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_a']['leisure'] }}</span>
                            <span class="radar-txt">Lazer</span>
                        </div>
                        <div class="radar-item">
                            <i class="fa-solid fa-store radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_a']['commerce'] }}</span>
                            <span class="radar-txt">Comércio</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VS Divider -->
            <div class="col-lg-2 d-flex align-items-center justify-content-center py-4">
                <div class="vs-circle">VS</div>
            </div>

            <!-- Region B -->
            <div class="col-lg-5">
                <div class="comparison-card">
                    <span class="metric-label">CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $reportB->cep) }}</span>
                    <h2 class="h4 fw-black mt-1 mb-0">{{ $reportB->bairro }}</h2>
                    <p class="small text-muted mb-4">{{ $reportB->territorial_classification }}</p>

                    <div class="metric-value" style="color: var(--accent);">{{ $comparison->comparison_data['metrics_b']['total_score'] }}</div>
                    <span class="metric-label">Score Territorial AI</span>

                    <div class="radar-box">
                        <div class="radar-item">
                            <i class="fa-solid fa-hotel radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_b']['infra'] }}</span>
                            <span class="radar-txt">Infra</span>
                        </div>
                        <div class="radar-item">
                            <i class="fa-solid fa-bus radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_b']['mobility'] }}</span>
                            <span class="radar-txt">Mobilidade</span>
                        </div>
                        <div class="radar-item">
                            <i class="fa-solid fa-tree radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_b']['leisure'] }}</span>
                            <span class="radar-txt">Lazer</span>
                        </div>
                        <div class="radar-item">
                            <i class="fa-solid fa-store radar-icon"></i>
                            <span class="radar-val">{{ $comparison->comparison_data['metrics_b']['commerce'] }}</span>
                            <span class="radar-txt">Comércio</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Differential Analysis -->
        <div class="analysis-card reveal mt-5">
            <div class="row">
                <div class="col-xl-4 border-end border-light">
                    <h3 class="h2 fw-black mb-4">Análise de Diferenciais</h3>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold small text-uppercase">Vantagem Infraestrutura</span>
                            <span class="delta-badge {{ $comparison->infra_diff >= 0 ? 'delta-plus' : 'delta-minus' }}">
                                {{ $comparison->infra_diff > 0 ? '+' : '' }}{{ $comparison->infra_diff }} itens
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold small text-uppercase">Vantagem Mobilidade</span>
                            <span class="delta-badge {{ $comparison->mobilidade_diff >= 0 ? 'delta-plus' : 'delta-minus' }}">
                                {{ $comparison->mobilidade_diff > 0 ? '+' : '' }}{{ $comparison->mobilidade_diff }} itens
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold small text-uppercase">Vantagem Lazer</span>
                            <span class="delta-badge {{ $comparison->lazer_diff >= 0 ? 'delta-plus' : 'delta-minus' }}">
                                {{ $comparison->lazer_diff > 0 ? '+' : '' }}{{ $comparison->lazer_diff }} itens
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-8 ps-xl-5 mt-4 mt-xl-0">
                    <h4 class="fw-black text-primary mb-3"><i class="fa-solid fa-robot me-2"></i>Veredito da Inteligência Artificial</h4>
                    <div class="lead text-secondary" style="text-align: justify; line-height: 1.8;">
                        {!! nl2br(e($comparison->analysis_text)) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('home') }}" class="btn btn-dark rounded-pill px-5 py-3 fw-bold shadow-lg">
                Fazer nova pesquisa
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
