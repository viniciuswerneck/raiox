# Memória e Regras do Projeto (Neighborhood X-Ray)

Este documento serve como a **memória de longo prazo** para o Assistente de IA. Siga estas diretrizes em todas as atualizações.

---

## 🏗️ Estratégia de Dados (Backend)

### 🚨 Regras de Ouro:
1. **Múltiplas Fontes (MANTENHA TODAS)**:
   - Nunca remova as camadas de: Wikipédia (História/Imagens), IBGE (População/Regional), Open-Meteo (Clima/AQI) ou Overpass (POIs).
2. **Consultas Overpass (Sempre `nwr` e `out center`)**:
   - Sempre utilize o comando `nwr` para buscar por *Nodes*, *Ways* e *Relations* simultaneamente.
   - Sempre use `out center` para garantir coordenadas em áreas grandes (como Shopping Centers).
3. **Erros Silenciosos**:
   - Todas as chamadas de API externa devem estar envolvidas por `try/catch` com `Log::error`.
   - Se uma API falhar, o sistema deve continuar carregando o restante dos dados vazios, nunca dar erro 500.

---

## 🎨 Consistência de Interface (Frontend)

### 🚨 Regras de Ouro:
1. **Estética Senior**:
   - Mantenha o design **Premium/Glassmorphism** (backdrop-filter: blur, transparências, bordas arredondadas exageradas `rounded-[3rem]`).
2. **Redundância Zero**:
   - Em listas (como Mobilidade), **NUNCA** repita apenas "Parada de Ônibus".
   - **Prioridade de exibição**: 1º Nome oficial do ponto -> 2º Nome da rua (`addr:street`) -> 3º Tipo (fallback).
3. **Cores por Categoria**:
   - **Azul/Indigo**: Mobilidade e Instituições.
   - **Laranja**: Comércio e Lazer.
   - **Vermelho**: Saúde.
   - **Roxo/Violeta**: Educação.

---

## 💾 Gestão de Cache e Performance

### 🚨 Regras de Ouro:
1. **Ciclo de 30 Dias**:
   - Re-ative o cache após cada rodada de mudanças na estrutura de dados.
   - Verifique a existência de novas colunas (ex: `air_quality_index`) antes de dar o "Cache Hit".

---

## ❌ O que NÃO fazer (Histórico de Erros):
- **NÃO force apenas `node` no Overpass**: Áreas grandes (supermercados) desaparecem.
- **NÃO esqueça do `out center`**: Elementos de área viram apenas IDs e o Leaflet não consegue colocá-los no mapa.
- **NÃO assuma que o `name` existe**: Muitos pontos no OSM não têm nome, use `addr:street` como plano B.
- **NÃO remova o `withoutVerifying()`**: Em ambientes de desenvolvimento sem SSL local configurado, as requisições falham silenciosamente.
