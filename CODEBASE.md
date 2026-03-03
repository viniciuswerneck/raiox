# CODEBASE.md — Raio-X de Vizinhança

> Mapa de dependências e referência rápida para agentes de IA.
> Leia este arquivo ANTES de modificar qualquer arquivo do projeto.

---

## File Dependency Map

| Arquivo Modificado | Impacta Diretamente |
|--------------------|---------------------|
| `app/Services/NeighborhoodService.php` | `app/Http/Controllers/ReportController.php` (injetado via DI) |
| `app/Services/GeminiService.php` | `app/Services/NeighborhoodService.php` (chamado em `getFullReport` para extrair JSON) |
| `app/Services/IbgeService.php` | `app/Services/NeighborhoodService.php` (chamado em `getFullReport`) |
| `app/Models/LocationReport.php` | Salva o histórico total por CEP pesquisado |
| `app/Models/City.php` | Banco de cache e dados estáticos das Cidades |
| `app/Models/Neighborhood.php` | Banco de cache e história local dos Bairros |
| `resources/views/report/show.blade.php` | `app/Http/Controllers/ReportController.php` (renderizado em `show()`) |
| `resources/views/welcome.blade.php` | `routes/web.php` (rota `home`) |
| `database/migrations/` | `app/Models/LocationReport.php` (schema do banco) |
| `config/services.php` | `app/Services/GeminiService.php` (lê `services.gemini.key`) |
| `.env` | `config/services.php`, `config/database.php` |

---

## Rotas (routes/web.php)

| Método | URI | Nome | Controller@Método |
|--------|-----|------|-------------------|
| GET | `/` | `home` | Welcome view (inline) |
| POST | `/search` | `search` | `ReportController@search` |
| GET | `/cep/{cep}` | `report.show` | `ReportController@show` |

---

## Banco de Dados

### Tabela: `location_reports`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | bigint | PK |
| `cep` | string | CEP sem formatação (8 dígitos) |
| `logradouro` | string | Nome da rua |
| `bairro` | string | Nome do bairro |
| `cidade` | string | Nome do município |
| `uf` | string | Estado (2 letras) |
| `codigo_ibge` | string | Código IBGE do município |
| `populacao` | integer | População estimada |
| `idhm` | float | IDHM (atualmente null — ver TODO) |
| `raw_ibge_data` | json | Resposta bruta da API IBGE |
| `lat` | float | Latitude (via Nominatim) |
| `lng` | float | Longitude (via Nominatim) |
| `pois_json` | json | Array de POIs do Overpass API |
| `climate_json` | json | Dados de clima do Open-Meteo |
| `wiki_json` | json | Dados da Wikipedia (source, term, extract, image, desktop_url, full_text) |
| `air_quality_index` | integer | Índice AQI europeu (Open-Meteo) |
| `walkability_score` | string | 'A', 'B' ou 'C' (calculado) |
| `average_income` | decimal | Renda média (herdado da Cidade) |
| `sanitation_rate` | decimal | Taxa de saneamento (herdado da Cidade) |
| `history_extract` | text | Texto gerado pelo Gemini AI (herdado da Cidade ou Bairro) |
| `safety_level` | string | "ALTO", "MODERADO" ou "BAIXO" gerado pelo Gemini AI |
| `safety_description` | string | Frase curta do Gemini AI justificando o nível de segurança |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | Usado para controle de cache de 90 dias |

### Tabela: `cities` e `neighborhoods`
O sistema conta com Tabelas de Normalização (`cities` e `neighborhoods`) que gravam em cache de banco os dados da Wikipedia (wiki_json), a geração histórica (history_extract) e os alertas de segurança, reduzindo requisições na API do Gemini/Wikipedia para buscas repetidas de cidades ou bairros vizinhos.

### Cache de 90 dias (NeighborhoodService::getCachedReport)

O relatório é reutilizado se:
- `updated_at` < 90 dias atrás
- `air_quality_index` não é null
- `history_extract` não é null
- `pois_json` não está vazio

Exceção: **Clima e AQI** são atualizados silenciosamente (sem alterar `updated_at`) a cada 1 hora.
