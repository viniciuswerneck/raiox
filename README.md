# Raio-X de Vizinhança

> Relatórios inteligentes de qualquer bairro brasileiro a partir de um CEP.

**Stack:** Laravel 11 · PHP 8.x · MySQL · Bootstrap 5 · Leaflet.js · Google Gemini AI

---

## O que faz

Dado um CEP, o sistema:
1. Localiza o endereço completo (ViaCEP)
2. Busca dados demográficos do município (IBGE)
3. Geocodifica as coordenadas (Nominatim/OSM)
4. Mapeia POIs em raio de 10km (Overpass API)
5. Coleta clima e qualidade do ar (Open-Meteo)
6. Busca história do bairro ou cidade (Wikipedia PT)
7. Gera texto humanizado de 3-4 parágrafos (Google Gemini AI)
8. Exibe tudo em dashboard interativo com mapa (Leaflet)

---

## Pré-requisitos

- PHP 8.2+
- MySQL 8+
- Chave de API do [Google AI Studio](https://aistudio.google.com)

---

## Instalação

```bash
git clone https://github.com/viniciuswerneck/raiox.git
cd raiox
composer install
cp .env.example .env
php artisan key:generate
```

Configure o `.env`:
```env
DB_DATABASE=raiox
DB_USERNAME=root
DB_PASSWORD=sua_senha

GEMINI_API_KEY=sua_chave_gemini
```

```bash
php artisan migrate
php artisan serve
```

Acesse: `http://127.0.0.1:8000`

---

## Documentação Técnica

| Arquivo | Conteúdo |
|---------|----------|
| `CODEBASE.md` | Mapa de dependências entre arquivos, schema do banco, regras de cache |
| `.agent/ARCHITECTURE.md` | Arquitetura completa, fluxo de dados, APIs externas, lógica de negócio, TODOs |
| `specs.md` | Histórico de sessões de desenvolvimento, decisões de produto |
| `.agent/workflows/` | Workflows para deploy, debug, preview e outros |

---

## Cache

Relatórios são cacheados por **90 dias** no banco. Clima/AQI são atualizados a cada hora.

Para forçar regeneração de um CEP:
```sql
DELETE FROM location_reports WHERE cep = '01310200';
```

---

## APIs Externas (todas gratuitas)

| Serviço | Uso |
|---------|-----|
| ViaCEP | Resolução do CEP |
| IBGE Localidades | Dados demográficos |
| Nominatim (OSM) | Geocodificação |
| Overpass API | Pontos de interesse |
| Open-Meteo | Clima e qualidade do ar |
| Wikipedia PT | Texto histórico |
| Google Gemini Flash | Geração de texto com IA |
