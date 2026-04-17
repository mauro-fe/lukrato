# Inicio da Execucao - Split Frontend/Backend

## Objetivo

Este documento transforma as Fases 0, 1 e 2 do checklist em um pacote de arranque executavel.
Ele serve para responder tres perguntas antes de abrir os primeiros PRs:

- quais consumidores ja existem hoje;
- quais decisoes tecnicas precisam ficar congeladas agora;
- qual e o primeiro lote de implementacao com menor risco e maior desbloqueio.

## Decisao fechada

- Topologia escolhida: corte inicial no mesmo host canonico `https://lukrato.com.br/`, com frontend web e API convivendo no mesmo dominio durante a transicao.
- Frontend app: `https://lukrato.com.br`
- API e backend legado durante a transicao: `https://lukrato.com.br/` com contratos sob `/api/*` e `/api/v1/*`
- Site publico: `https://lukrato.com.br` com compatibilidade opcional para `https://www.lukrato.com.br`
- Contrato interno oficial: `/api/v1/*` passa a ser o namespace canonico para web JS, mobile e integracoes internas.
- Site publico SSR permanece no backend por enquanto; separar landing, blog e paginas legais nao faz parte deste lote enquanto nao existir motivo comercial para isso.
- Motivo da escolha: o produto ja roda em `lukrato.com.br`, a stack atual usa sessao com cookie + CSRF e ainda fixa `SameSite=Lax`; manter o mesmo host reduz risco e evita trocar auth no lote inicial.

## Valores fechados

### Backend
- `APP_URL=https://lukrato.com.br/`
- `BASE_URL=https://lukrato.com.br/`
- `ALLOWED_DOMAINS=lukrato.com.br,www.lukrato.com.br`
- `ALLOWED_ORIGINS=https://lukrato.com.br,https://www.lukrato.com.br`
- `LEGACY_API_SUNSET=2026-12-31T23:59:59Z`
- `FRONTEND_APP_URL=https://lukrato.com.br`
- `FRONTEND_LOGIN_URL=https://lukrato.com.br/login`
- `FRONTEND_FORGOT_PASSWORD_URL=https://lukrato.com.br/recuperar-senha`
- `FRONTEND_RESET_PASSWORD_URL=https://lukrato.com.br/resetar-senha`
- `FRONTEND_VERIFY_EMAIL_NOTICE_URL=https://lukrato.com.br/verificar-email/aviso`
- `FRONTEND_GOOGLE_CONFIRM_URL=https://lukrato.com.br/auth/google/confirm-page`
- `FRONTEND_DASHBOARD_URL=https://lukrato.com.br/dashboard`
- `FRONTEND_WELCOME_URL=https://lukrato.com.br/dashboard?welcome=1`

### Dependencia adicional obrigatoria
- `GOOGLE_REDIRECT_URI=https://lukrato.com.br/auth/google/callback`

### Mobile
- `apiBaseUrl=https://lukrato.com.br/`
- Observacao: o valor versionado agora aponta para a API canonica de producao; para desenvolvimento local, o app precisa de override explicito antes de rodar contra localhost.

## O que ja existe pronto para apoiar o inicio

- `Application/Config/AuthRuntimeConfig.php` ja suporta externalizacao de URLs do frontend por `FRONTEND_*`.
- `routes/api/21_auth_v1.php` ja expõe login, logout, register, Google OAuth, email verify e password reset em `api/v1/auth/*`.
- `routes/api/01_user_access.php`, `routes/api/13_frontend_pilot_v1.php` e `routes/api/20_finance_dashboard_ai_v1.php` ja expõem session status, renew, CSRF refresh e `api/v1/user/bootstrap`.
- `resources/js/admin/api/endpoints/*` ja concentra boa parte dos contratos `api/v1/*` do admin.
- `mobile/src/*` ja consome contratos `api/v1/*` para auth, dashboard, contas, cartoes, lancamentos e perfil.

## Decisoes para congelar agora

### 1. Namespace canonico da API
- Recomendacao: `api/v1/*` vira o contrato canonico do corte.
- Consequencia: `api/*` continua apenas como bridge de compatibilidade enquanto existirem consumidores reais.
- Estado decidido: para consumo interno, esta recomendacao agora esta oficializada.
- Nao fazer agora: criar uma segunda familia de endpoints para o mesmo dominio.

### 2. Estrategia de autenticacao do primeiro corte
- Recomendacao: manter sessao com cookie + CSRF como modelo canonico do corte 1 para browser e mobile.
- Motivo: web admin e mobile ja operam assim hoje; trocar para JWT ou outro token antes da separacao aumenta risco e retrabalho.
- Reavaliar depois: somente quando a shell PHP sair do caminho critico e os contratos de auth estiverem estaveis.

### 2.1. Topologia de deploy para cookie auth
- Recomendacao: se o corte inicial mantiver cookie + CSRF, frontend web e API devem ficar no mesmo host canonico ou, no maximo, em hosts same-site sob o mesmo dominio registravel.
- Motivo: a base atual de sessao e resposta ainda fixa `SameSite=Lax` em varios pontos. Isso e compativel com mesmo site, mas nao com frontend hospedado em outro site independente mantendo o mesmo modelo de cookie.
- Consequencia: se a ideia for usar frontend em outro site diferente do backend, o PR 1 precisa incluir mudanca explicita de estrategia de sessao (`SameSite=None; Secure` ou auth por token), nao apenas CORS.

### 3. Ownership de runtime
- Recomendacao: o frontend passa a ser dono da propria `apiBaseUrl`; o backend fica dono apenas das URLs de retorno e redirecionamento expostas por `FRONTEND_*`.
- Consequencia: `window.LK`, meta `base-url`, `same-origin` implicito e fallbacks para `/public/` deixam de ser fonte canonica.

### 4. CORS e origem
- Recomendacao: origens permitidas devem sair de configuracao, nao de lista hard-coded em codigo.
- Consequencia: homologacao e frontend externo podem ser habilitados sem novo patch estrutural.

### 4.1. Politica de media, uploads e arquivos
- Recomendacao: no corte inicial, uploads, avatars, imagens e exports podem continuar servidos pelo backend, mas a URL canonica deles precisa apontar para o host publico final do backend.
- Motivo: hoje existem payloads e modelos que ainda montam URLs com `BASE_URL` para avatar, blog e outros assets.
- Consequencia: `BASE_URL` e `APP_URL` deixam de ser detalhe local e passam a ser parte critica do contrato operacional.

### 5. Regra de remocao
- Recomendacao: nenhuma rota legada e removida antes de existir evidencia de zero consumidores ativos e validacao em homologacao.
- Consequencia: mobile e bridges de auth/session continuam vivos ate o fim do rollout.

### 6. Escopo do SSR publico
- Recomendacao: manter landing, blog e paginas legais servidos pelo backend atual ate existir demanda comercial clara por frontend separado.
- Motivo: para API interna, forcar o site publico a consumir HTTP do proprio backend adiciona custo e complexidade sem retorno proporcional.
- Consequencia: o sistema fica API-first nos consumidores reais, sem transformar controllers web em clientes HTTP do proprio backend.

## Inventario executavel dos consumidores atuais

### Frontend web publico
- Evidencia principal: `resources/js/site/landing/contact.js` e `resources/js/site/api/endpoints/engagement.js`.
- Contratos visiveis hoje: `api/v1/contato/enviar`.
- Auth atual: nao depende de sessao para o contato.
- Acoplamento atual: pagina ainda nasce de views PHP e a URL da API ainda e derivada do host atual.
- Primeiro movimento: congelar `api/v1/contato/enviar` como contrato publico e fazer o frontend publico ler `apiBaseUrl` propria.
- Estado atual: o site publico ja consegue ler `api-base-url` dedicada via meta/global, sem depender implicitamente do host corrente como fonte canonica da API.

### Frontend web auth e shell admin
- Evidencia principal: `resources/js/admin/shared/api.js`, `resources/js/admin/auth/login/index.js`, `resources/js/admin/auth/forgot-password/index.js`, `resources/js/admin/auth/reset-password/index.js`, `resources/js/admin/auth/verify-email/index.js`.
- Contratos visiveis hoje: `api/v1/csrf/refresh`, `api/v1/auth/*`, `api/v1/session/*`, `api/v1/user/bootstrap`.
- Auth atual: sessao com cookie + CSRF.
- Acoplamento atual: `credentials: 'same-origin'`, `window.LK`, meta csrf, `base-url` por HTML e shell renderizada pelo backend.
- Primeiro movimento: tirar `same-origin` e `window.LK` do caminho canonico do client shared.

### Frontend web app autenticada
- Evidencia principal: `resources/js/admin/api/endpoints/preferences.js`, `finance.js`, `lancamentos.js`, `faturas.js`, `importacoes.js`, `sysadmin.js`, `sysadmin-blog.js`, `notifications.js`, `gamification.js`, `integrations.js`.
- Contratos visiveis hoje: `api/v1/user/*`, `api/v1/contas*`, `api/v1/cartoes*`, `api/v1/lancamentos*`, `api/v1/transfers`, `api/v1/faturas*`, `api/v1/importacoes*`, `api/v1/sysadmin*`, `api/v1/notificacoes*`, `api/v1/gamification*`, `api/v1/telegram*`, `api/v1/whatsapp*`.
- Auth atual: sessao com cookie + CSRF.
- Acoplamento atual: shell/admin layout, `pageContext`, `currentViewPath`, `currentViewId`, base URL do backend e navegacao por rotas PHP.
- Primeiro movimento: estabilizar runtime, auth e `user/bootstrap` antes de migrar dominios de produto.

### Mobile app
- Evidencia principal: `mobile/src/features/auth/repositories/auth-repository.ts`, `dashboard/repositories/dashboard-repository.ts`, `contas/repositories/contas-repository.ts`, `cartoes/repositories/cartoes-repository.ts`, `lancamentos/repositories/lancamentos-repository.ts`, `perfil/repositories/perfil-repository.ts`, `mobile/src/lib/api/http-client.ts`, `mobile/src/lib/config/app-config.ts`.
- Contratos visiveis hoje: `api/v1/auth/login`, `api/v1/auth/logout`, `api/v1/session/status`, `api/v1/session/renew`, `api/v1/csrf/refresh`, `api/v1/dashboard/*`, `api/v1/contas*`, `api/v1/cartoes*`, `api/v1/lancamentos*`, `api/v1/options`, `api/v1/perfil*`, `api/v1/telegram/*`, `api/v1/suporte/enviar`.
- Auth atual: sessao com cookie + CSRF.
- Acoplamento atual: `credentials: 'include'` e fallback para `http://10.0.2.2/lukrato/public/` ou `http://localhost/lukrato/public/`.
- Primeiro movimento: congelar esses contratos como obrigatorios no corte 1 e remover dependencia de fallback local como fonte de producao.

### AI service
- Evidencia principal: `ai-service/main.py` e `ai-service/routers/*`.
- Achado atual: sem evidencia direta de consumo da API PHP; o servico conversa com `ollama_base_url` local.
- Risco atual: baixo para o corte frontend/backend, mas ainda precisa confirmacao manual de integracoes indiretas.
- Primeiro movimento: registrar explicitamente que ele nao bloqueia o lote 1 ate prova em contrario.

### Backend interno, webhooks e jobs
- Evidencia principal: `cli/*`, `routes/webhooks.php`, workers, scheduler e services internos.
- Papel no split: continuam backend-only; nao sao consumidores que impedem externalizar o frontend.
- Primeiro movimento: mantelos fora do escopo dos PRs iniciais e apenas mapear dependencias de payload quando necessario.

## Variaveis de ambiente para definir antes do PR 1

### Backend
- `FRONTEND_APP_URL`
- `FRONTEND_LOGIN_URL`
- `FRONTEND_FORGOT_PASSWORD_URL`
- `FRONTEND_RESET_PASSWORD_URL`
- `FRONTEND_VERIFY_EMAIL_NOTICE_URL`
- `FRONTEND_GOOGLE_CONFIRM_URL`
- `FRONTEND_DASHBOARD_URL`
- `FRONTEND_WELCOME_URL`
- `ALLOWED_ORIGINS` para explicitar as origens de frontend permitidas pela API

### Frontend web separado
- `VITE_API_BASE_URL`
- `VITE_APP_URL`

### Mobile
- `apiBaseUrl` em `mobile/app.json` ou extra equivalente do Expo
- regra explicita de ambiente para nao depender de fallback local em producao

## Primeiro lote de implementacao recomendado

### PR 1. Runtime, origem e CORS configuraveis
- Objetivo: remover hard-codes de origem e abrir caminho para frontend externo sem quebrar o comportamento atual.
- Arquivos iniciais: `Application/Config/InfrastructureRuntimeConfig.php`, `Application/Bootstrap/SecurityHeaders.php`, `Application/Bootstrap/SessionManager.php`, `Application/Bootstrap/SessionConfig.php`, `public/index.php`, `public/index.producao.php`, `resources/js/admin/shared/api.js`, `mobile/src/lib/config/app-config.ts`.
- Entrega minima: CORS controlado por configuracao, topologia de sessao definida, client shared preparado para `apiBaseUrl` externa e mobile sem depender de `/public/` como verdade de producao.
- Risco: alto, mas concentrado e facil de validar.
- Criterio de aceite: uma origem de homolog compativel com a topologia escolhida consegue chamar `api/v1/csrf/refresh` e `api/v1/session/status` sem fallback HTML, e a sessao continua trafegando como esperado no browser.

### PR 2. Contrato canonico de auth, session e bootstrap
- Objetivo: consolidar o lote que desbloqueia login, permanencia de sessao e boot do app separado.
- Arquivos iniciais: `Application/Config/AuthRuntimeConfig.php`, `routes/api/01_user_access.php`, `routes/api/13_frontend_pilot_v1.php`, `routes/api/20_finance_dashboard_ai_v1.php`, `routes/api/21_auth_v1.php`, `resources/js/admin/auth/*`, `mobile/src/features/auth/repositories/auth-repository.ts`.
- Entrega minima: `api/v1/auth/*`, `api/v1/session/*`, `api/v1/csrf/refresh` e `api/v1/user/bootstrap` tratados como contratos canonicos e mantidos para web e mobile.
- Risco: muito alto; precisa smoke test de login/logout/renew/bootstrap.
- Criterio de aceite: web e mobile usam os mesmos contratos `v1` e bridges antigas continuam apenas onde ainda existe dependencia real.

### PR 3. Guardrails de rollout
- Objetivo: impedir corte cego enquanto as telas ainda sao migradas por dominio.
- Escopo: smoke tests minimos, telemetria de uso das rotas legadas e checklist de rollback.
- Areas iniciais: `tests/*` para smoke tests de auth/session/bootstrap e o ponto atual de logging/request tracking do backend.
- Entrega minima: saber quando quebramos login, sessao, CSRF, bootstrap ou CORS e saber se ainda existe trafego em rotas legadas; respostas em `/api/*` devem apontar o sucessor `v1` e, quando configurado, expor a data de sunset.
- Risco: medio.
- Criterio de aceite: alertas e testes cobrindo 401, 403, 419, 422, CORS/preflight e uso de bridges legadas.

## Nao fazer no lote inicial

- Nao reescrever auth para JWT agora.
- Nao assumir que CORS sozinho resolve frontend separado com cookie auth; a topologia de sessao precisa ser definida antes.
- Nao remover `routes/web`, `routes/admin` ou `routes/auth` antes de web e mobile estarem validados nos contratos novos.
- Nao mover importacoes, uploads e exportacoes antes de runtime/auth estarem estaveis.
- Nao introduzir um segundo mapa de endpoints paralelos ao `api/v1`.
- Nao deixar o mobile continuar com fallback local como comportamento implicito de producao.

## Ordem pratica para abrir trabalho agora

- Abrir PR 1 para runtime/origem/CORS.
- Em paralelo, registrar os valores reais das variaveis de ambiente acima por ambiente.
- Abrir PR 2 logo depois para auth/session/bootstrap.
- Criar PR 3 antes de qualquer remocao de rota legada.