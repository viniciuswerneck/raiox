<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Inteligência Territorial</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

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
            <div class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <span class="text-xl font-black tracking-tighter text-white uppercase italic">{{ config('app.name') }}<span class="text-indigo-500">.</span>territory</span>
            </div>
            <div class="hidden md:flex items-center space-x-8 text-sm font-bold uppercase tracking-widest text-slate-400">
                <a href="#" class="hover:text-white transition-colors">Tecnologia</a>
                <a href="#" class="hover:text-white transition-colors">Dados</a>
                <a href="https://github.com/viniciuswerneck/raiox" target="_blank" class="hover:text-white transition-colors">GitHub</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow container mx-auto px-6 flex flex-col justify-center items-center text-center">
            
            <div class="max-w-4xl space-y-10 py-12">
                
                <div class="space-y-4">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-black uppercase tracking-[0.2em] mb-4">
                        Powered by Gemini 2.5 AI
                    </span>
                    <h1 class="text-5xl md:text-7xl font-black text-white leading-[1.1] tracking-tight text-glow">
                        Inteligência Territorial <br>
                        <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">em Tempo Real.</span>
                    </h1>
                    <p class="text-slate-400 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed">
                        Analise bairros, infraestrutura e segurança com a maior rede de dados geográficos do Brasil. Relatórios instantâneos gerados por IA.
                    </p>
                </div>

                <!-- Search Panel -->
                <div class="glass-panel p-2 md:p-3 rounded-[32px] max-w-2xl mx-auto transform hover:scale-[1.01] transition-all duration-500">
                    <form action="{{ route('search') }}" method="POST" class="relative flex flex-col md:flex-row gap-2">
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
                                name="cep" 
                                placeholder="Digite o CEP para iniciar o Raio-X..."
                                maxlength="9"
                                required
                                class="block w-full pl-16 pr-6 py-6 text-xl text-white bg-transparent border-none rounded-2xl focus:ring-0 outline-none placeholder:text-slate-600 font-semibold input-glow"
                                oninput="this.value = this.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2')"
                            >
                        </div>
                        <button 
                            type="submit"
                            class="md:w-auto px-10 py-6 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-[24px] transition-all shadow-xl shadow-indigo-600/20 active:scale-95 text-lg uppercase tracking-wider"
                        >
                            Analisar
                        </button>
                    </form>
                </div>

                @error('cep')
                    <div class="flex items-center justify-center space-x-2 text-red-400 font-bold animate-bounce">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <!-- Symmetry Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-4">
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
                <span>V1.4 Panthera</span>
            </div>
            <div class="flex items-center space-x-8">
                <span>© {{ date('Y') }} Raio-X Territory</span>
                <span class="text-indigo-500">Desenvolvido com Paixão</span>
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
            { text: "Finalizando Relatório Premium...", badge: null, progress: 95 },
        ];

        document.querySelector('form').addEventListener('submit', function() {
            const loader = document.getElementById('loader');
            loader.style.display = 'flex';

            const textEl = document.getElementById('loader-text');
            const barEl = document.getElementById('progress-bar');
            const badges = document.querySelectorAll('.tech-badge-v2');
            let step = 0;

            function nextStep() {
                if (step >= loaderSteps.length) return;
                const s = loaderSteps[step];

                // Update text
                textEl.style.opacity = '0';
                setTimeout(() => {
                    textEl.innerText = s.text;
                    textEl.style.opacity = '1';
                }, 200);

                // Update Progress
                barEl.style.width = s.progress + '%';

                // Activate Badge
                badges.forEach(b => b.classList.remove('active'));
                if (s.badge) {
                    const target = document.querySelector(`[data-source="${s.badge}"]`);
                    if (target) target.classList.add('active');
                }

                step++;
            }

            nextStep();
            setInterval(nextStep, 2500);
        });
    </script>
</body>
</html>
