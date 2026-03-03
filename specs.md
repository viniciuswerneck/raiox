# Projeto: Raio-X de Vizinhança (Neighborhood X-Ray)

## 1. Visão Geral
O **Raio-X de Vizinhança** é um Micro-SaaS B2C projetado para fornecer relatórios rápidos e detalhados sobre uma região específica do Brasil, utilizando apenas o CEP. O foco é auxiliar pessoas que desejam mudar de residência ou empreendedores que buscam entender o perfil demográfico, socioeconômico e a infraestrutura local.

## 2. Tecnologias Utilizadas
- **Framework:** Laravel 12.x
- **Linguagem:** PHP 8.4+
- **Banco de Dados:** MySQL 8.0+
- **Frontend:** Blade, TailwindCSS 4.0, Leaflet.js (Mapas)
- **APIs de Terceiros:**
    - **ViaCEP:** Dados de endereço e código IBGE.
    - **Nominatim (OpenStreetMap):** Geocodificação (Lat/Lng).
    - **Overpass API (OpenStreetMap):** Pontos de Interesse (POIs) e Mobilidade.
    - **Open-Meteo:** Dados meteorológicos e Qualidade do Ar (AQI).
    - **Wikipedia REST API:** Resumo histórico e imagens da cidade.
    - **IBGE SIDRA/Serviço Dados:** População e estrutura regional.

## 3. Arquitetura e Padrões de Projeto
O sistema utiliza o **Service Pattern** para isolar a inteligência de dados.

### Componentes Principais:
- **`NeighborhoodService`**: Orquestrador central que consome múltiplas APIs e gerencia o cache.
- **`IbgeService`**: Especialista em dados demográficos do IBGE.
- **`ReportController`**: Controla as rotas de busca e exibição do relatório.

## 4. Regras de Negócio e Inteligência
1. **Cache de 30 Dias**:
   - As consultas são salvas na tabela `location_reports`. 
   - Se um CEP for consultado novamente em menos de 30 dias, os dados são lidos do banco local para evitar rate limiting e excesso de chamadas externas.

2. **Índice de Caminhabilidade (Walk Score Customizado)**:
   - **Nota A (Excelente)**: > 10 comércios/alimentação E > 5 pontos de mobilidade no raio de 2km.
   - **Nota B (Caminhável)**: > 5 comércios E > 2 pontos de mobilidade.
   - **Nota C (Dependente)**: Infraestrutura básica ou insuficiente.

3. **Qualidade do Ar (AQI)**:
   - Baseado no índice europeu (EAQI). Classificado visualmente: Verde (Bom), Amarelo (Moderado), Vermelho (Crítico).

4. **Socioeconômico**:
   - Exibição de Renda Média per capita e Taxa de Saneamento Básico (IBGE).

## 5. Estrutura do Banco de Dados
### Tabela: `location_reports`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `cep` | String (Unique) | CEP limpo (apenas números) |
| `lat`, `lng` | Decimal (10,8) | Coordenadas geográficas |
| `pois_json` | JSON | Lista completa de estabelecimentos e pontos de ônibus |
| `climate_json` | JSON | Dados de clima atual |
| `wiki_json` | JSON | Resumo e URL da imagem da Wikipédia |
| `air_quality_index` | Integer | Índice de qualidade do ar atual |
| `walkability_score` | String (A, B, C) | Nota de caminhabilidade |
| `average_income` | Decimal | Renda média da região |
| `sanitation_rate` | Decimal | % de saneamento básico |
| `populacao` | BigInt | População total do município |
| `raw_ibge_data` | JSON | Metadados regionais do IBGE |

## 6. Endpoints do Sistema
- `GET /`: Home.
- `POST /search`: Recebe o CEP e redireciona para `/cep/{cep}`.
- `GET /cep/{cep}`: Relatório completo (SEO-friendly).