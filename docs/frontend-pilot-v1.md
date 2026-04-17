# API V1 Internal Contract

## Objective
- Oficializar o contrato atual de `/api/v1` para consumidores internos do produto.
- `/api/v1/*` e o contrato canonico para admin em JS, mobile e fluxos publicos que dependem de runtime JS.
- `/api/*` fica apenas como compatibilidade temporaria ate o sunset configurado.
- Os aliases versionados espelham as rotas legadas `/api/*`; qualquer divergencia exige atualizar testes e documentacao antes.

## Positioning
- Este contrato e interno. Ele nao assume API publica para terceiros nem auth por Bearer token como fluxo principal.
- O backend pode continuar servindo SSR em `routes/web/*` e `routes/auth/*` sem deixar de ser API-first para os consumidores reais.
- O site publico SSR permanece fora de escopo neste momento.

## Scope
- `auth`
- `csrf`
- `session`
- `contato` e `suporte`
- `perfil`
- `user` preferences
- `notificacoes`

## Compatibility Rules
- O piloto continua usando cookie de sessao PHP. Nao existe JWT neste passo.
- O modelo oficial de auth para a v1 interna continua sendo sessao PHP + cookie + CSRF.
- `Application\Middlewares\AuthMiddleware` continua validando sessao, nao token Bearer.
- `Application\Core\Request::bearerToken()` existe apenas como utilitario; a presenca do header `Authorization` nao autentica a requisicao por si so.
- Chamadas ainda feitas em `/api/*` podem receber os headers `X-Legacy-Api: true`, `X-Legacy-Api-Successor` e `Link: <...>; rel="successor-version"` apontando para o caminho canonico em `/api/v1/*`.
- Quando `LEGACY_API_SUNSET` estiver configurado, essas respostas tambem passam a expor `Deprecation` e `Sunset` para orientar a retirada dos aliases com data.
- Rotas com middleware `auth` exigem sessao autenticada.
- Rotas com middleware `csrf` aceitam token por header `X-CSRF-TOKEN` ou `X-CSRF-Token`, ou no corpo em `csrf_token` ou `_token`.
- Quando existir token valido na sessao, a resposta pode incluir header `X-CSRF-TTL` com o tempo restante do token padrao.
- Envelope padrao de sucesso:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

- Envelope padrao de erro:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

- Excecao de compatibilidade importante: `GET /api/v1/session/status` retorna `401` com detalhes em `errors` quando nao existe sessao valida.

## Bootstrap Recomendado
1. Chamar `GET /api/v1/session/status`.
2. Se `data.authenticated === true`, carregar em paralelo:
   - `GET /api/v1/user/theme`
   - `GET /api/v1/perfil`
   - `GET /api/v1/notificacoes/unread`
3. Se `data.expired === true` e `data.canRenew === true`, chamar `POST /api/v1/session/renew`.
4. Atualizar o token CSRF em memoria com `data.newToken` antes de novos `POST` ou `DELETE`.

## Auth and CSRF

### Auth model
- `POST /api/v1/auth/login` autentica a sessao do usuario.
- `POST /api/v1/auth/logout` encerra a sessao autenticada e exige `auth` + `csrf`.
- `POST /api/v1/auth/register` cria conta seguindo o mesmo modelo de sessao do backend atual.
- Fluxos Google OAuth continuam ancorados no backend para callback e estado pendente, mas os contratos de leitura e follow-up ficam expostos sob `/api/v1/auth/google/*`.
- Para browser e mobile internos, o cliente deve enviar cookies de sessao no request. Esse continua sendo o modelo oficial deste contrato.

### CSRF model
- `POST /api/v1/csrf/refresh` e o endpoint canonico para obter ou renovar o token CSRF.
- O token retornado deve ser mantido em memoria pelo cliente e reenviado em mutacoes protegidas por `csrf`.
- Headers aceitos: `X-CSRF-TOKEN` e `X-CSRF-Token`.
- Campos de corpo aceitos para compatibilidade: `csrf_token` e `_token`.
- `GET` e outras leituras sem middleware `csrf` nao exigem esse token, mas continuam sujeitas a `auth` quando aplicavel.

### Official boundary
- Para clientes JS/mobile internos, a regra e simples: toda mutacao autenticada usa `/api/v1/*`, cookies de sessao e token CSRF valido.
- Controllers web, views SSR e jobs internos nao precisam chamar HTTP contra a propria API para reutilizar regra de negocio.

## Endpoint Map

### Auth
- `POST /api/v1/auth/login`
  - middlewares: `ratelimit`
  - sucesso: autentica a sessao e retorna payload JSON do fluxo de login
- `POST /api/v1/auth/logout`
  - middlewares: `auth`, `csrf`
  - sucesso: encerra a sessao autenticada atual
- `POST /api/v1/auth/register`
  - middlewares: `ratelimit`
  - sucesso: cria conta seguindo o contrato atual do backend
- `GET /api/v1/auth/google/login`
- `GET /api/v1/auth/google/register`
- `GET /api/v1/auth/google/callback`
- `GET /api/v1/auth/google/pending`
- `GET /api/v1/auth/google/confirm-page`
- `GET /api/v1/auth/google/confirm`
- `GET /api/v1/auth/google/cancel`
- `GET /api/v1/auth/email/verify`
- `GET /api/v1/auth/email/notice`
- `POST /api/v1/auth/email/resend`
- `POST /api/v1/auth/password/forgot`
- `GET /api/v1/auth/password/reset/validate`
- `POST /api/v1/auth/password/reset`

### CSRF
- `POST /api/v1/csrf/refresh`
  - middlewares: `ratelimit`
  - sucesso: retorna token CSRF atual ou renovado para o cliente

### Session
- `GET /api/v1/session/status`
  - middlewares: nenhum
  - autenticado: retorna `data.authenticated`, `data.expired`, `data.remainingTime`, `data.showWarning`, `data.canRenew`, `data.warningThreshold`, `data.sessionLifetime`, `data.userName`, `data.isRemembered`
  - sem sessao: retorna `401` com `errors.authenticated`, `errors.expired`, `errors.remainingTime`, `errors.showWarning`, `errors.canRenew`
- `POST /api/v1/session/renew`
  - middlewares: `ratelimit`
  - sucesso: retorna `data.newToken`, `data.remainingTime`, `data.expiresAt`
- `POST /api/v1/session/heartbeat`
  - middlewares: `auth`, `csrf`
  - sucesso: retorna `data.alive`, `data.remainingTime`

### Contato e suporte
- `POST /api/v1/contato/enviar`
  - middlewares: `ratelimit`
  - comportamento igual ao legado
- `POST /api/v1/suporte/enviar`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - comportamento igual ao legado

### Perfil
- `GET /api/v1/perfil`
  - middlewares: `auth`
  - sucesso: retorna `data.user`
- `POST /api/v1/perfil`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `data.message`, `data.user`, `data.new_achievements`, `data.email_change_pending`, `data.email_verification_sent`
  - validacao: retorna `422` com `errors.*`
- `POST /api/v1/perfil/senha`
  - middlewares: `auth`, `csrf`, `ratelimit_strict`
  - sucesso: retorna `data.message`
  - validacao: retorna `422` com `errors.senha`, `errors.senha_atual`, `errors.nova_senha` ou `errors.conf_senha`
- `POST /api/v1/perfil/tema`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `data.message`, `data.theme`
  - erro de dominio: retorna `400` com `message` simples para tema invalido
- `POST /api/v1/perfil/avatar`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `data.message`, `data.avatar`, `data.avatar_settings`
  - erro de dominio: retorna `400` com `message` simples para upload ausente ou arquivo invalido
- `POST /api/v1/perfil/avatar/preferences`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `data.message`, `data.avatar_settings`
- `GET /api/v1/perfil/dashboard-preferences`
  - middlewares: `auth`
  - sucesso: retorna `data.preferences`
- `POST /api/v1/perfil/dashboard-preferences`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `message: "Preferencias do dashboard atualizadas"` e `data.preferences`
- `DELETE /api/v1/perfil/avatar`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `data.message`, `data.avatar`, `data.avatar_settings`
- `DELETE /api/v1/perfil/delete`
  - middlewares: `auth`, `csrf`, `ratelimit_strict`
  - sucesso: retorna `data.message`

### User Preferences
- `GET /api/v1/user/theme`
  - middlewares: `auth`
  - sucesso: retorna `data.theme`
- `POST /api/v1/user/theme`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `data.message`, `data.theme`
  - validacao: retorna `422` com `errors.theme`
- `POST /api/v1/user/display-name`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `data.message`, `data.display_name`, `data.first_name`
  - validacao: retorna `422` com `errors.display_name`
- `GET /api/v1/user/help-preferences`
  - middlewares: `auth`
  - sucesso: retorna `data.preferences.settings`, `data.preferences.tour_completed`, `data.preferences.offer_dismissed`, `data.preferences.tips_seen`
- `POST /api/v1/user/help-preferences`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `message: "Preferencias de ajuda atualizadas"` e `data.preferences`
  - validacao: retorna `422` com `errors.action` ou `errors.page`
- `GET /api/v1/user/ui-preferences/{page}`
  - middlewares: `auth`
  - sucesso: retorna `data.page`, `data.preferences`
  - pagina invalida: retorna `422` com `errors.page`
- `POST /api/v1/user/ui-preferences/{page}`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - sucesso: retorna `message: "Preferencias de interface atualizadas"`, `data.page`, `data.preferences`
  - validacao: retorna `422` com `errors.page` ou `errors.preferences`
- `GET /api/v1/user/birthday-check`
  - middlewares: `auth`
  - sucesso: sempre retorna `data.is_birthday`; quando aplicavel inclui `data.reason` ou `data.first_name`, `data.age`, `data.full_name`

### Notificacoes
- `GET /api/v1/notificacoes`
  - middlewares: `auth`
  - sucesso: retorna `data.itens`, `data.unread`
- `GET /api/v1/notificacoes/unread`
  - middlewares: `auth`
  - sucesso: retorna `data.unread`
- `POST /api/v1/notificacoes/marcar`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - comportamento igual ao legado
- `POST /api/v1/notificacoes/marcar-todas`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - comportamento igual ao legado
- `GET /api/v1/notificacoes/referral-rewards`
  - middlewares: `auth`
  - comportamento igual ao legado
- `POST /api/v1/notificacoes/referral-rewards/seen`
  - middlewares: `auth`, `csrf`, `ratelimit`
  - comportamento igual ao legado

## Canonical Examples

### `GET /api/v1/session/status` autenticado

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "authenticated": true,
    "expired": false,
    "remainingTime": 1679,
    "showWarning": false,
    "canRenew": true,
    "warningThreshold": 300,
    "sessionLifetime": 1800,
    "userName": "Session User",
    "isRemembered": false
  }
}
```

### `GET /api/v1/session/status` sem sessao

```json
{
  "success": false,
  "message": "Usuario nao autenticado",
  "errors": {
    "authenticated": false,
    "expired": true,
    "remainingTime": 0,
    "showWarning": false,
    "canRenew": false
  }
}
```

### `POST /api/v1/session/renew`

```json
{
  "success": true,
  "message": "Sessao renovada com sucesso",
  "data": {
    "newToken": "64-char-hex-token",
    "remainingTime": 1800,
    "expiresAt": "2026-03-19 11:30:00"
  }
}
```

### `GET /api/v1/user/theme`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "theme": "dark"
  }
}
```

### `POST /api/v1/user/theme`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Preferencia de tema atualizada.",
    "theme": "dark"
  }
}
```

### `POST /api/v1/user/display-name`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Nome de exibicao salvo.",
    "display_name": "Maria Silva",
    "first_name": "Maria"
  }
}
```

### `GET /api/v1/user/help-preferences`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "preferences": {
      "settings": {
        "auto_offer": true
      },
      "tour_completed": {
        "dashboard": "v2"
      },
      "offer_dismissed": {},
      "tips_seen": {
        "perfil": "v1"
      }
    }
  }
}
```

### `POST /api/v1/user/help-preferences`

```json
{
  "success": true,
  "message": "Preferencias de ajuda atualizadas",
  "data": {
    "preferences": {
      "settings": {
        "auto_offer": false
      },
      "tour_completed": {
        "dashboard": "v2"
      },
      "offer_dismissed": {},
      "tips_seen": {
        "perfil": "v1"
      }
    }
  }
}
```

### `GET /api/v1/user/ui-preferences/dashboard`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "page": "dashboard",
    "preferences": {
      "highlightCards": true,
      "density": "compact"
    }
  }
}
```

### `POST /api/v1/user/ui-preferences/dashboard`

```json
{
  "success": true,
  "message": "Preferencias de interface atualizadas",
  "data": {
    "page": "dashboard",
    "preferences": {
      "highlightCards": true,
      "density": "compact"
    }
  }
}
```

### `GET /api/v1/user/birthday-check`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "is_birthday": true,
    "first_name": "Maria",
    "age": 32,
    "full_name": "Maria Silva"
  }
}
```

### `GET /api/v1/perfil`

```json
{
  "success": true,
  "message": "Perfil carregado",
  "data": {
    "user": {
      "nome": "Perfil User"
    }
  }
}
```

### `POST /api/v1/perfil`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Perfil atualizado com sucesso",
    "user": {
      "nome": "Perfil Flags"
    },
    "new_achievements": [],
    "email_change_pending": true,
    "email_verification_sent": true
  }
}
```

### `POST /api/v1/perfil/senha`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Senha alterada com sucesso"
  }
}
```

### `POST /api/v1/perfil/tema`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Tema atualizado com sucesso",
    "theme": "dark"
  }
}
```

### `POST /api/v1/perfil/avatar`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Foto de perfil atualizada!",
    "avatar": "https://example.test/assets/uploads/avatars/avatar_110.webp",
    "avatar_settings": {
      "position_x": 50,
      "position_y": 50,
      "zoom": 1
    }
  }
}
```

### `POST /api/v1/perfil/avatar/preferences`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Enquadramento da foto atualizado.",
    "avatar_settings": {
      "position_x": 42,
      "position_y": 58,
      "zoom": 1.2
    }
  }
}
```

### `GET /api/v1/perfil/dashboard-preferences`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "preferences": {
      "toggleGrafico": true
    }
  }
}
```

### `POST /api/v1/perfil/dashboard-preferences`

```json
{
  "success": true,
  "message": "Preferências do dashboard atualizadas",
  "data": {
    "preferences": {
      "toggleMetas": true
    }
  }
}
```

### `DELETE /api/v1/perfil/avatar`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Foto de perfil removida",
    "avatar": "",
    "avatar_settings": {
      "position_x": 50,
      "position_y": 50,
      "zoom": 1
    }
  }
}
```

### `DELETE /api/v1/perfil/delete`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Conta excluída com sucesso"
  }
}
```

### `GET /api/v1/notificacoes/unread`

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "unread": 5
  }
}
```

## Guardrails
- O alias versionado deve continuar apontando para o mesmo callback e os mesmos middlewares da rota legada correspondente.
- Mudancas de envelope, nomes de campos ou codigos HTTP precisam ser acompanhadas por atualizacao deste documento e dos testes de contrato.
- Enquanto o frontend separado nao estiver em producao, prefira expandir o piloto por alias e testes antes de alterar controladores existentes.