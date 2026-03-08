<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Últimos Duelos Territoriais</title>
    <meta name="description" content="Acompanhe os últimos duelos e comparações territoriais no Brasil. Inteligência Territorial e avaliações baseadas em infraestrutura e segurança com IA.">
    <meta name="keywords" content="duelo de bairros, melhores cidades, segurança pública, comparação territorial, inteligência territorial, melhores lugares para morar">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Social Media -->
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ config('app.name') }} - Duelos Territoriais do Brasil">
    <meta property="og:description" content="Acompanhe as comparações e descubra quais são os melhores bairros e cidades para se viver.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url('/hero_background_city_1772568797393.png') }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Ranking Nacional de Territórios | {{ config('app.name') }}">
    <meta name="twitter:description" content="Explore as melhores e mais seguras cidades e bairros do Brasil pela maior rede de inteligência do país.">
    <meta name="twitter:image" content="{{ url('/hero_background_city_1772568797393.png') }}">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "CollectionPage",
      "name": "Duelos Territoriais - {{ config('app.name') }}",
      "description": "Comparações diretas e duelos de infraestrutura e qualidade de vida entre regiões e bairros do Brasil.",
      "url": "{{ url()->current() }}"
    }
    </script>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #10b981;
            --dark-bg: #020617;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --card-radius: 28px;
            --font-main: 'Plus Jakarta Sans', sans-serif;
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
            padding: 80px 0 60px;
            text-align: center;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--card-radius);
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

        .filter-pill:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
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
        .rank-2 .podium-card { border-top: 4px solid #94a3b8; height: 260px; }
        .rank-3 .podium-card { border-top: 4px solid #b45309; height: 220px; }

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

        .pos-badge { font-weight: 900; font-size: 1.2rem; color: var(--primary); opacity: 0.5; min-width: 50px; }
        .info-col { flex: 1; }
        .info-col h3 { font-size: 1.2rem; margin: 0; color: white; }
        .info-col p { font-size: 0.85rem; margin: 0; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

        .score-circle {
            width: 48px; height: 48px; border-radius: 12px; 
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; color: white;
            background: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* Pagination Wrapper styling just in case, though the new view handles it. */
        .pagination-wrapper { margin-top: 3rem; }

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
        <a href="{{ route('home') }}" class="text-white/50 text-sm font-bold px-4 py-2 rounded-full border border-white/10 hover:bg-white/5 transition-all outline-none no-underline flex items-center">
            <i class="fa-solid fa-arrow-left mr-2"></i> VOLTAR
        </a>
        <div class="text-xl font-black italic uppercase tracking-tighter">{{ config('app.name') }}<span class="text-primary">.</span>TERRITORY</div>
    </nav>

    <header class="hero-section container mx-auto px-6">
        <h1 class="text-5xl md:text-6xl mb-4 font-black tracking-tight"><span class="bg-gradient-to-r from-purple-400 to-indigo-400 bg-clip-text text-transparent">Duelos</span> Territoriais</h1>
        <p class="text-slate-400 text-lg max-w-2xl mx-auto leading-relaxed">Comparativos estruturais auditados pela Engine entre as principais regiões do Brasil.</p>
    </header>

    <div class="container mx-auto px-6 pb-20">
        <div class="glass-panel p-6 md:p-10 max-w-5xl mx-auto">
            <div class="rank-list flex flex-col gap-3">
                @forelse($duels as $duel)
                    @php 
                        $dataA = $duel->comparison_data['location_a'] ?? 'Região A';
                        $dataB = $duel->comparison_data['location_b'] ?? 'Região B';
                        $scoreA = $duel->comparison_data['metrics_a']['total_score'] ?? 0;
                        $scoreB = $duel->comparison_data['metrics_b']['total_score'] ?? 0;
                        $winner = $scoreA >= $scoreB ? $dataA : $dataB;
                        $isOld = $duel->updated_at->diffInMonths(now()) >= 6;
                    @endphp
                    <div class="rank-row flex flex-col md:flex-row items-center w-full gap-4 md:gap-8">
                        <div class="flex-1 text-center md:text-right">
                            <h3 class="text-white text-lg md:text-xl font-black uppercase tracking-tight">
                                @if($scoreA > $scoreB) <i class="fa-solid fa-trophy text-warning me-1 text-sm"></i> @endif
                                {{ explode(',', $dataA)[0] }}
                            </h3>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">{{ round($scoreA) }} Score Territorial</p>
                        </div>
                        
                        <div class="flex-shrink-0 flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center font-black italic shadow-[0_0_15px_rgba(168,85,247,0.3)] border border-purple-500/30">VS</div>
                            @if($isOld)
                            <span class="text-[9px] font-black text-warning uppercase mt-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> Desatualizado</span>
                            @else
                            <span class="text-[9px] font-black text-slate-500 uppercase mt-2">{{ $duel->created_at->locale('pt_BR')->diffForHumans() }}</span>
                            @endif
                        </div>
                        
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-white text-lg md:text-xl font-black uppercase tracking-tight">
                                @if($scoreB > $scoreA) <i class="fa-solid fa-trophy text-warning me-1 text-sm"></i> @endif
                                {{ explode(',', $dataB)[0] }}
                            </h3>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">{{ round($scoreB) }} Score Territorial</p>
                        </div>
                        
                        <a href="{{ route('report.compare', ['cepA' => $duel->cep_a, 'cepB' => $duel->cep_b]) }}" class="border border-purple-500/30 text-purple-300 hover:bg-purple-500/20 hover:text-white rounded-full px-8 py-3 text-xs font-black uppercase tracking-widest transition-all no-underline shrink-0 mt-4 md:mt-0">
                            Ver Resultado
                        </a>
                    </div>
                @empty
                    <div class="text-center py-10 text-slate-500 font-bold uppercase tracking-widest">Nenhum duelo foi processado no banco de dados.</div>
                @endforelse
            </div>

            <div class="mt-12 w-full flex justify-center pagination-wrapper">
                {{ $duels->links('pagination::tailwind') }}
            </div>
        </div>
    </div>

    <footer class="py-5 text-center text-white/30 small font-black uppercase tracking-widest">
        <p>© Terrytory Engine | Auditando a Verdade de cada CEP</p>
    </footer>

</body>
</html>
