# Mapa de Rotas (Backend)

Este documento mostra onde cada grupo de rotas está para facilitar manutenção e busca.

## Contrato canonico

- Para clientes internos novos, use `/api/v1/*` como base canonica e oficial.
- `/api/*` continua existindo apenas como camada de compatibilidade ate o sunset configurado em `LEGACY_API_SUNSET`.
- Respostas servidas por rotas legadas `/api/*` agora incluem `X-Legacy-Api: true`, `X-Legacy-Api-Successor`, `Link: <...>; rel="successor-version"` e, quando `LEGACY_API_SUNSET` estiver configurado, tambem `Deprecation` e `Sunset`.
- O backend tambem registra telemetria leve de consumo legado: primeiro hit do dia por rota e novos marcos a cada 100 chamadas daquela rota legado.
- O contrato funcional de auth, session e CSRF para esse namespace esta documentado em `docs/frontend-pilot-v1.md`.
- Rotas SSR do site publico em `routes/web/*` continuam intencionais e nao invalidam o status de API-first para os consumidores JS/mobile.

## Ordem de carga em runtime

Definida em `Application/Bootstrap/Application.php`:

1. `routes/web.php`
2. `routes/auth.php`
3. `routes/admin.php`
4. `routes/api.php`
5. `routes/webhooks.php`

## Arquivos de rotas

| Área | Arquivo(s) |
|---|---|
| Site público (loader) | `routes/web.php` |
| Web: landing | `routes/web/01_landing.php` |
| Web: card + legais | `routes/web/02_card_legal.php` |
| Web: blog | `routes/web/03_blog.php` |
| Web: redirects legados | `routes/web/04_legacy_redirects.php` |
| Web: sitemap | `routes/web/05_sitemap.php` |
| Autenticação (loader) | `routes/auth.php` |
| Auth: login/cadastro | `routes/auth/01_login_register.php` |
| Auth: verificação email + Google | `routes/auth/02_email_google.php` |
| Auth: recuperação de senha | `routes/auth/03_password_reset.php` |
| Auth: exclusão de conta | `routes/auth/04_account_delete.php` |
| Admin web (loader) | `routes/admin.php` |
| Admin: páginas principais | `routes/admin/01_main_pages.php` |
| Admin: perfil + configurações | `routes/admin/02_profile_config.php` |
| Admin: finanças + billing | `routes/admin/03_finance_billing.php` |
| Admin: páginas sysadmin | `routes/admin/04_sysadmin_views.php` |
| Admin: redirects legados | `routes/admin/05_legacy_redirects.php` |
| API (loader) | `routes/api.php` |
| API: acesso usuário/sessão/perfil | `routes/api/01_user_access.php` |
| API: dashboard + relatórios | `routes/api/02_dashboard_reports.php` |
| API: lançamentos + transações | `routes/api/03_lancamentos_transactions.php` |
| API: contas/categorias/cartões | `routes/api/04_contas_categorias_cartoes.php` |
| API: gamificação + finanças | `routes/api/05_gamification_financas.php` |
| API: notificações (pt) + preferências | `routes/api/06_notificacoes_userprefs.php` |
| API: premium + cupons | `routes/api/07_premium_cupons.php` |
| API: faturas + parcelamentos | `routes/api/08_faturas_parcelamentos.php` |
| API: sysadmin core + blog | `routes/api/09_sysadmin_core_blog.php` |
| API: IA (usuário + sysadmin) | `routes/api/10_ai.php` |
| API: campanhas + notifications (en) | `routes/api/11_campaigns_notifications.php` |
| API: plano + referral + feedback | `routes/api/12_plan_referral_feedback.php` |
| API v1: frontend pilot | `routes/api/13_frontend_pilot_v1.php` |
| API v1: financas compartilhadas | `routes/api/14_financas_shared_v1.php` |
| API v1: faturas + parcelamentos | `routes/api/15_faturas_parcelamentos_v1.php` |
| API v1: lancamentos + transactions | `routes/api/16_lancamentos_transactions_v1.php` |
| API v1: reports + gamification | `routes/api/17_reports_gamification_v1.php` |
| API v1: engagement + billing + dashboard | `routes/api/18_engagement_billing_dashboard_v1.php` |
| API v1: sysadmin + adminops | `routes/api/19_sysadmin_adminops_v1.php` |
| API v1: finance + dashboard + AI | `routes/api/20_finance_dashboard_ai_v1.php` |
| API v1: auth | `routes/api/21_auth_v1.php` |
| API v1: integrations | `routes/api/22_integrations_v1.php` |
| API v1: remaining legacy catch-up | `routes/api/23_remaining_legacy_v1.php` |
| Webhooks | `routes/webhooks.php` |

## Busca rápida

Use `rg` para achar endpoints:

```powershell
rg "Router::add\('GET', '/api/v1/ai" routes
rg "Router::add\(.*'/api/" routes/api
rg "Router::add\(.*'/login" routes
rg "Router::add\(.*sysadmin" routes
```

## Verificacao automatica

Suite de qualidade backend (rotas + core + arquitetura de controllers):

```powershell
composer test:backend
```

CI:
- `.github/workflows/routes-integrity.yml`

Opcional (apenas integridade de rotas):

```powershell
composer test:routes
```
