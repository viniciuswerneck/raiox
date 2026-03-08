<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Rankings Territoriais Premium</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #10b981;
            --dark-bg: #020617;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --card-radius: 28px;
            --font-main: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            color: #f1f5f9;
            font-family: var(--font-main);
            overflow-x: hidden;
        }

        h1, h2, h3, h4 { font-family: var(--font-heading); font-weight: 900; }

        /* Background Animated */
        .bg-mesh {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 10% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 40%);
            z-index: -1;
        }

        .hero-section {
            padding: 100px 0 80px;
            text-align: center;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--card-radius);
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* Filter Pills */
        .filter-container {
            display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; flex-wrap: wrap;
        }

        .filter-pill {
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .filter-pill.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        /* Podium */
        .podium {
            display: flex; align-items: flex-end; justify-content: center; gap: 20px; margin-bottom: 60px;
        }

        .podium-item {
            flex: 1; max-width: 280px; text-align: center; position: relative;
        }

        .podium-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 30px 20px;
            transition: transform 0.3s;
        }

        .podium-item:hover .podium-card { transform: translateY(-10px); background: rgba(255, 255, 255, 0.05); }

        .rank-1 { order: 2; }
        .rank-2 { order: 1; }
        .rank-3 { order: 3; }

        .rank-1 .podium-card { border-top: 4px solid #fbbf24; height: 320px; display: flex; flex-direction: column; justify-content: center; }
        .rank-2 .podium-card { height: 260px; }
        .rank-3 .podium-card { height: 220px; }

        .medal { font-size: 2.5rem; margin-bottom: 15px; display: block; }
        .final-score { font-size: 2.5rem; font-weight: 900; color: white; display: block; margin-top: 10px; }

        /* List Styling */
        .rank-row {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .rank-row:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: var(--primary);
            transform: scale(1.01);
        }

        .pos-badge { font-weight: 900; font-size: 1.2rem; color: var(--primary); opacity: 0.5; min-width: 40px; }
        .info-col { flex: 1; }
        .info-col h3 { font-size: 1.1rem; margin: 0; color: white; }
        .info-col p { font-size: 0.8rem; margin: 0; color: #64748b; font-weight: 700; }

        .score-circle {
            width: 48px; height: 48px; border-radius: 12px; 
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; color: white;
            background: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        @media (max-width: 768px) {
            .podium { flex-direction: column; align-items: center; }
            .podium-item { max-width: 100%; width: 100%; }
            .rank-1, .rank-2, .rank-3 { order: unset; }
            .rank-1 .podium-card, .rank-2 .podium-card, .rank-3 .podium-card { height: auto; }
            .rank-row { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <div class="bg-mesh"></div>

    <nav class="container mx-auto px-6 py-8 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-white/50 text-decoration-none small fw-bold px-4 py-2 rounded-full border border-white/10 hover:bg-white/5 transition-all">
            <i class="fa-solid fa-arrow-left me-2"></i> VOLTAR
        </a>
        <div class="text-xl font-black italic uppercase tracking-tighter">{{ config('app.name') }}<span class="text-primary">.</span>TERRITORY</div>
    </nav>

    <header class="hero-section container">
        <h1 class="display-3 mb-3">Ranking <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">Nacional</span></h1>
        <p class="lead opacity-50 px-4">Auditórias realizadas pela Terrytory Engine baseadas em infraestrutura e segurança real.</p>
    </header>

    <div class="container pb-5">
        
        <div class="filter-container">
            <a href="{{ route('ranking.index', ['category' => 'all', 'type' => $locationType]) }}" class="filter-pill {{ $category == 'all' ? 'active' : '' }}">Geral</a>
            <a href="{{ route('ranking.index', ['category' => 'safety', 'type' => $locationType]) }}" class="filter-pill {{ $category == 'safety' ? 'active' : '' }}">Segurança</a>
            <a href="{{ route('ranking.index', ['category' => 'walk', 'type' => $locationType]) }}" class="filter-pill {{ $category == 'walk' ? 'active' : '' }}">Caminhabilidade</a>
            <a href="{{ route('ranking.index', ['category' => 'air', 'type' => $locationType]) }}" class="filter-pill {{ $category == 'air' ? 'active' : '' }}">Qualidade do Ar</a>
        </div>

        <div class="filter-container mb-5">
            <a href="{{ route('ranking.index', ['type' => 'bairro', 'category' => $category]) }}" class="filter-pill {{ $locationType == 'bairro' ? 'active' : '' }}">Por Bairro</a>
            <a href="{{ route('ranking.index', ['type' => 'cidade', 'category' => $category]) }}" class="filter-pill {{ $locationType == 'cidade' ? 'active' : '' }}">Por Cidade</a>
        </div>

        @if($results->onFirstPage())
        <div class="podium">
            @foreach($results->take(3) as $index => $item)
                <div class="podium-item rank-{{ $index + 1 }}">
                    <div class="podium-card">
                        <span class="medal">{!! $index == 0 ? '🥇' : ($index == 1 ? '🥈' : '🥉') !!}</span>
                        <p class="small fw-bold opacity-50 mb-1">{{ $item->cidade }} / {{ $item->uf }}</p>
                        <h2 class="h5 text-truncate">{{ $locationType == 'bairro' ? $item->bairro : $item->cidade }}</h2>
                        <span class="final-score">{{ $item->final_score }}</span>
                        <p class="text-[10px] font-black uppercase tracking-widest text-primary mt-2">Engine Score</p>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <div class="glass-panel">
            <div class="rank-list">
                @forelse($results as $index => $item)
                    @php $realPos = (($results->currentPage() - 1) * $results->perPage()) + $index + 1; @endphp
                    <div class="rank-row">
                        <div class="pos-badge">#{{ str_pad($realPos, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="info-col">
                            <h3>{{ $locationType == 'bairro' ? $item->bairro : $item->cidade }}</h3>
                            <p>{{ $item->cidade }} / {{ $item->uf }}</p>
                        </div>
                        <div class="score-circle">{{ $item->final_score }}</div>
                        
                        @php
                            $repCep = \App\Models\LocationReport::where('cidade', $item->cidade)
                                ->when($locationType == 'bairro', fn($q) => $q->where('bairro', $item->bairro))
                                ->value('cep');
                        @endphp
                        <a href="{{ route('report.show', $repCep) }}" class="btn btn-outline-light rounded-pill px-4 btn-sm fw-black uppercase tracking-widest">
                            Analisar
                        </a>
                    </div>
                @empty
                    <div class="text-center py-5 opacity-50">Nenhum dado auditado nesta categoria.</div>
                @endforelse
            </div>

            <div class="d-flex justify-content-center mt-5">
                {{ $results->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <footer class="py-5 text-center text-white/30 small font-black uppercase tracking-widest">
        <p>© Terrytory Engine | Auditando a Verdade de cada CEP</p>
    </footer>

</body>
</html>
