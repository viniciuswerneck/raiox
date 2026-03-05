# ARCHITECTURE.md — Raio-X de Vizinhança

> Documento central de arquitetura. Leia antes de implementar qualquer feature.
> Updated: 2026-03-05 (Agent-Based Refactor)

---

## 🎯 Visão Geral do Produto

**Raio-X de Vizinhança** é uma plataforma de inteligência territorial que gera diagnósticos precisos de qualquer micro-região brasileira a partir de um CEP. O sistema utiliza uma arquitetura de múltiplos agentes especializados para coletar, cruzar e auditar dados geográficos, climáticos, socioeconômicos e históricos.

**Stack:** Laravel 11 · PHP 8.4 · MySQL · Bootstrap 5 · Leaflet.js · Gemini 2.5 Flash

---

## 🏗️ Arquitetura Baseada em Agentes (Agent-Based)

O sistema opera no modelo de **Micro-Agentes**, coordenados por um orquestrador central (`PipelineCoordinator`).

### Fluxo de Dados (Pipeline Orquestrado)

```
Usuário → [Dashboard/Show]
   │
   ├── 1. CacheAgent::getCachedReport() ───────── [HIT] → Exibe Dados Imediatos
   │                                             [MISS] → Dispara Orquestração
   │
   ├── 2. PipelineCoordinator::orchestrateFastPath() (Síncrono/Rápido)
   │      │
   │      ├── GeoAgent ───────── Resolve CEP e Coordenadas (ViaCEP + Nominatim)
   │      │
   │      ├── MASTER POOL (Http::pool paralelo)
   │      │     ├─ ClimaAgent ── Clima Atual + AQI (EU/US Fallback)
   │      │     └─ SocioAgent ── População, IDHM, Renda (IBGE c/ Retry Síncrono)
   │      │
   │      ├── POIAgent ───────── OSM Overpass BBox (Saúde, Lazer, Comércio)
   │      └── CacheAgent ─────── Salva Estrutura Inicial (Status: processing_text)
   │
   └── 3. LLMAgent::dispatchTextGeneration() (Background Job)
          │
          ├── Wikipedia ──────── Fetch Contexto Histórico denso
          ├── Gemini ─────────── Auditoria Narrativa (AAN) + Imobiliário
          └── Finalize ───────── Atualiza Status: completed → Trigger Frontend Polling
```

---

## 📁 Estrutura de Agentes Especializados (`app/Services/Agents/`)

| Agente | Responsabilidade | Tecnologia |
|--------|------------------|------------|
| **GeoAgent** | Identidade do CEP e Geocodificação | ViaCEP + Nominatim (OSM) |
| **POIAgent** | Mapeamento de Pontos de Interesse (1km) | Overpass API (BBox Optimized) |
| **SocioAgent**| Renda, IDHM, População e Saneamento | IBGE API (Pool + Retry Síncrono) |
| **ClimaAgent**| Clima real-time e poluição | Open-Meteo (EU/US index) |
| **LLMAgent** | Narrativa, Segurança e Mercado Imobiliário | Gemini 2.5 Flash (Multi-Key) |
| **CacheAgent**| Persistência e Proteção de Dados | MySQL / Eloquent |
| **PipelineCoordinator** | Orquestrador do Pool Assíncrono | Laravel HTTP Client |

---

## 🛡️ Mecanismos de Resiliência (Blindagem)

O sistema implementa camadas de redundância para garantir 100% de disponibilidade:

1. **Blindagem de Capitais**: Valores de segurança (População, IDHM, Renda) para todas as capitais brasileiras, usados se o IBGE falhar.
2. **Retry Síncrono IBGE**: Se o pool de requisições falhar (erro 504), o `SocioAgent` faz uma tentativa direta e isolada.
3. **Proteção de Cache**: O sistema nunca apaga dados "bons" (Mapa, indicadores) por falhas momentâneas de rede em repetições de busca.
4. **Failover Gemini**: Rotação automática entre 5 chaves de API caso o limite seja atingido.

---

## 🧠 Lógica de Inteligência Territorial (AACT)

Protocolo **AACT (Ambiente de Análise Condicionada Territorial)**:
- **NCC (Narrativa Condicionada por Categoria)**: A IA adapta o tom (Popular, Médio, Premium) conforme a categoria detectada.
- **AAN (Auditoria Narrativa)**: Validação se a descrição condiz com a Renda Média e POIs reais detectados via satélite.

---

## 🔐 Variáveis de Ambiente (.env)

```env
# Essenciais
DB_DATABASE=raiox
DB_USERNAME=root
DB_PASSWORD=

# Gemini Multi-Key (Suporta até 5)
GEMINI_API_KEY=key_1
GEMINI_API_KEY_0=key_2
GEMINI_API_KEY_2=key_3
```

---

## 🧪 Comandos Úteis

- **Limpar Cache**: `php artisan tinker --execute="App\Models\LocationReport::where('cep', '88010300')->delete();"`
- **Verificar Job**: `php artisan queue:work`
- **Monitorar Logs**: `tail -f storage/logs/laravel.log`
