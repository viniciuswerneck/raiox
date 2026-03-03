# ARCHITECTURE.md — Raio-X de Vizinhança

> Documento central de arquitetura. Leia antes de implementar qualquer feature.
> Updated: 2026-03-03

---

## 🎯 Visão Geral do Produto

**Raio-X de Vizinhança** é uma aplicação web Laravel que gera relatórios inteligentes de qualquer bairro ou cidade brasileira a partir de um simples CEP. O relatório cruza dados de múltiplas fontes externas e usa **Google Gemini AI** para gerar um texto humanizado sobre a história e características do local.

**Stack:** Laravel 11 · PHP 8.x · MySQL · Bootstrap 5 · Leaflet.js · Tailwind CSS (via CDN)

---

## 🏗️ Arquitetura de Serviços

```
Usuário → [welcome.blade.php]
   → POST /search (CEP)
   → ReportController@search
   → redirect GET /cep/{cep}
   → ReportController@show
   → NeighborhoodService::getCachedReport()
        ├─ [HIT cache DB location_reports] → retorna direto (90 dias)
        └─ [MISS] → NeighborhoodService::getFullReport()
              │
              ├── 1. ViaCEP API ──────────────── endereço + código IBGE
              ├── 2. City & Neighborhood DB ───── HIT: retorna cache normalizado
              │                                    MISS (Cria Localmente):
              ├── 3. IbgeService ─────────────── população + microrregião
              ├── 4. Nominatim (OSM) ─────────── lat/lng do endereço
              ├── 5. Overpass API (3 endpoints)─ POIs num raio de 10km
              ├── 6. Open-Meteo ──────────────── clima + qualidade do ar
              ├── 7. Wikipedia ───────────────── fetchWikipediaInfo()
              │       ├── /page/summary/{termo}  (valida se é lugar real)
              │       └── /page/mobile-sections/ (conteúdo completo ~4000 chars)
              └── 8. GeminiService ───────────── gera texto e nota de segurança (JSON)
                      └── prompt especializado com conteúdo completo da Wikipedia
   → salva em location_reports (DB)
   → view report/show.blade.php
```

---

## 📁 Estrutura de Arquivos Principais

```
app/
  Http/Controllers/
    ReportController.php      # Único controller. search() + show()
  Models/
    LocationReport.php        # Eloquent Model global. Casts para JSON.
    City.php                  # Model Normalizado. Cache de textos da cidade.
    Neighborhood.php          # Model Normalizado. Cache de textos do bairro.
    User.php                  # Padrão Laravel (não usado ativamente)
  Services/
    NeighborhoodService.php   # ⭐ CORE. Orquestra todas as APIs externas.
    GeminiService.php         # Wrapper Google Gemini Flash. Gera history_extract.
    IbgeService.php           # Busca dados demográficos da API IBGE.
    ReportService.php         # Helper auxiliar (pouco utilizado atualmente)
    ViaCepService.php         # Wrapper simples para ViaCEP API

resources/views/
  welcome.blade.php           # Homepage com campo de CEP + loader de IA animado
  report/
    show.blade.php            # Dashboard completo do relatório (Bootstrap 5)

routes/
  web.php                     # 3 rotas: home, search (POST), report.show (GET)

database/migrations/
  ...create_location_reports_table.php
  ...add_extra_data_to_location_reports_table.php
  ...add_socioeconomic_and_walkability_to_location_reports.php
  ...add_history_extract_to_location_reports.php
```

---

## 🔌 APIs Externas Integradas

| # | Serviço | URL Base | Dados Obtidos | Fallback |
|---|---------|----------|---------------|---------|
| 1 | **ViaCEP** | `viacep.com.br/ws/{cep}/json` | Endereço + código IBGE | Nenhum (obrigatório) |
| 2 | **IBGE API** | `servicodados.ibge.gov.br` | Município, microrregião, população | Array vazio |
| 3 | **Nominatim (OSM)** | `nominatim.openstreetmap.org/search` | lat/lng | Tenta só cidade se rua falhar |
| 4 | **Overpass API** | 3 endpoints (veja abaixo) | POIs: comércios, saúde, transporte, lazer | Próximo endpoint |
| 5 | **Open-Meteo** | `api.open-meteo.com/v1/forecast` | Temperatura, vento | null |
| 6 | **Open-Meteo AQI** | `air-quality-api.open-meteo.com` | Índice AQI europeu | null |
| 7 | **Wikipedia PT** | `pt.wikipedia.org/api/rest_v1` | Texto, imagem, URL da página | Próximo candidato |
| 8 | **Google Gemini** | `generativelanguage.googleapis.com` | JSON: texto, safety_level, desc | Usa extract bruto |

### Overpass API — Endpoints de Fallback (ordem)
1. `https://overpass-api.de/api/interpreter` (principal)
2. `https://overpass.kumi.systems/api/interpreter`
3. `https://maps.mail.ru/osm/tools/overpass/api/interpreter`

---

## 🧠 Lógica de Wikipedia (Busca com Fallback Inteligente)

O método `fetchWikipediaInfo()` tenta os seguintes termos **em ordem**, parando no primeiro artigo válido:

1. `{Bairro}+({Cidade})` → ex: `Vila_Madalena_(São_Paulo)`
2. `{Bairro}` simples → ex: `Vila_Madalena` *(pulado se bairro for ambíguo)*
3. `{Cidade}+({UF})` → ex: `São_Paulo_(SP)`
4. `{Cidade}` simples → ex: `São_Paulo`

**Bairros ambíguos** (que não são buscados sozinhos):
`centro, norte, sul, leste, oeste, central, jardim, vila, bela vista, alto, baixo`

**Validação de artigo** (`isValidWikipediaPlace()`):
- Rejeita artigos de desambiguação (`type === 'disambiguation'`)
- Aceita se `description` contém palavras-chave de lugar (município, bairro, cidade, etc.)
- Rejeita se `extract` começa com padrões de conceitos abstratos (geometria, matemática, física)
- Por padrão aceita se nenhuma regra se aplicar

**Após encontrar a página:**
- Busca conteúdo completo via `/page/mobile-sections/` (~4000 chars)
- Passa o conteúdo completo ao Gemini (não só o extract curto)

---

## 🤖 GeminiService — Prompt de Geração

```
Modelo: gemini-2.5-flash
Temperature: 0.70
MaxOutputTokens: 2048
responseMimeType: application/json
Timeout: 45s
```

**Prompt:** Analista de segurança pública imobiliário. Exige output estruturado JSON contendo `historia` (2 parágrafos envolventes), `nivel_seguranca` ("ALTO", "MODERADO" ou "BAIXO") e `descricao_seguranca` justificando a nota de segurança.

**Input:** até 15.000 chars do conteúdo completo da Wikipedia
**Output:** JSON decodificado nativamente para popular tabelas no PHP.

---

## 🗺️ Frontend — Dashboard (report/show.blade.php)

### Seções do relatório:
| Seção | Dados de |
|-------|----------|
| Hero com imagem de fundo | `wiki_json.image` |
| Índice de Caminhabilidade (A/B/C) | Calculado a partir de `pois_json` |
| Clima + Qualidade do Ar | `climate_json` + `air_quality_index` |
| Renda Média + Saneamento | `average_income` + `sanitation_rate` |
| Mapa interativo (Leaflet) | `lat`, `lng`, `pois_json` |
| Infraestrutura Crítica (farm./hosp./esc./banco) | Filtrado de `pois_json` |
| Mobilidade e Transporte | Filtrado de `pois_json` (bus_stop, bicycle_parking, fuel) |
| Comércios e Serviços | Filtrado de `pois_json` (shops + amenidades de alimentação) |
| Nível de Segurança | `safety_level` e `safety_description` (via Gemini) |
| História Local | `history_extract` com badge de fonte (Bairro/Município) + link Wikipedia |

### Loader animado (welcome.blade.php):
- Núcleo central pulsante com anéis orbitando
- 7 mensagens rotativas com fade suave (a cada 2.8s)
- Badges iluminados de tecnologia (IBGE, Wikipedia, Satélite, OSM, Gemini AI)
- Barra de progresso indeterminada

---

## 🔐 Variáveis de Ambiente Necessárias

```env
APP_KEY=...
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=raiox
DB_USERNAME=root
DB_PASSWORD=

GEMINI_API_KEY=...   # Google AI Studio → aistudio.google.com
```

### config/services.php
```php
'gemini' => [
    'key' => env('GEMINI_API_KEY'),
],
```

---

## ⚠️ TODOs / Débitos Técnicos

| Prioridade | Item | Arquivo Relacionado |
|-----------|------|---------------------|
| 🟡 Médio | `average_income` e `sanitation_rate` são **simulados com `rand()`** — conectar ao SIDRA/IBGE real | `NeighborhoodService::fetchSocioEconomic()` |
| 🟡 Médio | `idhm` sempre retorna `null` — mapear de fonte estática ou IBGE | `IbgeService::getMunicipalityData()` |
| 🟡 Médio | `ReportService.php` e `ViaCepService.php` são redundantes com a lógica em `NeighborhoodService` | — |
| 🟢 Baixo | Adicionar `database/seeders` para teste com CEPs populares | — |
| 🟢 Baixo | Cache do Overpass: se todos os 3 endpoints falharem, relatório fica com `pois_json = []` e não reprocessa automaticamente (apenas itens que satisfazem a condição de cache são rejeitados) | `NeighborhoodService::getCachedReport()` |
| 🟢 Baixo | Gemini timeout de 45s pode deixar a request lenta — considerar processamento em background (Queue/Job) | `GeminiService` |
| 🔴 Alto | `test_gemini.php` e `test_gemini_2.php` estão commitados no repositório — remover antes do deploy em produção | raiz do projeto |

---

## 🧪 Como Rodar Localmente

```bash
# Instalar dependências
composer install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Banco de dados
php artisan migrate

# Servidor de desenvolvimento
php artisan serve
```

Acesse: `http://127.0.0.1:8000`

---

## 🚀 Deploy

Ver `.agent/workflows/deploy.md` para o fluxo completo de deploy.

**Pré-requisitos de produção:**
- PHP 8.2+
- MySQL 8+
- `GEMINI_API_KEY` configurada
- `php artisan migrate` executado
- `php artisan config:cache` executado
- `set_time_limit(120)` já está configurado no controller para suportar o tempo de análise
