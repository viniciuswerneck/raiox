<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Raio-X de Vizinhança</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes orbitRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes orbitRotateReverse {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }
        @keyframes textFade {
            0%, 100% { opacity: 0; transform: translateY(5px); }
            15%, 85% { opacity: 1; transform: translateY(0); }
        }
        .ai-core-glow {
            box-shadow: 0 0 30px 8px rgba(59, 130, 246, 0.35), 0 0 80px 20px rgba(59, 130, 246, 0.12);
        }
        .orbit-ring {
            border: 1.5px dashed rgba(99, 179, 237, 0.4);
            border-radius: 50%;
        }
        .orbit-dot::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: #60a5fa;
            border-radius: 50%;
            top: -4px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 0 8px #60a5fa;
        }
        #loader-text { transition: opacity 0.4s ease, transform 0.4s ease; }
        .tech-badge {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 9999px;
            padding: 4px 14px;
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
        }
        .tech-badge.active {
            background: rgba(59,130,246,0.12);
            border-color: rgba(96,165,250,0.4);
            color: rgba(147,197,253,0.9);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-6">
    <div class="max-w-2xl w-full text-center space-y-8">
        <h1 class="text-4xl font-bold text-slate-900 leading-tight">
            Descubra o potencial da sua próxima <span class="text-blue-600">vizinhança</span>
        </h1>
        <p class="text-slate-600 text-lg">
            Gere relatórios demográficos e estruturais instantâneos a partir de apenas um CEP. Gratuito e rápido.
        </p>

        <form action="{{ route('search') }}" method="POST" class="mt-8">
            @csrf
            <div class="relative group">
                <input 
                    type="text" 
                    name="cep" 
                    placeholder="Ex: 01310-200"
                    maxlength="9"
                    required
                    class="block w-full px-6 py-5 text-xl text-slate-900 bg-white border border-slate-200 rounded-2xl shadow-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all outline-none"
                    oninput="this.value = this.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2')"
                >
                <button 
                    type="submit"
                    class="absolute right-3 top-3 bottom-3 px-8 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200"
                >
                    Analisar
                </button>
            </div>
            @error('cep')
                <p class="mt-4 text-red-500 text-sm font-medium">{{ $message }}</p>
            @enderror
        </form>

        <div class="pt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm text-slate-500">
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mb-2">📍</div>
                ViaCEP Integration
            </div>
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mb-2">📊</div>
                Dados do IBGE
            </div>
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mb-2">⚡</div>
                Cache Inteligente
            </div>
        </div>
    </div>
    <div id="loader" class="fixed inset-0 bg-slate-950/97 backdrop-blur-2xl z-50 hidden flex flex-col items-center justify-center text-white p-8" style="display:none;">

        {{-- Núcleo de IA animado --}}
        <div class="relative flex items-center justify-center mb-10" style="width:160px;height:160px;">
            {{-- Anel externo orbitando --}}
            <div class="orbit-ring orbit-dot absolute" style="width:150px;height:150px;animation:orbitRotate 6s linear infinite;position:relative;"></div>
            {{-- Anel médio orbitando reverso --}}
            <div class="orbit-ring orbit-dot absolute" style="width:110px;height:110px;animation:orbitRotateReverse 4s linear infinite;"></div>
            {{-- Núcleo central --}}
            <div class="absolute flex items-center justify-center w-20 h-20 bg-blue-600 rounded-full ai-core-glow animate-pulse">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.338 2.798H4.136c-1.368 0-2.338-1.798-1.338-2.798L4 15.298" />
                </svg>
            </div>
        </div>

        {{-- Textos --}}
        <div class="text-center max-w-sm">
            <h2 class="text-3xl font-extrabold mb-3 tracking-tight">Análise <span class="text-blue-400">em Progresso</span></h2>
            <p class="text-slate-300 text-base mb-1 font-medium">Estamos processando as informações solicitadas</p>
            <p id="loader-text" class="text-blue-300 text-sm font-semibold mb-8 min-h-[1.5rem]">Inicializando motores de IA...</p>

            {{-- Barra de progresso indeterminada --}}
            <div class="w-72 h-1 bg-slate-800 rounded-full overflow-hidden mb-8 mx-auto">
                <div class="h-full bg-gradient-to-r from-blue-600 via-blue-400 to-blue-600 rounded-full" style="width:40%;animation:progressSlide 2s ease-in-out infinite;"></div>
            </div>

            {{-- Badges de tecnologia --}}
            <div class="flex flex-wrap items-center justify-center gap-2" id="tech-badges">
                <span class="tech-badge" data-source="ibge">📊 IBGE</span>
                <span class="tech-badge" data-source="wikipedia">📖 Wikipedia</span>
                <span class="tech-badge" data-source="satellite">🛰️ Satélite</span>
                <span class="tech-badge" data-source="osm">🗺️ OpenStreetMap</span>
                <span class="tech-badge" data-source="gemini">✦ Google Gemini AI</span>
            </div>
        </div>
    </div>

    <style>
        @keyframes progressSlide {
            0% { margin-left: -40%; }
            100% { margin-left: 100%; }
        }
    </style>

    <script>
        const loaderSteps = [
            { text: "Consultando base geográfica do IBGE...", badge: "ibge" },
            { text: "Buscando dados históricos na Wikipedia...", badge: "wikipedia" },
            { text: "Processando imagens de satélite...", badge: "satellite" },
            { text: "Mapeando pontos de interesse via OpenStreetMap...", badge: "osm" },
            { text: "Google Gemini AI analisando padrões regionais...", badge: "gemini" },
            { text: "Cruzando dados multidimensionais...", badge: null },
            { text: "Gerando relatório de inteligência territorial...", badge: null },
        ];

        document.querySelector('form').addEventListener('submit', function() {
            const loader = document.getElementById('loader');
            loader.style.display = 'flex';

            const textEl = document.getElementById('loader-text');
            const badges = document.querySelectorAll('.tech-badge');
            let step = 0;

            function nextStep() {
                if (step >= loaderSteps.length) step = 0;
                const s = loaderSteps[step];

                // Atualiza texto com fade
                textEl.style.opacity = '0';
                textEl.style.transform = 'translateY(5px)';
                setTimeout(() => {
                    textEl.innerText = s.text;
                    textEl.style.opacity = '1';
                    textEl.style.transform = 'translateY(0)';
                }, 300);

                // Ativa badge correspondente
                badges.forEach(b => b.classList.remove('active'));
                if (s.badge) {
                    const target = document.querySelector(`[data-source="${s.badge}"]`);
                    if (target) target.classList.add('active');
                }

                step++;
            }

            nextStep();
            setInterval(nextStep, 2800);
        });
    </script>
</body>
</html>
