<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Raio-X de Vizinhança</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
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
    <div id="loader" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center text-white">
        <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-6"></div>
        <h2 class="text-2xl font-bold mb-2">Estamos analisando as informações solicitadas</h2>
        <p class="text-slate-400 animate-pulse">Cruzando dados de IBGE, Wikipedia e Satélite...</p>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('loader').classList.remove('hidden');
        });
    </script>
</body>
</html>
