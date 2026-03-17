<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melhores Bairros de {{ $cityModel->name }} - {{ $cityModel->uf }} | Rankings Territoriais</title>
    <meta name="description" content="Descubra quais são os melhores bairros para morar em {{ $cityModel->name }} / {{ $cityModel->uf }} com base em infraestrutura, segurança e IA.">
    <meta name="keywords" content="viver em {{ $cityModel->name }}, melhor bairro de {{ $cityModel->name }}, segurança {{ $cityModel->name }}, IDH {{ $cityModel->name }}, ranking {{ $cityModel->name }}">
    
    <!-- Open Graph -->
    <meta property="og:title" content="Ranking de Bairros: {{ $cityModel->name }} - {{ $cityModel->uf }}">
    <meta property="og:description" content="Confira o top 20 bairros de {{ $cityModel->name }} auditados pela Territory Engine.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ $cityModel->image_url ?: url('/hero_background_city_1772568797393.png') }}">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "ItemList",
      "name": "Melhores Bairros de {{ $cityModel->name }}",
      "description": "Lista dos bairros com melhor infraestrutura e segurança em {{ $cityModel->name }}.",
      "itemListElement": [
        @foreach($results as $index => $item)
        {
          "@@type": "ListItem",
          "position": {{ $index + 1 }},
          "name": "{{ $item->bairro }}",
          "url": "{{ route('report.show', $item->cep) }}"
        } @if(!$loop->last) , @endif
        @endforeach
      ]
    }
    </script>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --primary: #6366f1;
            --dark-bg: #020617;
            --font-heading: 'Outfit', sans-serif;
        }
        body { background: var(--dark-bg); color: #f1f5f9; font-family: 'Outfit', sans-serif; }
        .glass-panel { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 28px; }
        .rank-row { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s; }
        .rank-row:hover { background: rgba(255, 255, 255, 0.04); border-color: var(--primary); transform: scale(1.01); }
        .filter-pill { padding: 8px 16px; border-radius: 50px; background: rgba(255, 255, 255, 0.05); color: #94a3b8; font-weight: 800; transition: all 0.3s; font-size: 11px; }
        .filter-pill.active { background: var(--primary); color: white; }
    </style>
</head>
<body class="p-4 md:p-12">
    <nav class="container mx-auto flex justify-between items-center mb-12">
        <a href="{{ route('city.show', $cityModel->slug) }}" class="text-white/50 text-xs font-bold px-4 py-2 rounded-full border border-white/10 hover:bg-white/5 transition-all outline-none no-underline flex items-center">
            <i class="fa-solid fa-arrow-left mr-2"></i> VOLTAR PARA {{ mb_strtoupper($cityModel->name) }}
        </a>
    </nav>

    <header class="text-center mb-16">
        <span class="text-primary font-black tracking-widest text-[10px] uppercase mb-4 block">Engine Data Ranking</span>
        <h1 class="text-4xl md:text-6xl font-black text-white mb-4">Melhores Bairros de <br> <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">{{ $cityModel->name }}</span></h1>
        <p class="text-slate-400 max-w-xl mx-auto">Mapeamento dinâmico de segurança e infraestrutura para a região de {{ $cityModel->name }} / {{ $cityModel->uf }}.</p>
    </header>

    <div class="container mx-auto max-w-6xl">
        <div class="flex justify-center gap-3 mb-10">
            <a href="{{ route('ranking.city', ['slug' => $cityModel->slug, 'category' => 'all']) }}" class="filter-pill {{ $category == 'all' ? 'active' : '' }}">GERAL</a>
            <a href="{{ route('ranking.city', ['slug' => $cityModel->slug, 'category' => 'safety']) }}" class="filter-pill {{ $category == 'safety' ? 'active' : '' }}">SEGURANÇA</a>
            <a href="{{ route('ranking.city', ['slug' => $cityModel->slug, 'category' => 'walk']) }}" class="filter-pill {{ $category == 'walk' ? 'active' : '' }}">CAMINHABILIDADE</a>
        </div>

        <div class="glass-panel p-6 md:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @forelse($results as $index => $item)
                <div class="rank-row p-6 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="text-2xl font-black text-white/20">#{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $item->bairro }}</h3>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $item->cidade }} / {{ $item->uf }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="text-right hidden sm:block">
                            <span class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Score IA</span>
                            <span class="text-xl font-black text-indigo-400">{{ $item->final_score }}</span>
                        </div>
                        <a href="{{ route('report.show', $item->cep) }}" class="bg-primary hover:bg-indigo-500 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all text-center">
                            EXPLORAR
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-span-1 lg:col-span-2 text-center py-12 text-slate-500 font-bold uppercase tracking-widest">Aguardando auditoria de novos bairros nesta cidade...</div>
                @endforelse
            </div>
        </div>
    </div>

    <footer class="mt-20 text-center text-[10px] font-black text-slate-600 uppercase tracking-[0.3em]">
        © Territory Engine v3.0 | Powered by Lumina Intelligence
    </footer>
</body>
</html>
