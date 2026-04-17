# Checklist de Corte - Separacao Frontend/Backend

## Definicao de pronto
- [ ] O frontend roda em origem e build proprios, consumindo apenas contratos REST do backend e sem depender de HTML renderizado pelo backend.
- [ ] O backend expõe apenas JSON, webhooks, rotinas de fila/cron e arquivos/upload.
- [ ] Nenhuma rota de usuario final emite HTML em producao.
- [ ] Nenhuma tela critica depende de seed SSR, pageContext, window.LK ou same-origin implícito.
- [ ] Todas as rotas legadas e aliases foram removidas ou desativadas.

## Pacote de inicio

- Usar `docs/inicio_execucao_split_frontend_backend.md` como documento de arranque para Fases 0, 1 e 2.
- O checklist abaixo continua sendo a cobertura completa; o documento de inicio organiza o que entra primeiro e com quais contratos.

## Plano de execucao por fases

- A ordem abaixo define a sequencia recomendada de execucao. O checklist detalhado por modulo continua valendo como cobertura.
- Regra pratica: nenhuma fase corta rota legada em producao antes da fase anterior estar validada em homologacao e observabilidade.
- Risco alto significa chance real de quebrar login, sessao, navegacao, mobile ou integracoes externas.

### Fase 0. Inventario, contrato-alvo e regra de corte
- Objetivo: descobrir todos os consumidores reais, congelar os contratos REST alvo e definir a politica de compatibilidade antes de mover UI ou desligar rotas.
- Executar aqui: inventario completo do item 1, item 2.9 Contratos e rotas do frontend, item 2.10 Consumidores, rollout e compatibilidade.
- Dependencias: nenhuma.
- Bloqueia: todas as outras fases.
- Risco: Alto. Se esta fase ficar incompleta, o corte final tende a quebrar mobile, auth ou consumidores silenciosos.
- Saida esperada: mapa de consumidores por rota, estrategia de autenticacao por tipo de cliente, janela de deprecacao e contrato REST alvo por dominio.

### Fase 1. Infra cross-origin e runtime minimo
- Objetivo: preparar frontend e API para funcionar fora de same-origin, sem fallback HTML e sem runtime injetado por pagina PHP.
- Executar aqui: item 2.1 Infra de separacao e as partes de 2.8 ligadas a base URL, runtime e boot.
- Dependencias: Fase 0.
- Bloqueia: Fases 2, 3, 4, 5, 6 e 7.
- Risco: Alto. Erros aqui aparecem como CORS, 401, 419, cookie perdido, HTML inesperado ou frontend preso ao backend.
- Saida esperada: origens definidas, CORS/cookies/CSRF funcionando para browser e mobile, API sem fallback HTML e boot sem dependencia critica de window.LK ou window.bootstrap.

### Fase 2. Auth e sessao externalizadas
- Objetivo: tornar autenticacao, sessao e fluxos de verificacao consumiveis por frontend separado e por clientes nao-web.
- Executar aqui: item 2.2 Auth e os testes de compatibilidade dos consumidores que usam login, logout, renew, verificacao de email, reset e Google OAuth.
- Dependencias: Fases 0 e 1.
- Bloqueia: Fases 4, 5, 6, 7 e o corte final.
- Risco: Muito alto. Esta e a fase com maior chance de regressao visivel para usuario final.
- Saida esperada: login/logout/reset/verify/google API-first, URLs do frontend externalizadas, bridges legadas apenas onde ainda houver consumidor ativo e mobile validado nos mesmos contratos.

### Fase 3. Frontend assume build, runtime e roteamento
- Objetivo: tirar do backend a responsabilidade de shell, bundle e roteamento de pagina.
- Executar aqui: restante do item 2.8, todo o item 2.9 e as partes de 2.4 ligadas a shell/layout.
- Dependencias: Fases 0, 1 e 2.
- Bloqueia: desligamento seguro de routes/web, routes/admin e routes/auth.
- Risco: Alto. Se esta fase for pulada, o backend continua dono da navegacao mesmo com APIs prontas.
- Saida esperada: build do frontend fora do backend, frontend dono de 404/500/manutencao, sem pageContext/currentViewPath/currentViewId no boot principal e shell admin PHP fora do caminho critico.

### Fase 4. Site publico por REST
- Objetivo: migrar landing, legal, card, blog, sitemap e paginas auxiliares para frontend separado consumindo contratos REST.
- Executar aqui: item 2.3 Site publico e contratos REST.
- Dependencias: Fases 0, 1 e 3. Se houver dependencia de login/social nas paginas publicas, tambem depende da Fase 2.
- Pode rodar em paralelo com Fases 5 e 7 depois da Fase 3.
- Risco: Medio. O maior risco aqui e SEO, sitemap e regressao de paginas institucionais.
- Saida esperada: paginas publicas fora do backend, estrategia de SEO resolvida e sitemap gerado fora da camada PHP de views.

### Fase 5. App autenticada: admin, financas e core
- Objetivo: migrar o produto principal para o frontend separado sem deixar a shell PHP como dependencia operacional.
- Executar aqui: itens 2.4 Admin app e 2.5 Financas e core.
- Dependencias: Fases 0, 1, 2 e 3.
- Bloqueia: corte das rotas admin e remocao de HandlesAdminLayoutData do caminho principal.
- Risco: Muito alto. Esta fase mexe no fluxo central do produto e exige rollout mais controlado por dominio funcional.
- Saida esperada: dashboard, lancamentos, contas, cartoes, billing, relatorios e demais dominios rodando no frontend; APIs core JSON-only; controllers de pagina admin fora do fluxo principal.

### Fase 6. Importacoes e fluxos com arquivo
- Objetivo: fechar os fluxos com upload, preview, confirmacao, historico e configuracoes sem reload de pagina e sem tela admin legado.
- Executar aqui: item 2.6 Importacoes e as validacoes de uploads/downloads ligadas ao item 2.10.
- Dependencias: Fases 0, 1, 2 e 3. Na pratica, fica mais segura depois da Fase 5 estabilizar a app autenticada.
- Bloqueia: desligamento final das paginas admin de importacao.
- Risco: Alto. O risco principal esta em upload de arquivo, preview, confirmacao e inconsistencias de estado.
- Saida esperada: page-init suficiente para todas as telas, fluxo de arquivo API-first e controllers admin de importacao removiveis.

### Fase 7. Sysadmin, blog e comunicacoes
- Objetivo: retirar do backend as telas operacionais de sysadmin e blog, mantendo apenas APIs e operacoes backend necessarias.
- Executar aqui: item 2.7 Sysadmin, blog e comunicacoes.
- Dependencias: Fases 0, 1, 2 e 3.
- Pode rodar em paralelo com Fases 4 e 5 depois da base estabilizar.
- Risco: Medio. O impacto de negocio costuma ser menor que o core, mas ainda envolve operacao e conteudo.
- Saida esperada: sysadmin/blog fora da shell PHP, apenas caminhos API e operacionais permanecendo no backend.

### Fase 8. Corte final, limpeza e producao
- Objetivo: desligar caminhos legados, consolidar o backend como API-only e validar a operacao em producao.
- Executar aqui: item 3 O que fica no backend, item 4 Validacao final e remocao de aliases, bridges e rotas de compatibilidade restantes.
- Dependencias: todas as fases anteriores.
- Risco: Muito alto. Esta fase remove as ultimas muletas de compatibilidade e por isso deve ser a ultima.
- Gate de entrada: nenhum consumidor ativo nas rotas legadas, rollout/rollback definidos, observabilidade pronta e smoke tests verdes.
- Saida esperada: nenhuma rota de usuario final emitindo HTML em producao, backend restrito a JSON/webhooks/jobs/uploads e mapa final de rotas removidas ou mantidas.

## Dependencias criticas entre fases

- Fase 0 bloqueia tudo. Sem inventario e contrato-alvo, qualquer migracao vira tentativa e erro.
- Fase 1 bloqueia toda externalizacao real. Nao vale migrar tela se CORS, cookie, CSRF e runtime ainda dependem do backend.
- Fase 2 bloqueia qualquer corte serio de auth, rotas web/auth e consumidores mobile.
- Fase 3 bloqueia o desligamento seguro da shell PHP e das rotas de pagina.
- Fase 5 precisa estabilizar o core antes do corte final do admin.
- Fase 8 so comeca quando todas as outras estiverem validadas em ambiente semelhante ao de producao.

## 1. Inventario completo do legado atual

### Rotas publicas
- [ ] routes/web.php
- [ ] routes/web/01_landing.php
- [ ] routes/web/02_card_legal.php
- [ ] routes/web/03_blog.php
- [ ] routes/web/04_legacy_redirects.php
- [ ] routes/web/05_sitemap.php

### Rotas de auth
- [ ] routes/auth.php
- [ ] routes/auth/01_login_register.php
- [ ] routes/auth/02_email_google.php
- [ ] routes/auth/03_password_reset.php
- [ ] routes/auth/04_account_delete.php

### Rotas admin
- [ ] routes/admin.php
- [ ] routes/admin/01_main_pages.php
- [ ] routes/admin/02_profile_config.php
- [ ] routes/admin/03_finance_billing.php
- [ ] routes/admin/04_sysadmin_views.php
- [ ] routes/admin/05_legacy_redirects.php
- [ ] routes/admin/06_frontend_pilot.php

### Rotas API
- [ ] routes/api.php
- [ ] routes/api/01_user_access.php
- [ ] routes/api/02_dashboard_reports.php
- [ ] routes/api/03_lancamentos_transactions.php
- [ ] routes/api/04_contas_categorias_cartoes.php
- [ ] routes/api/05_gamification_financas.php
- [ ] routes/api/06_notificacoes_userprefs.php
- [ ] routes/api/07_premium_cupons.php
- [ ] routes/api/08_faturas_parcelamentos.php
- [ ] routes/api/09_sysadmin_core_blog.php
- [ ] routes/api/10_ai.php
- [ ] routes/api/11_campaigns_notifications.php
- [ ] routes/api/12_plan_referral_feedback.php
- [ ] routes/api/13_frontend_pilot_v1.php
- [ ] routes/api/14_financas_shared_v1.php
- [ ] routes/api/15_faturas_parcelamentos_v1.php
- [ ] routes/api/16_lancamentos_transactions_v1.php
- [ ] routes/api/17_reports_gamification_v1.php
- [ ] routes/api/18_engagement_billing_dashboard_v1.php
- [ ] routes/api/19_sysadmin_adminops_v1.php
- [ ] routes/api/20_finance_dashboard_ai_v1.php
- [ ] routes/api/21_auth_v1.php
- [ ] routes/api/22_integrations_v1.php
- [ ] routes/api/23_remaining_legacy_v1.php

### Templates e assets ainda acoplados
- [ ] views/site/*
- [ ] views/admin/*
- [ ] views/errors/*
- [ ] views/site/auth/google-confirm.php
- [ ] resources/js/site/*
- [ ] resources/js/admin/*
- [ ] public/api/csrf-token.php
- [ ] package.json
- [ ] vite.config.js
- [ ] vite.config.producao.js
- [ ] postcss.config.js
- [ ] tailwind.config.js
- [ ] eslint.config.mjs
- [ ] vitest.config.mjs

### Consumidores e clientes que precisam sobreviver ao corte
- [ ] Frontend web principal deve consumir apenas a API separada.
- [ ] mobile/* precisa entrar no inventario de consumidores antes de remover qualquer rota legada.
- [ ] Validar se ai-service/*, integracoes externas, webhooks recebidos e jobs internos dependem de endpoints, cookies, URLs ou payloads legados.

### Controllers e camadas de acoplamento
- [ ] Application/Controllers/Site/*
- [ ] Application/Controllers/Auth/*
- [ ] Application/Controllers/Admin/*
- [ ] Application/Controllers/SysAdmin/*
- [ ] Application/Controllers/GamificationController.php
- [ ] Application/Controllers/Settings/AccountController.php
- [ ] Application/Controllers/Concerns/HandlesWebPresentation.php
- [ ] Application/Controllers/Concerns/HandlesAdminLayoutData.php
- [ ] Application/Controllers/Concerns/HandlesAuthGuards.php
- [ ] Application/Controllers/BaseController.php
- [ ] Application/Controllers/WebController.php
- [ ] Application/Controllers/ApiController.php deve ser a base do backend, sem misturar responsabilidade de pagina.

### Infra e auth/cors/csrf
- [ ] Application/Lib/Auth.php
- [ ] Application/Middlewares/CsrfMiddleware.php
- [ ] Application/Bootstrap/SecurityHeaders.php
- [ ] Application/Config/AuthRuntimeConfig.php
- [ ] Application/Config/InfrastructureRuntimeConfig.php
- [ ] Application/Config/SecurityRuntimeConfig.php
- [ ] Application/Support/Admin/AdminModuleRegistry.php
- [ ] Application/Modules/*
- [ ] routes/api/23_remaining_legacy_v1.php precisa zerar antes do corte final.

## 2. Corte por modulo

### 2.1 Infra de separacao
- [ ] Definir explicitamente a origem do frontend e a origem da API, sem depender de same-origin implícito.
- [ ] Trocar o uso de same-origin em resources/js/admin/shared/api.js por uma base URL do frontend configurada pelo proprio frontend.
- [ ] Revalidar cookies, SameSite e credenciais para funcionar com frontend em outra origem, se a autenticacao continuar baseada em cookie.
- [ ] Definir explicitamente a estrategia de autenticacao para browser, mobile e outros consumidores: cookie + CSRF cross-origin, token, ou modelo misto documentado.
- [ ] Remover public/api/csrf-token.php e manter apenas o fluxo oficial de renovacao de CSRF exposto pela API.
- [ ] Remover o allowlist de unsafe-eval em Application/Bootstrap/SecurityHeaders.php assim que os bundles legados sairem.
- [ ] Remover a dependencia de window.LK, window.LKFeedback, window.LKPageLoading e window.bootstrap para o boot principal do produto.
- [ ] Garantir que respostas de API jamais retornem HTML de fallback.

### 2.2 Auth
- [ ] Migrar login, cadastro, logout, recuperacao de senha, reset, verificacao de email, resend e Google OAuth para contratos de API consumidos pelo frontend.
- [ ] Remover fallback para views PHP em Application/Controllers/Auth/LoginController.php, ForgotPasswordController.php, EmailVerificationController.php, GoogleCallbackController.php e RegistroController.php.
- [ ] Converter /login, /register/criar, /recuperar-senha, /resetar-senha, /verificar-email, /verificar-email/aviso, /auth/google/* e /config/excluir-conta para o fluxo do frontend ou desligar as rotas backend.
- [ ] Normalizar rotas de sessao e auth ainda usadas por consumidores fora do frontend web, como login/entrar, logout, api/session/status, api/session/renew e api/v1/csrf/refresh.
- [ ] Remover views/admin/auth/* e a view de confirmacao Google em views/site/auth/google-confirm.php.
- [ ] Garantir que Application/Config/AuthRuntimeConfig.php so forneca URLs para o frontend externo e nao navegue a aplicacao via PHP.
- [ ] Padronizar respostas JSON para logout, callbacks e erros de auth, inclusive token expirado, email nao verificado e cancelamento Google.

### 2.3 Site publico e contratos REST
- [ ] Criar e manter contratos REST para landing, contato, legal, card, blog, sitemap e paginas auxiliares.
- [ ] Fazer o frontend publico consumir esses contratos; o backend nao deve renderizar HTML dessas rotas.
- [ ] Remover Application/Controllers/Site/LandingController.php, CardController.php, LegalController.php, AprendaController.php e SitemapController.php do caminho principal do backend.
- [ ] Remover views/site/partials/*, views/site/landing/*, views/site/legal/*, views/site/card/index.php, views/site/aprenda/* e qualquer template publico restante.
- [ ] Remover routes/web/01_landing.php, 02_card_legal.php, 03_blog.php, 04_legacy_redirects.php e 05_sitemap.php quando o frontend estiver consumindo os contratos REST.
- [ ] Se o frontend mantiver SEO, gerar HTML e sitemap fora do backend, sempre a partir dos dados expostos pela API.

### 2.4 Admin app
- [ ] Transferir dashboard, lancamentos, faturas, relatorios, perfil, configuracoes, contas, cartoes, orcamento, metas, categorias, billing, gamification e frontend pilot para o frontend.
- [ ] Remover Application/Controllers/Admin/*, Application/Controllers/GamificationController.php e os templates em views/admin/*.
- [ ] Remover views/admin/partials/* e toda dependencia de layout gerado por HandlesAdminLayoutData.
- [ ] Remover routes/admin/01_main_pages.php, 02_profile_config.php, 03_finance_billing.php, 04_sysadmin_views.php, 05_legacy_redirects.php e 06_frontend_pilot.php quando o frontend assumir.
- [ ] Parar de injetar currentViewPath, currentViewId, menu, breadcrumbs, sidebar e footerModules via backend.
- [ ] Transformar AdminModuleRegistry e Application/Modules/* em configuracao do frontend ou eliminá-los junto com a shell.

### 2.5 Financas e core
- [ ] Manter as APIs de contas, categorias, cartoes, transacoes, lancamentos, faturas, metas, orcamentos, billing e dashboard.
- [ ] Garantir que todos os controllers em Application/Controllers/Api/Conta, Categoria, Cartao, Lancamentos, Fatura, Metas, Orcamentos, Dashboard, Financas e Billing nunca chamem renderResponse.
- [ ] Remover qualquer controller de pagina antiga dessas areas que ainda exista em Application/Controllers/Admin/.
- [ ] Validar que routes/api/02_dashboard_reports.php, 03_lancamentos_transactions.php, 04_contas_categorias_cartoes.php, 05_gamification_financas.php, 07_premium_cupons.php, 08_faturas_parcelamentos.php, 14_financas_shared_v1.php, 15_faturas_parcelamentos_v1.php, 16_lancamentos_transactions_v1.php, 17_reports_gamification_v1.php, 18_engagement_billing_dashboard_v1.php e 20_finance_dashboard_ai_v1.php sejam JSON-only.

### 2.6 Importacoes
- [ ] Manter ImportacoesHistoricoPageDataService, ImportacoesIndexPageDataService e os demais services compartilhados.
- [ ] Remover as paginas admin de importacao quando os endpoints page-init forem suficientes.
- [ ] Garantir que index, configuracoes e historico dependam apenas de GET /api/v1/importacoes/page-init, GET /api/v1/importacoes/configuracoes/page-init e GET /api/v1/importacoes/historico/page-init.
- [ ] Eliminar reload completo de pagina para filtros, exclusao e confirmacao.
- [ ] Remover Application/Controllers/Admin/ImportacoesController.php, ImportacoesConfiguracoesController.php e ImportacoesHistoricoController.php quando o frontend assumir tudo.
- [ ] Manter preview, confirmacao e exclusao como API pura.

### 2.7 Sysadmin, blog e comunicacoes
- [ ] Transferir as telas de super admin, comunicacoes, blog, AI logs e AI dashboard para frontend ou para um painel separado.
- [ ] Remover Application/Controllers/SysAdmin/SuperAdminController.php, CommunicationController.php, BlogViewController.php, AiViewController.php e AiLogsViewController.php.
- [ ] Manter apenas os controllers API de sysadmin e blog em Application/Controllers/Api/... e os controllers de operacao em SysAdmin para JSON.
- [ ] Remover views/admin/sysadmin/* e os modulos JS de sysadmin em resources/js/admin/sysadmin/*.
- [ ] Validar que routes/api/09_sysadmin_core_blog.php e routes/api/19_sysadmin_adminops_v1.php sejam o unico caminho de operacao.

### 2.8 Frontend build e runtime
- [ ] Mover o toolchain de frontend para o projeto frontend: package.json, vite.config.js, vite.config.producao.js, postcss.config.js, tailwind.config.js, eslint.config.mjs e vitest.config.mjs.
- [ ] Mover resources/js/site/* e resources/js/admin/* para o frontend app, mantendo o backend so como provedor de API e arquivos de upload.
- [ ] Revisar qualquer uso de BASE_URL, window.APP_BASE_URL, window.LK, window.LKFeedback, window.LKPageLoading e window.bootstrap para nao depender do HTML do backend.
- [ ] Reescrever resources/js/admin/shared/api.js, bootstrap-context.js e bootstrap-shell.js para consumir um contrato de runtime fornecido pelo frontend, nao pelo servidor de paginas.
- [ ] Eliminar dependencias de pageContext, currentViewPath e currentViewId do boot principal.
- [ ] Validar os entrypoints frontend-pilot, perfil, contas, cartoes, categorias, billing, lancamentos, importacoes, dashboard e relatorios fora do backend.

### 2.9 Contratos e rotas do frontend
- [ ] Definir um contrato REST por dominio e por pagina do frontend, incluindo site publico, auth, app autenticada e sysadmin.
- [ ] Fazer o frontend ser dono das rotas e do roteamento de paginas, inclusive 404, 500 e manutencao.
- [ ] Padronizar envelope JSON, status codes, paginação, filtros, ordenação, validação e mensagens de erro.
- [ ] Publicar especificacao OpenAPI ou JSON Schema para os contratos principais e manter isso sincronizado com a implementacao.
- [ ] Garantir que uploads, downloads, imagens e demais assets usem URLs e contratos proprios, sem depender de views PHP para montagem de pagina.

### 2.10 Consumidores, rollout e compatibilidade
- [ ] Inventariar todos os consumidores da API antes do corte: frontend web, mobile, integrações externas, webhooks recebidos, jobs, CRONs e qualquer cliente terceiro.
- [ ] Mapear por consumidor quais endpoints, headers, cookies, redirects, arquivos e formatos de erro ainda sao usados.
- [ ] Nao remover rotas legadas enquanto existir consumidor ativo nelas, especialmente mobile com release ja distribuida.
- [ ] Definir janela de compatibilidade e politica de deprecacao para aliases e endpoints antigos, com data de desligamento.
- [ ] Criar smoke tests e, quando possivel, contract tests para os consumidores criticos antes de desligar rotas antigas.
- [ ] Validar uploads, downloads, exportacoes CSV/binarias, avatar, blog media, templates CSV e sitemap fora do fluxo SSR.
- [ ] Definir plano de rollout e rollback: ordem de deploy frontend/API, feature flag de corte, monitoração e criterio objetivo para reverter.
- [ ] Garantir que base URLs e configuracao de ambiente de todos os clientes nao apontem para caminhos locais ou acoplados ao public/ legado.

## 3. O que fica no backend
- [ ] Manter Application/Controllers/Api/* como a camada oficial de dados.
- [ ] Manter Application/Services/*, Application/Repositories/*, Application/Models/*, Application/DTO/*, Application/Validators/* e Application/UseCases/* como backend-only.
- [ ] Manter cli/*, database/*, storage/* e rotinas de integracao como backend-only.
- [ ] Manter views/emails/* para envio de email, se ainda forem necessarias, sem expor isso como tela do frontend.
- [ ] Manter os webhooks em routes/webhooks.php e seus controllers como integracoes do backend.

## 4. Validacao final
- [ ] Rodar smoke tests de auth, bootstrap, importacoes, dashboard, lancamentos, faturas e Google/email verification.
- [ ] Validar navegacao do frontend a partir de uma origem diferente do backend.
- [ ] Validar o app mobile e qualquer outro consumidor real contra a API final, inclusive login, renovacao de sessao, CSRF e uploads.
- [ ] Confirmar que nenhuma rota em routes/web, routes/admin ou routes/auth retorna HTML em producao.
- [ ] Confirmar que todas as rotas restantes em routes/api retornam JSON consistente.
- [ ] Remover aliases legados e rotas de compatibilidade quando o frontend ja usar os endpoints novos.
- [ ] Monitorar erros 401, 403, 404, 419, 422 e falhas de CORS/preflight durante e depois do corte.
- [ ] Documentar o mapa final de rotas removidas, rotas mantidas e rotas migradas para o frontend.