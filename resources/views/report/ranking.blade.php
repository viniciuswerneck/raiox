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
            --glass: rgba(255, 255, 255, 0.9);
            --card-radius: 28px;
            --font-main: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: var(--font-main);
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Fix horizontal scroll */
        }

        h1, h2, h3, h4, .font-heading {
            font-family: var(--font-heading);
            font-weight: 800;
        }

        /* Hero */
        .hero-explore {
            background: var(--dark);
            padding: 100px 0 160px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-explore::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 40px 40px;
        }

        .hero-content { position: relative; z-index: 2; }

        .explore-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: var(--card-radius);
            margin-top: -100px;
            padding: 40px;
            box-shadow: 0 30px 60px -15px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.5);
            margin-bottom: 60px;
        }

        /* Filter Pills */
        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            background: #e2e8f0;
            padding: 8px;
            border-radius: 20px;
            width: fit-content;
            margin: 0 auto 30px;
        }

        .filter-link {
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--secondary);
            transition: all 0.3s;
            background: transparent;
        }

        .filter-link.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        /* Podium */
        .podium-container {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 15px;
            margin-bottom: 60px;
            padding: 20px;
        }

        .podium-slot {
            flex: 1;
            max-width: 300px;
            text-align: center;
            transition: all 0.3s;
        }

        .podium-slot:hover { transform: translateY(-10px); }

        .podium-box {
            background: white;
            border-radius: 24px;
            padding: 30px 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border-bottom: 8px solid #cbd5e1;
        }

        .slot-1 { order: 2; }
        .slot-1 .podium-box { border-bottom-color: var(--accent); padding-bottom: 60px; }
        .slot-2 { order: 1; }
        .slot-2 .podium-box { border-bottom-color: #94a3b8; }
        .slot-3 { order: 3; }
        .slot-3 .podium-box { border-bottom-color: #b45309; }

        .crown-icon { font-size: 2.5rem; margin-bottom: 10px; display: block; }

        /* Table Ranking */
        .rank-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .rank-item {
            background: white;
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid #f1f5f9;
            transition: all 0.3s;
        }

        .rank-item:hover {
            transform: translateX(10px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .rank-position {
            font-weight: 900;
            font-size: 1.5rem;
            color: var(--secondary);
            opacity: 0.2;
            min-width: 50px;
        }

        .rank-info { flex: 1; }
        .rank-info h3 { margin: 0; font-size: 1.2rem; color: var(--dark); }
        .rank-info p { margin: 0; font-size: 0.85rem; color: var(--secondary); font-weight: 600; }

        .rank-score {
            width: 55px;
            height: 55px;
            background: var(--primary);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .rank-metrics {
            display: flex;
            gap: 25px;
        }

        .metric-mini { text-align: center; }
        .metric-mini span { display: block; font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; }
        .metric-mini strong { font-size: 1rem; color: var(--dark); }

        /* Pagination custom */
        .pagination-container {
            margin-top: 40px;
            display: flex;
            justify-content: center;
        }

        .pagination-container .pagination {
            gap: 10px;
        }

        .pagination-container .page-item .page-link {
            border: none;
            background: white;
            color: var(--secondary);
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }

        .pagination-container .page-item.active .page-link {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
        }

        @media (max-width: 991px) {
            .rank-item { flex-direction: column; text-align: center; padding: 30px; }
            .rank-metrics { margin: 15px 0; }
            .podium-container { flex-direction: column; align-items: center; }
            .podium-slot { max-width: 100%; width: 100%; }
            .slot-1, .slot-2, .slot-3 { order: unset; }
        }
    </style>
</head>
<body>

    <section class="hero-explore">
        <div class="hero-content container">
            <a href="{{ route('home') }}" class="text-white opacity-50 text-decoration-none small fw-bold mb-4 d-inline-block">
                <i class="fa-solid fa-arrow-left me-2"></i>VOLTAR PARA A BUSCA
            </a>
            <h1 class="display-3 mb-2">Explorar <span class="text-primary">Ranking</span></h1>
            <p class="lead opacity-75">Inteligência Territorial Baseada em Dados Reais</p>
        </div>
    </section>

    <div class="container">
        <div class="explore-card">
            
            <!-- Filter Main -->
            <div class="filter-group">
                <a href="{{ route('ranking.index', ['category' => 'all', 'type' => $locationType]) }}" class="filter-link {{ $category == 'all' ? 'active' : '' }}">Geral</a>
                <a href="{{ route('ranking.index', ['category' => 'safety', 'type' => $locationType]) }}" class="filter-link {{ $category == 'safety' ? 'active' : '' }}">Segurança</a>
                <a href="{{ route('ranking.index', ['category' => 'walk', 'type' => $locationType]) }}" class="filter-link {{ $category == 'walk' ? 'active' : '' }}">Caminhabilidade</a>
                <a href="{{ route('ranking.index', ['category' => 'air', 'type' => $locationType]) }}" class="filter-link {{ $category == 'air' ? 'active' : '' }}">Ar Puro</a>
            </div>

            <!-- Filter Type -->
            <div class="filter-group mb-5" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                <a href="{{ route('ranking.index', ['type' => 'bairro', 'category' => $category]) }}" class="filter-link {{ $locationType == 'bairro' ? 'active' : '' }}">Ver Bairros</a>
                <a href="{{ route('ranking.index', ['type' => 'cidade', 'category' => $category]) }}" class="filter-link {{ $locationType == 'cidade' ? 'active' : '' }}">Ver Cidades</a>
            </div>

            @if($results->onFirstPage())
            <!-- TOP 3 PODIUM -->
            <div class="podium-container">
                @foreach($results->take(3) as $index => $item)
                    <div class="podium-slot slot-{{ $index + 1 }}">
                        <span class="crown-icon">
                            {!! $index == 0 ? '🥇' : ($index == 1 ? '🥈' : '🥉') !!}
                        </span>
                        <div class="podium-box">
                            <p class="text-muted small fw-black mb-1 text-uppercase">{{ $item->cidade }} / {{ $item->uf }}</p>
                            <h3 class="mb-4 text-truncate">{{ $locationType == 'bairro' ? $item->bairro : $item->cidade }}</h3>
                            <div class="rank-score mx-auto" style="width: 70px; height: 70px; font-size: 1.5rem; {{ $index > 0 ? 'background: #94a3b8;' : '' }}">
                                {{ $item->final_score }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            <!-- RANKING LIST -->
            <div class="rank-list">
                @forelse($results as $index => $item)
                    @php
                        // Cálculo da posição real considerando a paginação
                        $realPosition = (($results->currentPage() - 1) * $results->perPage()) + $index + 1;
                    @endphp
                    <div class="rank-item">
                        <div class="rank-position">#{{ str_pad($realPosition, 2, '0', STR_PAD_LEFT) }}</div>
                        
                        <div class="rank-info">
                            <h3>{{ $locationType == 'bairro' ? $item->bairro : $item->cidade }}</h3>
                            <p>{{ $item->cidade }} / {{ $item->uf }}</p>
                        </div>

                        <div class="rank-metrics d-none d-lg-flex">
                            <div class="metric-mini">
                                <span>Segurança</span>
                                <strong>{{ round($item->score_safety) }}%</strong>
                            </div>
                            <div class="metric-mini">
                                <span>Mobilidade</span>
                                <strong>{{ round($item->score_walk) }}%</strong>
                            </div>
                            <div class="metric-mini">
                                <span>Saneamento</span>
                                <strong>{{ round($item->avg_sanitation) }}%</strong>
                            </div>
                        </div>

                        <div class="rank-score" style="background: {{ $item->final_score >= 80 ? 'var(--success)' : ($item->final_score >= 60 ? 'var(--primary)' : 'var(--secondary)') }}">
                            {{ $item->final_score }}
                        </div>

                        @php
                            $repCep = \App\Models\LocationReport::where('cidade', $item->cidade)
                                ->when($locationType == 'bairro', fn($q) => $q->where('bairro', $item->bairro))
                                ->first()->cep;
                        @endphp
                        <a href="{{ route('report.show', $repCep) }}" class="btn btn-dark rounded-pill px-4 fw-bold small">
                            VER RAIO-X
                        </a>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="fa-solid fa-ghost fa-4x opacity-10 mb-4"></i>
                        <h4 class="fw-bold opacity-50">Nenhum dado encontrado.</h4>
                        <p>Seja o primeiro a mapear esta região!</p>
                        <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-5 py-3 mt-3 fw-bold">REALIZAR BUSCA</a>
                    </div>
                @endforelse
            </div>

            <!-- PAGINATION -->
            <div class="pagination-container">
                {{ $results->links('pagination::bootstrap-5') }}
            </div>

        </div>

        <div class="text-center pb-5">
            <p class="small text-muted fw-bold">
                <i class="fa-solid fa-database me-2"></i>
                AUDITORIA TERRITORIAL: {{ \App\Models\LocationReport::count() }} REGISTROS PROCESSADOS
            </p>
        </div>
    </div>

    <footer class="bg-dark text-white-50 py-5">
        <div class="container text-center">
            <h5 class="text-white fw-black text-uppercase mb-3">{{ config('app.name') }}</h5>
            <p class="small mb-0">Territory Engine™ | Powered by Werneck &copy; {{ date('Y') }}</p>
        </div>
    </footer>

</body>
</html>
