<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Explorar Rankings Territoriais</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --glass: rgba(255, 255, 255, 0.85);
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

        .header-section {
            background: var(--dark);
            padding: 80px 0 120px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-grid {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 32px 32px;
            opacity: 0.5;
        }

        .ranking-container {
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }

        .card-ranking {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: var(--card-radius);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 50px -15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 40px;
        }

        .nav-pills-custom {
            display: flex;
            gap: 10px;
            background: #f1f5f9;
            padding: 6px;
            border-radius: 16px;
            margin-bottom: 30px;
            width: fit-content;
        }

        .nav-pills-custom .nav-link {
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 700;
            color: var(--secondary);
            font-size: 14px;
            border: none;
            transition: all 0.3s;
        }

        .nav-pills-custom .nav-link.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        /* Podium Style */
        .podium-row {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 20px;
            margin-bottom: 60px;
        }

        .podium-item {
            flex: 1;
            max-width: 280px;
            text-align: center;
            position: relative;
            transition: transform 0.3s;
        }

        .podium-item:hover { transform: translateY(-10px); }

        .podium-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }

        .podium-1 { order: 2; }
        .podium-1 .podium-card { border-top: 6px solid #f59e0b; padding-bottom: 40px; }
        .podium-2 { order: 1; }
        .podium-2 .podium-card { border-top: 6px solid #94a3b8; }
        .podium-3 { order: 3; }
        .podium-3 .podium-card { border-top: 6px solid #b45309; }

        .medal-icon {
            font-size: 32px;
            margin-bottom: 15px;
            display: block;
        }

        .ranking-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .ranking-table tr {
            background: white;
            transition: all 0.2s;
        }

        .ranking-table tr:hover {
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .ranking-table td {
            padding: 20px;
            vertical-align: middle;
            border: none;
        }

        .ranking-table td:first-child { border-radius: 16px 0 0 16px; width: 80px; text-align: center; }
        .ranking-table td:last-child { border-radius: 0 16px 16px 0; }

        .rank-num {
            font-weight: 900;
            font-size: 1.2rem;
            color: var(--secondary);
            opacity: 0.5;
        }

        .score-badge {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
        }

        .btn-view {
            background: #f1f5f9;
            color: var(--primary);
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 700;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>

    <header class="header-section">
        <div class="header-grid"></div>
        <div class="container position-relative">
            <a href="{{ route('home') }}" class="text-white opacity-50 text-decoration-none small fw-bold mb-4 d-inline-block">
                <i class="fa-solid fa-arrow-left me-2"></i>VOLTAR PARA A BUSCA
            </a>
            <h1 class="display-4 fw-black mb-3">Ranking de Autoridade <span class="text-primary">Territorial</span></h1>
            <p class="lead opacity-75 mb-4">Os melhores lugares para morar, trabalhar e investir no Brasil.</p>
            
            <div class="d-inline-block px-4 py-3 rounded-4 border border-white/10" style="background: rgba(255,255,255,0.03); backdrop-filter: blur(10px); max-width: 600px;">
                <p class="small text-white-50 mb-0 leading-relaxed font-semibold">
                    <i class="fa-solid fa-circle-info text-primary me-2"></i>
                    Este ranking é gerado dinamicamente com base nas **pesquisas realizadas por nossos usuários**. 
                    Quer ver sua cidade ou bairro aqui? Basta <a href="{{ route('home') }}" class="text-primary text-decoration-none hover:underline">realizar uma busca por CEP</a> para processar os dados e incluí-la no sistema.
                </p>
            </div>
        </div>
    </header>

    <div class="container ranking-container">
        <div class="card-ranking">
            
            <!-- Filters -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 gap-3">
                <nav class="nav-pills-custom">
                    <a href="{{ route('ranking.index', ['category' => 'all', 'type' => $locationType]) }}" class="nav-link {{ $category == 'all' ? 'active' : '' }}">Geral</a>
                    <a href="{{ route('ranking.index', ['category' => 'safety', 'type' => $locationType]) }}" class="nav-link {{ $category == 'safety' ? 'active' : '' }}">Segurança</a>
                    <a href="{{ route('ranking.index', ['category' => 'walk', 'type' => $locationType]) }}" class="nav-link {{ $category == 'walk' ? 'active' : '' }}">Caminhabilidade</a>
                    <a href="{{ route('ranking.index', ['category' => 'air', 'type' => $locationType]) }}" class="nav-link {{ $category == 'air' ? 'active' : '' }}">Ar Puro</a>
                </nav>

                <div class="nav-pills-custom">
                    <a href="{{ route('ranking.index', ['type' => 'bairro', 'category' => $category]) }}" class="nav-link {{ $locationType == 'bairro' ? 'active' : '' }}">Bairros</a>
                    <a href="{{ route('ranking.index', ['type' => 'cidade', 'category' => $category]) }}" class="nav-link {{ $locationType == 'cidade' ? 'active' : '' }}">Cidades</a>
                </div>
            </div>

            @if($results->count() >= 3)
            <!-- Podium -->
            <div class="podium-row d-none d-lg-flex">
                @foreach($results->take(3) as $index => $item)
                    <div class="podium-item podium-{{ $index + 1 }}">
                        <span class="medal-icon">
                            {!! $index == 0 ? '🥇' : ($index == 1 ? '🥈' : '🥉') !!}
                        </span>
                        <div class="podium-card">
                            <h5 class="fw-black mb-1 text-truncate">{{ $locationType == 'bairro' ? $item->bairro : $item->cidade }}</h5>
                            <p class="text-muted small mb-3 uppercase fw-bold">{{ $item->cidade }} / {{ $item->uf }}</p>
                            <div class="score-badge mx-auto mb-3" style="background: var(--primary); width: 60px; height: 60px; font-size: 24px;">
                                {{ $item->final_score }}
                            </div>
                            <div class="small fw-bold text-muted">SCORE REAL</div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            <!-- List -->
            <div class="table-responsive">
                <table class="ranking-table">
                    <tbody>
                        @forelse($results as $index => $item)
                        <tr>
                            <td><span class="rank-num">#{{ $index + 1 }}</span></td>
                            <td>
                                <div class="fw-black text-dark h5 mb-0">{{ $locationType == 'bairro' ? $item->bairro : $item->cidade }}</div>
                                <div class="small text-muted fw-bold">{{ $item->cidade }} / {{ $item->uf }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="d-flex gap-4">
                                    <div class="text-center">
                                        <div class="small fw-bold text-muted uppercase" style="font-size: 9px;">Segurança</div>
                                        <div class="fw-bold">{{ round($item->score_safety) }}%</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="small fw-bold text-muted uppercase" style="font-size: 9px;">Mobilidade</div>
                                        <div class="fw-bold">{{ round($item->score_walk) }}%</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="small fw-bold text-muted uppercase" style="font-size: 9px;">Saneamento</div>
                                        <div class="fw-bold">{{ round($item->avg_sanitation) }}%</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-3">
                                    <div class="score-badge" style="background: {{ $item->final_score >= 80 ? 'var(--success)' : ($item->final_score >= 60 ? 'var(--primary)' : 'var(--secondary)') }}">
                                        {{ $item->final_score }}
                                    </div>
                                    <!-- Ações -->
                                    @php
                                        // Tenta achar um CEP representativo desse bairro/cidade
                                        $repCep = \App\Models\LocationReport::where('cidade', $item->cidade)
                                            ->when($locationType == 'bairro', fn($q) => $q->where('bairro', $item->bairro))
                                            ->first()->cep;
                                    @endphp
                                    <a href="{{ route('report.show', $repCep) }}" class="btn-view d-none d-sm-inline-block">VER RAIO-X</a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="fa-solid fa-magnifying-glass-chart mb-3 opacity-25" style="font-size: 4rem;"></i>
                                <h5 class="fw-bold">Nenhum dado processado ainda.</h5>
                                <p class="text-muted">Realize uma busca por CEP na página inicial para começar o ranking.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Meta Footer -->
        <div class="text-center mb-5">
            <p class="small text-muted">
                <i class="fa-solid fa-circle-check text-success me-2"></i>
                Auditoria de dados baseada em <strong>{{ \App\Models\LocationReport::count() }}</strong> análises territoriais.
            </p>
        </div>
    </div>

    <footer class="bg-dark text-white-50 py-5">
        <div class="container text-center">
            <h3 class="text-white h5 fw-black text-uppercase">{{ config('app.name') }}</h3>
            <p class="small">Data Intelligence for Urban Planning & Real Estate</p>
        </div>
    </footer>

</body>
</html>
