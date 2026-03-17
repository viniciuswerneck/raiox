# PLANO DE AÇÃO: Correção da Narrativa Territorial

## Contexto
Após a migração para a arquitetura de agentes isolados e o `LlmManagerService` (Maestro), as requisições de narrativa estão sendo disparadas, mas o conteúdo final está vindo nulo, resultando na exceção: `Falha ao gerar narrativa via LlmManager`.

## Objetivos
1. Identificar por que o `LlmManagerService` não está retornando conteúdo válido mesmo com fallbacks ativos.
2. Corrigir a conversão de mensagens para o protocolo nativo do Google Gemini.
3. Garantir que o `GenerateNeighborhoodText` job conclua o processamento com sucesso.
4. Validar o log de telemetria (`llm_logs`) para monitorar as tentativas.

## Fases

### Fase 1: Diagnóstico Profundo (Agente: Debugger)
- [ ] Analisar `storage/logs/laravel.log` em busca de erros específicos no loop de fallback do `LlmManagerService`.
- [ ] Verificar o estado das chaves de API na tabela `ai_keys`.
- [ ] Executar o script de teste `fix_narrative.php` com logs de depuração ativados para ver a resposta bruta dos provedores.

### Fase 2: Ajuste do Maestro (Agente: Backend Specialist)
- [ ] Corrigir a estrutura do payload enviado ao Gemini se houver erro de conformidade com a API V1Beta.
- [ ] Validar a rotação de chaves e os logs de `LlmLog`.
- [ ] Garantir que o fallback para `openrouter` funcione quando o Gemini local falha.

### Fase 3: Validação (Agente: Test Engineer)
- [ ] Executar o Job manualmente para o CEP `06501007`.
- [ ] Verificar se o campo `history_extract` foi preenchido corretamente.
- [ ] Rodar os scripts de verificação (lint e segurança).

## Pessoas Envolvidas (Agentes)
- `debugger`: Investigação de erros e logs.
- `backend-specialist`: Ajustes no serviço de IA e protocolos.
- `test-engineer`: Validação final e scripts de teste.
