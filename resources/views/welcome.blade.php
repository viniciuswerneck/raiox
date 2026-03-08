<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Inteligência Territorial</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Analise bairros, infraestrutura e segurança com a maior rede de dados geográficos do Brasil. Relatórios instantâneos gerados por IA para compra, venda ou aluguel de imóveis.">
    <meta name="keywords" content="raio-x, vizinhança, inteligência territorial, segurança, ibge, wikipedia, imóveis, campinas, são paulo, análise de bairros">
    <meta name="author" content="Antigravity Territory Engine">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ config('app.name') }} - Inteligência Territorial em Tempo Real">
    <meta property="og:description" content="Descubra a verdade sobre qualquer vizinhança. Dados reais de infraestrutura, segurança e demografia.">
    <meta property="og:image" content="{{ asset('hero_background_city_1772568797393.png') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ config('app.name') }} - Inteligência Territorial em Tempo Real">
    <meta property="twitter:description" content="Descubra a verdade sobre qualquer vizinhança. Dados reais de infraestrutura, segurança e demografia.">
    <meta property="twitter:image" content="{{ asset('hero_background_city_1772568797393.png') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --accent: #10b981;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #020617;
            color: #f8fafc;
            overflow-x: hidden;
        }

        h1, h2, h3, .font-display {
            font-family: 'Outfit', sans-serif;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .hero-bg {
            background-image: linear-gradient(to bottom, rgba(2, 6, 23, 0.3), rgba(2, 6, 23, 1)), url('/hero_background_city_1772568797393.png');
            background-size: cover;
            background-position: center;
        }

        .floating {
            animation: floating 6s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .ai-pulse {
            animation: ai-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes ai-pulse {
            0%, 100% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 var(--primary-glow); }
            50% { transform: scale(1.05); opacity: 0.9; box-shadow: 0 0 40px 10px var(--primary-glow); }
        }

        .orbit {
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .text-glow {
            text-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
        }

        .input-glow:focus {
            box-shadow: 0 0 25px 0 rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .tech-badge-v2 {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(4px);
            transition: all 0.3s ease;
        }

        .tech-badge-v2.active {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            color: #818cf8;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.1);
        }

        /* Simetria em cards */
        .feature-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="min-h-screen">
    
    <!-- Hero Background Layer -->
    <div class="fixed inset-0 hero-bg z-[-1] opacity-60"></div>

    <div class="relative min-h-screen flex flex-col">
        
        <!-- Header / Nav -->
        <nav class="container mx-auto px-6 py-8 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
                    <div class="relative w-12 h-12 bg-slate-900 rounded-xl overflow-hidden shadow-2xl border border-white/10">
                        <img src="{{ asset('favicon.png') }}" alt="Raio-X Logo" class="w-full h-full object-cover">
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tighter text-white uppercase italic leading-none">{{ config('app.name') }}<span class="text-indigo-500">.</span>territory</span>
                </div>
            </div>
            <div class="hidden md:flex items-center space-x-8 text-sm font-bold uppercase tracking-widest text-slate-400">
                <a href="{{ route('ranking.index') }}" class="text-white bg-indigo-600/20 px-4 py-2 rounded-lg border border-indigo-500/20 hover:bg-indigo-600/40 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-ranking-star text-indigo-400"></i> Explorar Rankings
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow container mx-auto px-6 flex flex-col justify-center items-center text-center">
            
            <div class="max-w-4xl space-y-10 py-12">
                
                <div class="space-y-4">
                    <h1 class="text-5xl md:text-7xl font-black text-white leading-[1.1] tracking-tight text-glow">
                        Inteligência Territorial <br>
                        <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">em Tempo Real.</span>
                    </h1>
                    <p class="text-slate-400 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed">
                        Analise bairros, infraestrutura e segurança com a maior rede de dados geográficos do Brasil. Relatórios instantâneos gerados por IA.
                    </p>
                </div>

                <!-- Search Panel -->
                <div class="relative glass-panel p-2 md:p-3 rounded-[32px] max-w-2xl mx-auto transform hover:scale-[1.01] transition-all duration-500 z-50">
                    <form id="search-form" action="{{ route('search') }}" method="POST" class="relative flex flex-col md:flex-row gap-2">
                        @csrf
                        <div class="relative flex-grow">
                            <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                             <input 
                                 type="text" 
                                 id="cep-input"
                                 name="cep" 
                                 placeholder="00000-000"
                                 aria-label="Digite o CEP ou endereço para análise"
                                 autocomplete="off"
                                 maxlength="9"
                                 required
                                 class="block w-full pl-16 pr-6 py-6 text-xl text-white bg-transparent border-none rounded-2xl focus:ring-0 outline-none placeholder:text-slate-600 font-semibold input-glow tracking-[0.2em]"
                             >
                        </div>
                        <button 
                            type="submit"
                            aria-label="Iniciar análise territorial"
                            class="md:w-auto px-10 py-6 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-[24px] transition-all shadow-xl shadow-indigo-600/20 active:scale-95 text-lg uppercase tracking-wider"
                        >
                            Analisar
                        </button>

                        <!-- Suggestions Dropdown -->
                        <div id="suggestions-box" class="absolute top-full left-0 right-0 mt-4 hidden glass-panel rounded-3xl p-2 overflow-hidden shadow-2xl border border-white/10 max-h-64 overflow-y-auto z-[60]">
                            <div id="suggestions-list" class="flex flex-col"></div>
                        </div>
                    </form>
                    
                    <!-- Search Modes / Help -->
                    <div class="mt-4 flex justify-center">
                        <button 
                            type="button" 
                            onclick="showManualEntry('')"
                            aria-label="Buscar endereço manualmente"
                            class="group flex items-center space-x-2 text-slate-500 hover:text-indigo-400 transition-colors text-[11px] font-black uppercase tracking-widest px-4 py-2 rounded-full hover:bg-white/5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Não sei o CEP</span>
                        </button>
                    </div>
                </div>
                <div id="verification-card" class="hidden relative glass-panel p-6 md:p-10 rounded-[48px] max-w-2xl mx-auto shadow-2xl border border-white/20">
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center space-x-4">
                            <div class="p-4 bg-indigo-500/20 rounded-3xl">
                                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-white font-black uppercase tracking-[0.2em] text-sm">Localizador Inteligente</h3>
                                <p class="text-slate-500 text-[11px] font-bold uppercase tracking-wider mt-1 text-indigo-400/60">Verifique e ajuste se necessário</p>
                            </div>
                        </div>
                        <button onclick="cancelVerification()" class="p-3 hover:bg-white/10 rounded-full text-slate-500 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                        <div class="space-y-3">
                            <label class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] block ml-1">Logradouro / Rua</label>
                            <input type="text" id="v-road-input" class="w-full bg-white/5 border-none rounded-2xl px-6 py-4 text-white font-bold text-lg focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-slate-700">
                        </div>
                        <div class="space-y-3">
                            <label class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] block ml-1">Bairro</label>
                            <input type="text" id="v-neighborhood-input" class="w-full bg-white/5 border-none rounded-2xl px-6 py-4 text-white font-bold text-lg focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-slate-700">
                        </div>
                        <div class="space-y-3">
                            <label class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] block ml-1">Cidade</label>
                            <input type="text" id="v-city-input" placeholder="Ex: Jarinu" class="w-full bg-white/5 border-none rounded-2xl px-6 py-4 text-white font-bold text-lg focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-slate-700">
                        </div>
                        <div class="space-y-3">
                            <label class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] block ml-1">Estado (UF)</label>
                            <select id="v-state-input" class="w-full bg-slate-900 border-none rounded-2xl px-6 py-4 text-white font-bold text-lg focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all appearance-none cursor-pointer">
                                <option value="" disabled selected>UF</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                        <div class="space-y-3 relative" id="v-cep-container">
                            <label class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] block ml-1">Referência (CEP)</label>
                            <div class="flex items-center gap-3">
                                <div class="flex-[1.5] min-w-[160px] bg-white/5 border border-white/10 rounded-2xl px-4 py-4 flex items-center group focus-within:ring-2 focus-within:ring-indigo-500/50 transition-all">
                                    <input type="text" id="v-cep-display" placeholder="00000-000" class="bg-transparent border-none text-white font-bold text-lg tracking-widest outline-none w-full p-0 placeholder:text-slate-600">
                                </div>
                                <button type="button" id="v-find-cep-btn" class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl px-6 py-4 font-bold transition-all active:scale-95 flex items-center gap-2 group whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Buscar CEP
                                </button>
                            </div>
                            <!-- Menu de CEPs (Modal-Style) -->
                            <div id="v-cep-suggestions" class="hidden absolute left-0 right-0 top-full mt-4 bg-slate-900/98 backdrop-blur-3xl border-2 border-indigo-500/30 rounded-[32px] p-4 z-[999] shadow-[0_0_100px_rgba(79,70,229,0.3)] min-h-[100px] animate-in fade-in zoom-in duration-300">
                                <div class="flex items-center justify-between mb-4 px-2">
                                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Selecione o CEP Correto:</span>
                                    <button onclick="document.getElementById('v-cep-suggestions').classList.add('hidden')" class="p-2 hover:bg-white/10 rounded-full text-slate-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" /></svg>
                                    </button>
                                </div>
                                <div id="v-cep-suggestions-list" class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar p-1"></div>
                            </div>
                        </div>
                    </div>

                    <button 
                        id="v-confirm-btn"
                        class="w-full py-6 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-black rounded-[28px] transition-all shadow-2xl shadow-indigo-600/40 active:scale-[0.97] text-xl uppercase tracking-[0.1em] flex items-center justify-center space-x-3 group"
                    >
                        <span>Confirmar e Analisar</span>
                        <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                    
                    <p class="text-center mt-6 text-slate-600 text-[10px] font-bold uppercase tracking-widest">A análise levará aproximadamente 60 segundos</p>
                </div>

                @error('cep')
                    <div class="flex items-center justify-center space-x-2 text-red-400 font-bold animate-bounce">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                </div>

                <!-- Trending / Rankings Quick View -->
                <div class="pt-8">
                    <div class="flex items-center justify-center space-x-3 mb-6">
                        <div class="h-[1px] w-12 bg-indigo-500/20"></div>
                        <span class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] whitespace-nowrap">Tendências do Território</span>
                        <div class="h-[1px] w-12 bg-indigo-500/20"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @php
                            $trending = \App\Models\LocationReport::select('cidade', 'bairro', 'uf', 'cep', 'walkability_score', 'air_quality_index')
                                ->orderBy('created_at', 'desc')
                                ->limit(3)
                                ->get();
                        @endphp

                        @foreach($trending as $item)
                        <a href="{{ route('report.show', $item->cep) }}" class="feature-card p-4 rounded-3xl text-left block group">
                            <div class="flex justify-between items-start mb-3">
                                <div class="bg-indigo-500/20 p-2 rounded-xl text-indigo-400 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <span class="bg-indigo-500/10 text-indigo-400 text-[9px] font-black px-2 py-1 rounded-md">#{{ $loop->iteration }} NO RANKING</span>
                            </div>
                            <h4 class="text-white font-bold text-sm uppercase mb-1 group-hover:text-indigo-300 transition-colors">{{ $item->bairro ?: $item->cidade }}</h4>
                            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">{{ $item->cidade }} / {{ $item->uf }}</p>
                            
                            <div class="mt-4 flex items-center gap-3">
                                <div class="flex items-center gap-1 text-[9px] font-black text-slate-400 uppercase">
                                    <i class="fa-solid fa-person-walking text-primary"></i> {{ $item->walkability_score }}
                                </div>
                                <div class="flex items-center gap-1 text-[9px] font-black text-slate-400 uppercase">
                                    <i class="fa-solid fa-wind text-accent"></i> {{ $item->air_quality_index }} AQI
                                </div>
                            </div>
                        </a>
                        @endforeach
                        
                        @if($trending->isEmpty())
                            <div class="col-span-3 py-10 bg-white/5 rounded-3xl border border-dashed border-white/10 text-center">
                                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Aguardando as primeiras análises territoriais...</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="mt-8">
                        <a href="{{ route('ranking.index') }}" class="text-indigo-400 hover:text-indigo-300 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 group transition-all">
                            Ver ranking completo de cidades <i class="fa-solid fa-chevron-right group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <!-- Footer Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-4 pt-12">
                    <div class="feature-card p-4 rounded-2xl text-center">
                        <div class="text-2xl mb-2">📊</div>
                        <h4 class="text-white font-bold text-sm uppercase mb-1">IBGE Data</h4>
                        <p class="text-slate-500 text-xs">Demografia Real</p>
                    </div>
                    <div class="feature-card p-4 rounded-2xl text-center">
                        <div class="text-2xl mb-2">🛡️</div>
                        <h4 class="text-white font-bold text-sm uppercase mb-1">Safety AI</h4>
                        <p class="text-slate-500 text-xs">Segurança Pública</p>
                    </div>
                    <div class="feature-card p-4 rounded-2xl text-center">
                        <div class="text-2xl mb-2">📖</div>
                        <h4 class="text-white font-bold text-sm uppercase mb-1">Wikipedia</h4>
                        <p class="text-slate-500 text-xs">História Local</p>
                    </div>
                    <div class="feature-card p-4 rounded-2xl text-center">
                        <div class="text-2xl mb-2">🚶</div>
                        <h4 class="text-white font-bold text-sm uppercase mb-1">WalkScore</h4>
                        <p class="text-slate-500 text-xs">Mobilidade</p>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer Bar -->
        <footer class="container mx-auto px-6 py-10 flex flex-col md:flex-row justify-between items-center text-slate-500 text-xs font-bold uppercase tracking-widest gap-6">
            <div class="flex items-center space-x-6">
                <span class="flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> Sistema Online</span>
                <span class="text-indigo-400">V1.4 Territory Engine™</span>
            </div>
            <div class="flex items-center space-x-8">
                <span>© {{ date('Y') }} Raio-X Territory</span>
                <span class="text-indigo-500">Powered by Werneck</span>
            </div>
        </footer>
    </div>

    <!-- LOADER OVERLAY (Glassmorphism Core) -->
    <div id="loader" class="fixed inset-0 bg-slate-950/98 backdrop-blur-3xl z-[100] hidden flex flex-col items-center justify-center text-white" style="display:none;">
        
        <div class="relative flex items-center justify-center w-64 h-64 mb-12">
            <!-- Orbs orbitando -->
            <div class="absolute inset-0 orbit opacity-20">
                <div class="absolute top-0 left-1/2 w-4 h-4 bg-indigo-500 rounded-full blur-sm"></div>
                <div class="absolute bottom-0 left-1/2 w-4 h-4 bg-purple-500 rounded-full blur-sm"></div>
            </div>
            
            <!-- Anel de DNA/Data -->
            <div class="absolute inset-4 rounded-full border-2 border-dashed border-indigo-500/30 scale-100 animate-[spin_10s_linear_infinite]"></div>
            
            <!-- Núcleo de IA -->
            <div class="relative w-32 h-32 bg-indigo-600 rounded-full flex items-center justify-center ai-pulse shadow-2xl shadow-indigo-500/50">
                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.338 2.798H4.136c-1.368 0-2.338-1.798-1.338-2.798L4 15.298" />
                </svg>
            </div>
        </div>

        <div class="text-center space-y-6 max-w-lg px-6">
            <div class="space-y-2">
                <h2 class="text-4xl font-black text-white tracking-tight">Sincronizando <br> <span class="text-indigo-400">Dados Territoriais</span></h2>
                <div id="loader-text" class="text-slate-400 font-bold uppercase tracking-[0.3em] text-[10px] min-h-[20px]">Acessando satélites...</div>
            </div>

            <!-- Progress Bar -->
            <div class="w-64 h-1 bg-white/5 rounded-full overflow-hidden mx-auto">
                <div id="progress-bar" class="h-full bg-indigo-500 transition-all duration-500 ease-out" style="width: 10%"></div>
            </div>

            <div class="flex flex-wrap justify-center gap-3 pt-4" id="tech-badges">
                <span class="tech-badge-v2 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-white/30" data-source="ibge">IBGE</span>
                <span class="tech-badge-v2 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-white/30" data-source="wikipedia">Wiki</span>
                <span class="tech-badge-v2 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-white/30" data-source="osm">OSM</span>
                <span class="tech-badge-v2 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-white/30" data-source="gemini">Gemini AI</span>
            </div>
        </div>
    </div>

    <script>
        const loaderSteps = [
            { text: "Conectando ao gateway do IBGE...", badge: "ibge", progress: 25 },
            { text: "Minerando artigos na Wikipedia...", badge: "wikipedia", progress: 45 },
            { text: "Triangulando coordenadas OSM...", badge: "osm", progress: 65 },
            { text: "Gerando Insights com Gemini 2.5...", badge: "gemini", progress: 85 },
            { text: "Finalizando Territory Engine Neural Protocol...", badge: null, progress: 95 },
        ];

        const searchForm = document.getElementById('search-form');
        const cepInput = document.getElementById('cep-input');
        const sugBox = document.getElementById('suggestions-box');
        const sugList = document.getElementById('suggestions-list');
        const loader = document.getElementById('loader');

        searchForm.onsubmit = function(e) {
            e.preventDefault();
            const val = cepInput.value.trim();
            if (!val) return;

            // Se for um submit final (após confirmação no card)
            if (window.isConfirmedSearch) {
                loader.style.display = 'flex';
                startLoaderAnimation();
                // Redireciona para a URL do relatório (que aceita CEP ou Endereço codificado)
                window.location.href = `/cep/${encodeURIComponent(val.replace(/-/g, ''))}`;
                return;
            }

            // Se for um CEP completo digitado rápido (00000-000 ou 8 dígitos)
            const numericCep = val.replace(/\D/g, '');
            if (numericCep.length === 8 && /^\d+$/.test(numericCep)) {
                window.isConfirmedSearch = true;
                searchForm.dispatchEvent(new Event('submit'));
                return;
            }

            // Caso contrário (é uma rua ou busca parcial), abre o modo manual para confirmar detalhes
            // Primeiro tentamos um "best match" via API
            fetch(`/suggestions?q=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        showVerificationCard(data[0]);
                    } else {
                        showManualEntry(val);
                    }
                });
        };

        function showManualEntry(typedValue) {
            const item = {
                cep: typedValue,
                details: {
                    road: typedValue || '',
                    neighborhood: '',
                    city: '',
                    state: '',
                    formatted_cep: 'Sob consulta'
                }
            };
            showVerificationCard(item);
            
            setTimeout(() => {
                document.getElementById('v-road-input').focus();
            }, 600);
        }

        // Flag global para permitir a submissão final
        window.isConfirmedSearch = false;

        cepInput.addEventListener('input', function(e) {
            let v = e.target.value;
            
            // Se o usuário está digitando algo que parece um CEP (inicia com números)
            if (/^\d/.test(v)) {
                let numeric = v.replace(/\D/g, '');
                if (numeric.length > 5) {
                    numeric = numeric.substring(0, 5) + '-' + numeric.substring(5, 8);
                }
                e.target.value = numeric;

                if (numeric.length === 9) {
                    sugBox.classList.add('hidden');
                }
            }
        });

        const stateToUFMap = {
            'Acre': 'AC', 'Alagoas': 'AL', 'Amapá': 'AP', 'Amazonas': 'AM', 'Bahia': 'BA', 'Ceará': 'CE',
            'Distrito Federal': 'DF', 'Espírito Santo': 'ES', 'Goiás': 'GO', 'Maranhão': 'MA', 'Mato Grosso': 'MT',
            'Mato Grosso do Sul': 'MS', 'Minas Gerais': 'MG', 'Pará': 'PA', 'Paraíba': 'PB', 'Paraná': 'PR',
            'Pernambuco': 'PE', 'Piauí': 'PI', 'Rio de Janeiro': 'RJ', 'Rio Grande do Norte': 'RN',
            'Rio Grande do Sul': 'RS', 'Rondônia': 'RO', 'Roraima': 'RR', 'Santa Catarina': 'SC',
            'São Paulo': 'SP', 'Sergipe': 'SE', 'Tocantins': 'TO'
        };

        // Máscara para o CEP editável no card
        document.getElementById('v-cep-display').addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5, 8);
            e.target.value = v;
        });

        // Helper para normalizar strings (remover acentos)
        function normalizeStr(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }

        // Lógica de busca de CEP pelo endereço digitado
        async function findCepByAddress() {
            const road = document.getElementById('v-road-input').value;
            const city = document.getElementById('v-city-input').value;
            const state = document.getElementById('v-state-input').value;
            const display = document.getElementById('v-cep-display');
            const btn = document.getElementById('v-find-cep-btn');
            const sugMenu = document.getElementById('v-cep-suggestions');
            const sugList = document.getElementById('v-cep-suggestions-list');

            if (!road || !city || !state) {
                display.value = "DADOS INCOMPLETOS";
                return;
            }

            display.value = "BUSCANDO...";
            btn.disabled = true;
            sugMenu.classList.add('hidden');
            sugList.innerHTML = '';

            try {
                // NÃO normalizar (remover acentos) aqui, pois ViaCEP exige acentuação correta na URL
                // encodeURIComponent já cuida de enviar os caracteres especiais com segurança
                const q = `${road}, ${city} - ${state}`;
                console.log("Consultando CEP para:", q);
                
                const res = await fetch(`/suggestions?q=${encodeURIComponent(q)}`);
                const data = await res.json();

                if (Array.isArray(data) && data.length > 0) {
                    display.value = "VERIFICAR LISTA ↓";
                    console.log("CEPs encontrados:", data);
                    
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = "flex items-center justify-between p-4 bg-white/5 hover:bg-indigo-500/20 rounded-2xl cursor-pointer transition-all border border-white/5 hover:border-indigo-500/40 group mb-2";
                        const fCep = item.details.formatted_cep || item.cep;
                        const complement = item.details.complement ? `<span class="text-indigo-300/60 lowercase italic ml-1"> - ${item.details.complement}</span>` : "";
                        
                        div.innerHTML = `
                            <div class="flex flex-col">
                                <div class="flex items-center">
                                    <span class="text-white font-black text-lg tracking-wider group-hover:text-indigo-200">${fCep}</span>
                                    ${complement}
                                </div>
                                <span class="text-indigo-400/70 text-[10px] uppercase font-black tracking-widest mt-1">${item.details.neighborhood || 'Bairro local'}</span>
                            </div>
                            <div class="bg-indigo-500/10 p-2 rounded-xl group-hover:bg-indigo-500/40 transition-colors text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        `;
                        div.onclick = () => {
                            display.value = fCep;
                            sugMenu.classList.add('hidden');
                            console.log("CEP Selecionado:", fCep);
                        };
                        sugList.appendChild(div);
                    });
                    
                    sugMenu.classList.remove('hidden');
                } else {
                    display.value = "NÃO LOCALIZADO";
                    sugList.innerHTML = '<div class="p-8 text-center text-slate-500 font-bold uppercase text-xs tracking-widest">Nenhum CEP encontrado para esta rua.<br>Verifique os nomes e tente novamente.</div>';
                    sugMenu.classList.remove('hidden');
                }
            } catch (e) {
                console.error("Erro na busca de CEP:", e);
                display.value = "ERRO NA API";
            } finally {
                btn.disabled = false;
            }
        }

        document.getElementById('v-find-cep-btn').onclick = findCepByAddress;

        function showVerificationCard(item) {
            window.currentVerificationItem = item; 
            const vCard = document.getElementById('verification-card');
            const searchPanel = document.querySelector('.max-w-2xl.mx-auto');
            
            // Populate card inputs
            document.getElementById('v-road-input').value = item.details.road;
            document.getElementById('v-neighborhood-input').value = item.details.neighborhood;
            document.getElementById('v-city-input').value = item.details.city;
            
            const stateInput = document.getElementById('v-state-input');
            const uf = stateToUFMap[item.details.state] || item.details.state || '';
            stateInput.value = uf;

            document.getElementById('v-cep-display').value = item.details.formatted_cep || '';
            
            document.getElementById('v-confirm-btn').onclick = () => {
                const road = document.getElementById('v-road-input').value;
                const city = document.getElementById('v-city-input').value;
                const state = stateInput.value;
                
                // Pegamos o que o usuário editou no campo de CEP
                const displayedCep = document.getElementById('v-cep-display').value.trim();
                const hasValidNumericCep = /^\d{5}-\d{3}$/.test(displayedCep);

                if (hasValidNumericCep) {
                    cepInput.value = displayedCep.replace(/\D/g, ''); 
                } else {
                    cepInput.value = `${road}, ${city} - ${state}`;
                }
                    
                window.isConfirmedSearch = true; 
                searchForm.dispatchEvent(new Event('submit'));
            };

            // Transition UI
            if (!sugBox.classList.contains('hidden')) sugBox.classList.add('hidden');
            searchPanel.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
            setTimeout(() => {
                searchPanel.classList.add('hidden');
                vCard.classList.remove('hidden');
            }, 300);
        }

        function cancelVerification() {
            const vCard = document.getElementById('verification-card');
            const searchPanel = document.querySelector('.max-w-2xl.mx-auto');
            const vCepSug = document.getElementById('v-cep-suggestions');
            
            vCard.classList.add('hidden');
            searchPanel.classList.remove('hidden', 'opacity-0', 'pointer-events-none', 'scale-95');
            if (vCepSug) vCepSug.classList.add('hidden');
            cepInput.focus();
        }

        // Fechar sugestões ao clicar fora
        document.addEventListener('click', (e) => {
            const vCepCont = document.getElementById('v-cep-container');
            const vCepSug = document.getElementById('v-cep-suggestions');
            
            // Sugestões do input principal
            if (!searchForm.contains(e.target)) {
                sugBox.classList.add('hidden');
            }

            // Sugestões de CEP do card
            if (vCepCont && vCepSug && !vCepCont.contains(e.target)) {
                vCepSug.classList.add('hidden');
            }
        });

        function startLoaderAnimation() {
            const textEl = document.getElementById('loader-text');
            const barEl = document.getElementById('progress-bar');
            const badges = document.querySelectorAll('.tech-badge-v2');
            let step = 0;

            function nextStep() {
                if (step >= loaderSteps.length) return;
                const s = loaderSteps[step];

                textEl.style.opacity = '0';
                setTimeout(() => {
                    textEl.innerText = s.text;
                    textEl.style.opacity = '1';
                }, 200);

                barEl.style.width = s.progress + '%';

                badges.forEach(b => b.classList.remove('active'));
                if (s.badge) {
                    const target = document.querySelector(`[data-source="${s.badge}"]`);
                    if (target) target.classList.add('active');
                }

                step++;
            }

            nextStep();
            setInterval(nextStep, 2500);
        }
    </script>
</body>
</html>
