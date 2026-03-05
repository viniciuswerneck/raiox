# Plano de Estabilização do Sistema de Filas (Async Processing)

## Overview
O sistema de relatórios do Raio-X Territorial foi convertido de processamento síncrono para assíncrono para evitar timeouts e sobrecarga no servidor. Recentemente, foram identificados problemas de cache (placeholders bloqueando a geração real) e timeouts em execuções locais. Este plano visa consolidar as correções e garantir que o sistema funcione tanto localmente quanto na Hostinger.

## Project Type: WEB (Laravel + Blade + MySQL)

## Success Criteria
- [ ] Nenhum relatório fica preso infinitamente em "Localizando...".
- [ ] O status `completed` só é atribuído quando os dados reais (Cidade, Bairro, Coordenadas) estão salvos.
- [ ] O sistema detecta falhas de geocodificação e as reporta ao usuário (status `failed`).
- [ ] O servidor não excede o `max_execution_time` durante o polling ou disparo de jobs.

## Tech Stack
- **Backend:** Laravel 12 / PHP 8.4
- **Database:** MySQL (Queue Connection: `database`)
- **Worker:** Web-triggered (local dev) / Cron-based (Production/Hostinger)
- **Frontend:** Vanilla JS Polling (Blade)

## Task Breakdown

### Phase 1: Correção de Lógica de Modelo e Cache (Backend)
- **ID:** `FIX-MODEL-FILLABLE`
- **Agent:** `backend-specialist`
- **Goal:** Garantir que todos os campos dinâmicos (`real_estate_json`, etc) sejam salvos no banco.
- **Action:** Adicionado `real_estate_json` ao array `$fillable` no model `LocationReport`.
- **Status:** ✅ Concluído.

### Phase 2: Correção de Lógica de Cache (Backend)
- **ID:** `FIX-CACHE-LOOP`
- **Agent:** `backend-specialist`
- **Goal:** Garantir que o `NeighborhoodService` ignore registros "Localizando..." ao buscar cache.
- **Status:** ✅ Concluído.

### Phase 3: Estabilização do Job e Geocodificação
- **ID:** `STABILIZE-JOB`
- **Agent:** `debugger`
- **Goal:** Garantir que o Job não marque como sucesso se a geocodificação falhar.
- **Action:** Refinar o retorno do `getFullReport` para lançar exceções em falhas críticas.
- **Status:** ✅ Concluído.

### Phase 3: UX e Polling (Frontend)
- **ID:** `POLISH-LOADER`
- **Agent:** `frontend-specialist`
- **Goal:** Melhorar a tela de "Sincronizando" para mostrar mensagens de erro claras vindas do Job e garantir que o Overlay seja destruído após o sucesso.
- **Status:** 🔄 Em andamento.

### Phase 4: Configuração de Produção (DevOps)
- **ID:** `PROD-CONFIG`
- **Agent:** `devops-engineer`
- **Goal:** Documentar o Cron Job necessário para a Hostinger sem depender de Supervisor.
- **Status:** ⏳ Pendente.
