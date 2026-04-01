# Frontend Refactor Baseline (Web)

## Objective
- Refatorar JS por dominio sem quebrar funcionalidade de telas PHP existentes.
- Padrao alvo por dominio:
  - `index.js`: bootstrap e bridge legado apenas.
  - `state.js`: estado/config/selectors.
  - `api.js`: IO e integracao remota.
  - `ui.js` ou `render.js`: render e manipulacao de DOM.
  - `app.js`/`services`: regras de negocio.
  - `legacy-bridge.js` (quando necessario): superficie `window.*`.

## Minimum Gate (every step)
1. `npm run lint:js`
2. `npm run check:js-size`
3. `npm run build:local`

Comando agregado:
- `npm run guard:front`

## Out-Of-Scope Artifacts
- `public/build/*`
- `storage/vite-check/*`

Esses artefatos podem ser gerados durante validacao, mas nao entram no escopo de refatoracao funcional.

## Screen And Domain Map (Vite entrypoints)
- Core admin: `global`, `lancamento-global`, `lancamentos`, `dashboard`
- Finance domains: `contas`, `contas-arquivadas`, `cartoes`, `cartoes-arquivadas`, `faturas`, `financas`, `orcamento`, `metas`, `relatorios`, `categorias`, `perfil`, `billing`
- Shared modals/ux: `card-modals`, `gamification`, `gamification-dashboard`
- Sysadmin: `sysadmin`, `sysadmin-communications`, `sysadmin-cupons`, `sysadmin-blog`, `sysadmin-ai`, `sysadmin-ai-logs`
- Auth: `auth-login`, `auth-forgot-password`, `auth-reset-password`, `auth-verify-email`
- Public site: `landing-base`, `site-card`

## Critical Manual Regression Checklist

### Common
- Carregamento inicial sem erros de console bloqueantes.
- Botoes primarios e atalhos de teclado principais funcionam.
- Tooltips, toasts e modais abrem/fecham corretamente.

### Faturas
- Listagem renderiza e filtros funcionam.
- Abrir detalhes da fatura funciona.
- Acao de pagamento/quitacao e acao de edicao continuam operando.

### Relatorios
- Troca de abas e filtros atualiza graficos.
- Resumo/insights/comparativos carregam sem erro.
- Exportacao (PDF/Excel) abre modal e realiza download.

### Dashboard, Categorias, Financas, Orcamento, Metas
- Render inicial e atualizacao de cards/graficos.
- CRUD principal (quando existir) sem regressao visual.
- Estados vazio/erro/loading renderizam corretamente.

### Legacy Bridge
- Callbacks `window.*` usados em views PHP continuam disponiveis.
- Nenhuma tela perde comportamento por quebra de binding global.

## File Size Guardrails
- Meta de `index.js`: ate 150 linhas.
- Meta de qualquer arquivo JS: ate 800 linhas.
- Excecao documentada no proprio arquivo com `@size-exception`.
- Baseline atual de divida de tamanho esta em `docs/frontend-size-allowlist.json`.
- O script `check:js-size` bloqueia qualquer arquivo acima do limite fora dessa allowlist.

## Execution Order
1. Piloto de baixo risco: `faturas/ui.js`, `relatorios/app.js` (ja iniciado).
2. Replicar no dominio: `dashboard`, `categorias`, `financas`, `orcamento`, `metas`.
3. So depois atacar alto blast radius: `lancamento-global`, `lancamentos/modal`, `global/help-center`.

## PR Strategy
- Um dominio por PR.
- PR pequeno e vertical (refactor + gate + checklist).
- Sem mistura de artefatos de build no diff funcional.
