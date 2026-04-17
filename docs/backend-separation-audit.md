# Auditoria de Separacao Frontend x Backend

## Status atual

O objetivo valido para esta frente e simples: frontend e backend devem conversar por REST, sem introduzir uma segunda sidebar, breadcrumbs duplicados ou uma nova moldura visual dentro das paginas reais do admin.

Com isso, o estado atual fica assim:

- os contratos REST versionados continuam sendo a direcao principal
- o runtime config global do admin continua aproveitando `GET /api/v1/user/bootstrap` para estado autenticado compartilhado
- a tentativa de renderizar uma shell visual extra dentro de paginas reais do admin foi revertida
- os adapters, marcadores de view e estilos que existiam apenas para essa shell duplicada foram removidos das paginas reais afetadas

## O que ja esta em contrato REST

- autenticacao REST para clientes JS/mobile via `POST /api/v1/auth/login` e `POST /api/v1/auth/logout`
- aliases versionados para billing/premium sob `api/v1`
- bootstrap autenticado compartilhado via `GET /api/v1/user/bootstrap`
- entrada de Google OAuth sob `GET /api/v1/auth/google/login` e `GET /api/v1/auth/google/register`
- etapa pendente do Google consumivel via `GET /api/v1/auth/google/pending`
- validacao de reset de senha via `GET /api/v1/auth/password/reset/validate`
- verificacao de email via `GET /api/v1/auth/email/verify`
- notice de email nao verificado via `GET /api/v1/auth/email/notice`

No admin, isso ja ajuda a reduzir acoplamento porque partes do frontend deixam de depender de seeds SSR fixos e passam a sincronizar estado com API e runtime config.

## Ajuste de direcao confirmado

A direcao rejeitada foi a rollout de uma shell visual adicional dentro das paginas reais do admin. Esse caminho nao faz parte do objetivo de separacao por REST e foi removido das paginas afetadas.

O que foi mantido:

- integracoes REST
- runtime config global
- resolucao de endpoints versionados
- boot comum de paginas quando ele nao depende de shell visual extra

O que foi removido:

- blocos visiveis de shell duplicada dentro de paginas reais
- `data-bootstrap-*` adicionados apenas para essa renderizacao
- adapters JS por pagina dedicados a montar sidebar, breadcrumbs e footer extras
- CSS compartilhado usado apenas por essa shell duplicada

## Bloqueios restantes para separar o backend

### 1. OAuth social ainda depende de sessao e redirects do backend

Mesmo com endpoints REST expostos, o callback e a conclusao do login social ainda dependem de sessao PHP e de redirects controlados pelo backend.

Para um frontend separado, o fluxo ideal continua sendo:

- frontend iniciando OAuth por endpoint proprio
- frontend lendo estado pendente via API
- callback e pos-login desacoplados da renderizacao PHP tradicional

### 2. A navegacao principal ainda parte de paginas PHP

Hoje varias telas do admin ainda sao servidas como paginas PHP, mesmo quando o miolo da tela ja consome API. Isso significa que a separacao total ainda exige uma shell propria fora do backend PHP, mas sem repetir o menu atual dentro do conteudo.

### 3. Ainda ha rotas e telas para revisar pagina a pagina

Separar backend de verdade ainda pede uma auditoria continua para identificar:

- seeds SSR restantes
- chamadas antigas fora de `api/v1`
- estados que ainda nascem da view em vez de API/runtime config

## Proxima leva recomendada

1. Continuar a migracao pagina a pagina para contratos REST, sem alterar a navegacao visual existente.
2. Redesenhar callback e pos-login do Google OAuth para um fluxo compativel com frontend separado.
3. Definir depois a shell do frontend separado fora do PHP, reaproveitando os contratos REST existentes em vez de renderizar uma moldura extra nas views atuais.