# specs.md — Raio-X de Vizinhança

> Especificações técnicas e decisões de produto.
> Atualizado: 2026-03-03

---

## Sessões de Desenvolvimento — Registro de Mudanças

### Sessão 2026-03-03 (Mais Recente)

#### Funcionalidades Implementadas

**1. Análise de Vizinhança com Google Gemini AI (REGRA DE OURO)**
- Integração do `GeminiService` com API `gemini-2.0-flash`.
- Geração de texto de **4 a 8 parágrafos** envolventes e comerciais.
- **Enriquecimento Obrigatório:** O Gemini usa seu próprio conhecimento para complementar a Wikipedia.
- **Validação de Cache:** Resumos com menos de 1000 caracteres são descartados e regenerados.

**2. Wikipedia com Fallback Inteligente**
- 4 tentativas em cascata: `Bairro_(Cidade)` → `Bairro` → `Cidade_(UF)` → `Cidade`
- Validação de artigos: rejeita artigos não-geográficos (geometria, matemática, etc.)
- Filtro de termos ambíguos ("Centro", "Norte", etc.) — não buscados sozinhos
- Busca conteúdo completo via `/page/mobile-sections/` para alimentar o Gemini com mais contexto

**3. Overpass API com 3 Endpoints de Fallback**
- Problema: servidor principal sobrecarregado com frequência
- Solução: tenta `overpass-api.de` → `overpass.kumi.systems` → `maps.mail.ru/osm`
- Raio de busca: 10km

**4. Loader de IA Animado (welcome.blade.php)**
- Núcleo central com anéis orbitando em direções opostas
- 7 mensagens rotativas com fade (IBGE, Wikipedia, Satélite, OSM, Gemini AI...)
- Badges iluminados que acendem conforme a etapa ativa
- Barra de progresso indeterminada

**5. Dashboard de Relatório (report/show.blade.php)**
- Badge dinâmico na seção História: indica se veio do Bairro ou Município
- Link "Ler mais na Wikipedia" com ícone
- Todas as categorias de POIs mapeadas e traduzidas para PT-BR

---

## Problema Conhecido: Cidades Pequenas com Wikipedia Curta

**Sintoma:** Texto gerado muito curto para municípios com artigos Wikipedia pequenos (ex: Jarinu/SP).

**Causa:** Article Wikipedia curto → Gemini recebe pouco input → gera pouco output.

**Solução implementada (2026-03-03):**
O prompt do Gemini instrui explicitamente a usar conhecimento próprio quando o texto de referência for insuficiente. Comportamento:
- Referência rica (>500 chars) → Gemini reescreve/expande o conteúdo da Wikipedia
- Referência curta (<500 chars) → Gemini usa conhecimento próprio sobre o local para complementar

---

## Dados Simulados (Pendente Integração Real)

| Campo | Status | Fonte Ideal |
|-------|--------|-------------|
| `average_income` | ⚠️ `rand(1500, 4500)` | SIDRA/IBGE Censo |
| `sanitation_rate` | ⚠️ `rand(65, 98)` | SNIS/IBGE |
| `idhm` | ⚠️ null | Atlas Brasil / PNUD |