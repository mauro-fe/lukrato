# Lukrato AI — Sistema de Qualidade

## Arquitetura do Pipeline

```
Mensagem do usuário
  │
  ├─ TextNormalizer::normalize()      → expande abreviações WhatsApp (vc→você, ss→sim, hj→hoje)
  ├─ NumberNormalizer::normalize()     → normaliza números BR (2 mil→2000, duzentos→200, 1.5k→1500)
  │
  ▼
IntentRouter::detect()
  ├─ [1] Multi-turn flow ativo?       → CREATE_ENTITY (confidence 1.0)
  ├─ [2] Cache hit?                   → intent cacheado (confidence 0.95)
  ├─ [3] ConfirmationIntentRule       → CONFIRM_ACTION (confidence 1.0, requer PendingAiAction)
  ├─ [4] EntityCreationIntentRule     → CREATE_ENTITY (confidence 0.85-0.9)
  ├─ [5] TransactionIntentRule        → EXTRACT_TRANSACTION (confidence 0.7-0.9)
  ├─ [6] QuickQueryIntentRule         → QUICK_QUERY (confidence 0.8)
  ├─ [7] AnalysisIntentRule           → ANALYZE (confidence 0.75)
  ├─ [8] CategorizationIntentRule     → CATEGORIZE (confidence 0.75)
  ├─ [9] SmartFallbackRule            → EXTRACT_TRANSACTION (confidence 0.65)
  └─ [fallback] Nenhum match          → CHAT (confidence 0.5, abaixo do threshold)
  │
  ▼
AIService::dispatch()
  ├─ confidence < 0.6?  → redireciona para ChatHandler
  ├─ Resolve handler pelo intent
  ├─ handler->handle($request) → AIResponseDTO
  └─ logDispatch() → ai_logs (com intent, confidence, prompt_version)
```

**Threshold de confiança:** 0.6 — abaixo disso, a mensagem vai para o ChatHandler (fallback LLM).

## Como Rodar Avaliações

### Benchmark completo de intents
```bash
vendor/bin/phpunit --filter=AIBenchmarkTest --stderr
```
Mostra: accuracy por intent, taxa de fallback, confiança média, falhas individuais.

### Benchmark de extração de transação
```bash
vendor/bin/phpunit --filter=TransactionDetectorTest --stderr
```
Mostra: taxa de extração correta, falhas por campo (valor, descrição, tipo).

### Testes NLP (TextNormalizer + NumberNormalizer)
```bash
vendor/bin/phpunit --filter=NLPNormalizerTest
```

### Testes de regressão das intent rules
```bash
vendor/bin/phpunit --filter=IntentRulesRegressionTest
```

### Suite completa de IA
```bash
vendor/bin/phpunit tests/Unit/Services/AI/
```

### Benchmarks com relatório detalhado
```bash
vendor/bin/phpunit tests/Unit/Services/AI/ --stderr --testdox
```

## Como Interpretar Métricas

### Em testes (benchmark)
| Métrica | Target | Descrição |
|---------|--------|-----------|
| Intent accuracy | ≥ 80% | % de mensagens com intent correto |
| Fallback rate | ≤ 15% | % de mensagens caindo no ChatHandler |
| Transaction extraction | ≥ 75% | % de transações extraídas corretamente |
| Entity creation | ≥ 75% | % de entity types detectados corretamente |
| Confirmation | ≥ 85% | % de confirmações/negações detectadas |

### Em produção (dashboard /sysadmin/ai/logs)
| Métrica | Target | Descrição |
|---------|--------|-----------|
| Low Confidence Rate | < 20% | % de chamadas com confidence < 0.6 |
| Fallback to Chat Rate | < 25% | % de chamadas resolvidas pelo ChatHandler |
| Success Rate | > 95% | % de chamadas sem erro técnico |
| Avg Response Time | < 3000ms | Latência média (inclui chamadas LLM) |

## Como Adicionar Novos Casos de Benchmark

1. **Identifique o tipo de caso**: intent detection, transaction extraction, NLP, entity creation, ou confirmation.

2. **Edite o fixture correspondente** em `tests/Fixtures/AI/`:
   - `intent_detection_cases.php` — detecção de intent
   - `transaction_extraction_cases.php` — extração de transação
   - `nlp_normalization_cases.php` — normalização de texto/números
   - `entity_creation_cases.php` — detecção de tipo de entidade
   - `confirmation_cases.php` — confirmação/negação

3. **Siga o formato do arquivo**:
   - Intent: `[mensagem, intent_esperado, confidence_minima, tags[], notas]`
   - Transaction: `['input' => ..., 'expected' => [...], 'tags' => [...], 'notes' => ...]`
   - NLP: `[input, expected_output, component, tags[], notes]`
   - Entity: `[mensagem, entity_type_esperado, confidence_min, tags[], notes]`
   - Confirmation: `[mensagem, is_affirmative, is_negative, tags[], notes]`

4. **Rode o benchmark**:
   ```bash
   vendor/bin/phpunit --filter=AIBenchmarkTest --stderr
   ```

5. **Se a accuracy cair**, investigue os casos que falharam no relatório e ajuste regras ou thresholds se necessário.

## Prompt Versioning

As versões dos prompts estão em `Application/Services/AI/PromptBuilder.php` na constante `PROMPT_VERSIONS`.

### Como funciona
- Cada prompt tem uma versão semântica (ex: `1.0`)
- Ao alterar o conteúdo de um prompt, incremente a versão
- O campo `prompt_version` no `ai_logs` registra qual versão foi usada em cada chamada
- Use SQL para correlacionar mudanças de prompt com mudanças em métricas:

```sql
SELECT prompt_version,
       COUNT(*) as total,
       AVG(CASE WHEN success THEN 1 ELSE 0 END) as success_rate,
       AVG(confidence) as avg_confidence
FROM ai_logs
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY prompt_version;
```

### Como incrementar versão
1. Edite o prompt em `PromptBuilder.php`
2. Atualize a versão correspondente em `PROMPT_VERSIONS`
3. Deploy e monitore o efeito no dashboard

## Riscos Conhecidos

1. **Composite numbers parcialmente suportados** — "cento e cinquenta" (150) funciona, mas "cento e trinta" (130) não. NumberNormalizer mapeia apenas expressões que têm entrada específica no EXTENSO_MAP.

2. **SmartFallbackRule confidence 0.65** — Muito perto do threshold 0.6. Se o threshold for aumentado, esta regra para de funcionar.

3. **QuickQueryHandler getSaldo() é O(n)** — Executa 2 queries por conta bancária. Usuários com muitas contas podem ter latência alta.

4. **Sem rate limiting no pipeline de IA** — Um cliente malicioso pode gerar custo alto de tokens.

5. **Sem retry/circuit-breaker no OpenAIProvider** — Se a API falhar, a resposta é erro imediato.

6. **Cache de intent é user-agnostic** — Mesma mensagem normalizada de diferentes usuários retorna o mesmo intent cacheado. Correto para intent, mas não para contexto.

7. **Timezone do servidor usado para "hoje"** — PromptBuilder e detectDate() usam o timezone do servidor (America/Sao_Paulo por padrão). Usuários em outros timezones podem ver data incorreta.

8. **LIKE wildcards não escapados** — Em CategoryRuleEngine e ConfirmationHandler, buscas LIKE não escapam `%` e `_` no input do usuário. Risco baixo mas tecnicamente um bug.
