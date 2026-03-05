# Raio-X de Vizinhança 🗺️🛡️

> Inteligência Territorial em Tempo Real: Relatórios precisos de qualquer micro-região brasileira via CEP.

**Stack:** [Laravel 11](https://laravel.com) · PHP 8.4 · MySQL · Bootstrap 5 · Leaflet.js · Google Gemini AI

---

## 🏗️ Como Funciona (Arquitetura Agent-Based)

Ao digitar um CEP, o sistema orquestra uma frota de **Micro-Agentes** especializados:

1.  **GeoAgent**: Resolve endereço e coordenadas exatas (ViaCEP + OSM).
2.  **POIAgent**: Mapeia comércios, lazer e infraestrutura em 1km (Overpass API).
3.  **SocioAgent**: Coleta Renda, IDHM e População (IBGE c/ Retry Síncrono).
4.  **ClimaAgent**: Dados meteorológicos e qualidade do ar em tempo real (Open-Meteo).
5.  **LLMAgent**: Gera narrativa humanizada e auditoria de segurança (Gemini AI).

---

## ⚡ Diferenciais de Resiliência

- **Blindagem Socioeconômica**: Garantia de dados básicos para capitais mesmo se o IBGE falhar.
- **Failover Multi-Key**: Rotação inteligente de chaves da API Gemini para 100% de uptime.
- **Pipeline Assíncrono**: O dashboard abre instantaneamente; a análise profunda de IA carrega em background.
- **Cache Inteligente**: Proteção de dados históricos e geográficos por 90 dias.

---

## 🛠️ Instalação Local

```bash
# 1. Clonar e Instalar
git clone https://github.com/viniciuswerneck/raiox.git
cd raiox
composer install

# 2. Configurar Ambiente
cp .env.example .env
php artisan key:generate

# 3. Banco de Dados
php artisan migrate

# 4. Iniciar Servidores (Em terminais separados)
php artisan queue:work  # Para processar a IA em background
php artisan serve       # Para o dashboard web
```

**Configuração do `.env`:**
```env
DB_DATABASE=raiox
DB_USERNAME=root
DB_PASSWORD=

GEMINI_API_KEY=sua_chave_aqui
```

---

## 🔌 Ecossistema de APIs

| Serviço | Função |
|---|---|
| **ViaCEP** | Resolução de Endereço |
| **IBGE** | Indicadores Socioeconômicos |
| **OSM (Nominatim)** | Geocodificação de Coordenadas |
| **OpenStreetMap (Overpass)** | Pontos de Interesse (POIs) |
| **Open-Meteo** | Clima e Qualidade do Ar |
| **Wikipedia** | Contexto Histórico e Imagens |
| **Google Gemini Flash** | Auditoria Territorial e Narrativa |

---

## 📄 Documentação Técnica Completa
Consulte o arquivo [ARCHITECTURE.md](.agent/ARCHITECTURE.md) para detalhes sobre o protocolo AACT, fluxo de dados assíncrono e estrutura de agentes.
