# 🔍 AUDITORIA COMPLETA - SISTEMA DE CARTÃO DE CRÉDITO

**Data:** 29 de janeiro de 2026  
**Sistema:** Lukrato - Controle Financeiro Pessoal  
**Objetivo:** Mapear estado atual antes de refatoração segura  
**Status:** ⚠️ SISTEMA EM PRODUÇÃO - NÃO ALTERAR DURANTE ANÁLISE

---

## 📊 RESUMO EXECUTIVO

### Problema Identificado

O sistema atual **NÃO** separa corretamente:

- **Competência** (mês da despesa)
- **Caixa** (mês do pagamento)

**Comportamento Atual:**

```
┌─────────────────────────────────────────────────────────┐
│ JANEIRO: Compra R$ 1.200 no cartão                     │
├─────────────────────────────────────────────────────────┤
│ ❌ NÃO cria lançamento em Janeiro                       │
│ ❌ NÃO aparece no dashboard de Janeiro                  │
│ ❌ NÃO afeta despesas de Janeiro                        │
│ ✅ Cria apenas item em faturas_cartao_itens (pendente)  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ FEVEREIRO: Paga a fatura                                │
├─────────────────────────────────────────────────────────┤
│ ✅ Cria lançamento em FEVEREIRO                         │
│ ✅ Aparece como despesa de FEVEREIRO                    │
│ ❌ Janeiro fica sem despesa registrada                  │
└─────────────────────────────────────────────────────────┘
```

**Impacto Financeiro:**

- Dashboard mostra saldo incorreto no mês da compra
- Relatórios mensais não refletem despesas reais
- Usuário pensa que gastou menos do que realmente gastou

---

## 🗄️ ESTRUTURA ATUAL DAS TABELAS

### 1. Tabela `lancamentos`

**Local:** Fonte única da verdade financeira

**Estrutura:**

```sql
CREATE TABLE lancamentos (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    tipo ENUM('receita', 'despesa', 'transferencia'),
    data DATE NOT NULL,                    -- Data que aparece no dashboard
    data_pagamento DATE NULL,              -- Quando foi efetivamente pago
    valor DECIMAL(10,2) NOT NULL,
    descricao VARCHAR(255),
    observacao TEXT,
    categoria_id BIGINT NULL,
    conta_id BIGINT NULL,
    conta_id_destino BIGINT NULL,

    -- Flags de controle
    eh_transferencia BOOLEAN DEFAULT FALSE,
    eh_saldo_inicial BOOLEAN DEFAULT FALSE,
    pago BOOLEAN DEFAULT TRUE,             -- Se está pago (afeta saldo)

    -- Campos de cartão de crédito
    cartao_credito_id BIGINT NULL,
    eh_parcelado BOOLEAN DEFAULT FALSE,
    parcela_atual INT NULL,
    total_parcelas INT NULL,

    -- Campos de parcelamento (agrupamento visual)
    parcelamento_id BIGINT NULL,
    numero_parcela INT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_user_data (user_id, data),
    INDEX idx_cartao (cartao_credito_id),
    INDEX idx_data_pagamento (data_pagamento)
)
```

**Campos Críticos:**

- `data`: Campo usado em TODOS os cálculos (dashboard, relatórios, conquistas)
- `data_pagamento`: Existe mas NÃO é usado em cálculos
- `pago`: Flag existente mas não implementada corretamente

### 2. Tabela `faturas_cartao_itens`

**Local:** Itens pendentes de faturas (ainda não são lançamentos)

**Estrutura:**

```sql
CREATE TABLE faturas_cartao_itens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    cartao_credito_id BIGINT NOT NULL,
    fatura_id BIGINT NULL,                 -- FK para tabela faturas
    lancamento_id BIGINT NULL,             -- FK criado quando paga

    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,

    data_compra DATE NOT NULL,             -- Quando foi comprado
    data_vencimento DATE NOT NULL,         -- Quando vence a parcela
    mes_referencia INT NOT NULL,           -- Mês de competência (1-12)
    ano_referencia INT NOT NULL,           -- Ano de competência

    categoria_id BIGINT NULL,

    eh_parcelado BOOLEAN DEFAULT FALSE,
    parcela_atual INT NULL,
    total_parcelas INT NULL,
    item_pai_id BIGINT NULL,

    pago BOOLEAN DEFAULT FALSE,            -- Se foi paga
    data_pagamento DATE NULL,              -- Quando foi paga

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_cartao_vencimento (cartao_credito_id, data_vencimento),
    INDEX idx_fatura (fatura_id),
    INDEX idx_referencia (mes_referencia, ano_referencia)
)
```

**Campos Críticos:**

- `mes_referencia`/`ano_referencia`: MÊS DA COMPRA (competência)
- `data_vencimento`: Quando a parcela vence
- `data_pagamento`: Quando foi efetivamente paga
- `lancamento_id`: NULL até pagar, depois aponta para lançamento criado

### 3. Tabela `faturas`

**Local:** Agrupador lógico (cabeçalho de fatura)

**Estrutura:**

```sql
CREATE TABLE faturas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    cartao_credito_id BIGINT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    numero_parcelas INT NOT NULL,
    data_compra DATE NOT NULL,
    status ENUM('pendente', 'parcial', 'paga', 'cancelado'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**Nota:** Uma fatura PODE ser criada para compra parcelada, MAS na implementação atual também é usada como **agrupador mensal** (1 fatura por mês por cartão).

---

## 🔄 FLUXO ATUAL DE CRIAÇÃO DE LANÇAMENTOS

### Cenário 1: Compra no Cartão (HOJE)

```php
// Service: CartaoCreditoLancamentoService.php
public function criarLancamentoCartao(int $userId, array $data): array
{
    // 1. Validar cartão
    $cartao = CartaoCredito::findOrFail($data['cartao_id']);

    // 2. Calcular vencimento
    $vencimento = $this->calcularDataVencimento(
        $data['data'],                    // Data da compra
        $cartao->dia_vencimento,
        $cartao->dia_fechamento
    );

    // 3. Buscar ou criar fatura mensal
    $fatura = $this->buscarOuCriarFatura(
        $userId,
        $cartao->id,
        $vencimento['mes'],              // Mês de VENCIMENTO (não da compra!)
        $vencimento['ano']
    );

    // 4. Criar ITEM de fatura (NÃO cria lançamento)
    FaturaCartaoItem::create([
        'user_id' => $userId,
        'cartao_credito_id' => $cartao->id,
        'fatura_id' => $fatura->id,
        'descricao' => $data['descricao'],
        'valor' => $data['valor'],
        'data_compra' => $data['data'],
        'data_vencimento' => $vencimento['data'],
        'mes_referencia' => $vencimento['mes'],    // ⚠️ Vencimento, não compra!
        'ano_referencia' => $vencimento['ano'],
        'pago' => false,                           // ✅ Pendente
    ]);

    // 5. Reduzir limite disponível do cartão
    $cartao->atualizarLimiteDisponivel();

    // ❌ NÃO cria lançamento
    // ❌ NÃO afeta dashboard
    // ❌ NÃO afeta saldo
}
```

### Cenário 2: Pagamento da Fatura

```php
// Service: CartaoFaturaService.php
public function pagarFatura(int $cartaoId, int $mes, int $ano, int $userId): array
{
    // 1. Buscar itens pendentes
    $itens = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
        ->where('pago', false)
        ->whereYear('data_vencimento', $ano)
        ->whereMonth('data_vencimento', $mes)
        ->get();

    $dataPagamento = now()->format('Y-m-d');  // HOJE

    // 2. Para cada item, criar lançamento
    foreach ($itens as $item) {
        // ⚠️ CRIA LANÇAMENTO NA DATA DO PAGAMENTO, NÃO DA COMPRA!
        $lancamento = Lancamento::create([
            'user_id' => $userId,
            'conta_id' => $cartao->conta_id,
            'categoria_id' => $item->categoria_id,
            'tipo' => 'despesa',
            'valor' => $item->valor,
            'descricao' => $item->descricao,
            'data' => $dataPagamento,          // ❌ Data do PAGAMENTO
            'observacao' => "Fatura {$mes}/{$ano}",
            'pago' => true,
            'data_pagamento' => $dataPagamento,
        ]);

        // 3. Vincular item ao lançamento
        $item->lancamento_id = $lancamento->id;
        $item->pago = true;
        $item->data_pagamento = $dataPagamento;
        $item->save();
    }

    // 4. Liberar limite do cartão
    $cartao->atualizarLimiteDisponivel();
}
```

**🚨 PROBLEMA:** Lançamento é criado com `data = hoje`, não com `data = data_compra`.

---

## 📈 IMPACTO NOS CÁLCULOS

### Dashboard (DashboardController.php)

```php
// Cálculo de receitas/despesas do mês
$receitas = Lancamento::where('tipo', 'receita')
    ->where('eh_transferencia', 0)
    ->whereBetween('data', [$start, $end])  // ⚠️ Usa campo 'data'
    ->sum('valor');

$despesas = Lancamento::where('tipo', 'despesa')
    ->where('eh_transferencia', 0)
    ->whereBetween('data', [$start, $end])  // ⚠️ Usa campo 'data'
    ->sum('valor');

$resultado = $receitas - $despesas;
```

**Problema:**

- Se comprou R$ 1.000 em Janeiro mas pagou em Fevereiro
- Janeiro mostra R$ 0 de despesa (incorreto)
- Fevereiro mostra R$ 1.000 de despesa (correto em caixa, errado em competência)

### Relatórios (ReportService.php)

```php
// Relatório mensal de despesas por categoria
$rows = DB::table('lancamentos')
    ->whereBetween('lancamentos.data', [$start, $end])  // ⚠️ Usa 'data'
    ->where('lancamentos.tipo', 'despesa')
    ->where('eh_transferencia', 0)
    ->groupBy('categoria_id')
    ->selectRaw('SUM(lancamentos.valor) as total')
    ->get();
```

**Problema:**

- Relatório de Janeiro não mostra despesas do cartão
- Relatório de Fevereiro mostra todas de uma vez

### Gamificação (GamificationService.php)

```php
// Verificar mês positivo (receitas > despesas)
private function hasPositiveMonth(int $userId): bool
{
    $mes = now()->format('Y-m');

    $receitas = Lancamento::where('user_id', $userId)
        ->where('tipo', 'receita')
        ->where('data', 'like', "$mes%")  // ⚠️ Usa 'data'
        ->sum('valor');

    $despesas = Lancamento::where('user_id', $userId)
        ->where('tipo', 'despesa')
        ->where('data', 'like', "$mes%")  // ⚠️ Usa 'data'
        ->sum('valor');

    return $receitas > $despesas;
}
```

**Problema:**

- Conquista "Mês Positivo" pode ser desbloqueada incorretamente
- Usuário gastou mais do que ganhou mas sistema não detectou

---

## 🔴 PONTOS CRÍTICOS IDENTIFICADOS

### 1. Mistura de Conceitos

| Conceito             | O que deveria ser           | O que é hoje          |
| -------------------- | --------------------------- | --------------------- |
| **Despesa**          | Quando gastei (competência) | Quando paguei (caixa) |
| **Saldo**            | Dinheiro disponível         | Correto ✅            |
| **Relatório Mensal** | Todas as despesas do mês    | Só o que foi pago     |
| **Dashboard**        | Visão completa              | Incompleta ❌         |

### 2. Campo `data` Sobrecarregado

O campo `lancamentos.data` é usado para:

- ✅ Receitas e despesas normais → Correto
- ❌ Pagamento de fatura → Deveria ser data da compra
- ✅ Saldo inicial → Correto
- ✅ Transferências → Correto

### 3. Campos Subutilizados

- `data_pagamento`: Existe mas não é usado em cálculos
- `pago`: Existe mas não diferencia competência/caixa
- `mes_referencia`/`ano_referencia`: Só em itens, não em lançamentos

### 4. Lógica Duplicada

**Lançamentos de cartão podem vir de 2 lugares:**

1. `CartaoFaturaService::pagarFatura()` → Pagamento completo da fatura
2. `CartaoFaturaService::pagarParcelas()` → Pagamento parcial de itens
3. `FaturaService::marcarItemComoPago()` → Marcar item individual

Todos criam lançamentos, mas nenhum preserva a data original da compra.

---

## 📊 DADOS EXISTENTES EM PRODUÇÃO

### Lançamentos com cartão_credito_id

```sql
SELECT COUNT(*) FROM lancamentos WHERE cartao_credito_id IS NOT NULL;
-- Retorna: X lançamentos vinculados a cartão
```

**Características:**

- `data` = data do pagamento (não da compra)
- `data_pagamento` = mesma data
- `pago` = true
- Podem ter `lancamento_id` apontando para `faturas_cartao_itens`

### Itens de fatura pagos

```sql
SELECT COUNT(*) FROM faturas_cartao_itens WHERE pago = TRUE;
-- Retorna: Y itens já pagos
```

**Características:**

- Têm `lancamento_id` preenchido
- `data_pagamento` preenchida
- `mes_referencia`/`ano_referencia` podem estar corretos ou não

### Histórico de Comportamento

**Antes da correção (dados antigos):**

- Podem ter `mes_referencia` = mês de vencimento
- Não refletem corretamente a competência

**Após futuras correções:**

- `mes_referencia` = mês da compra ✅
- Lançamentos devem usar `data_compra`, não `now()`

---

## 🎯 ÁREAS AFETADAS PELA REFATORAÇÃO

### 1. Services que criam lançamentos

- ✅ `CartaoFaturaService.php` → pagarFatura(), pagarParcelas()
- ✅ `FaturaService.php` → marcarItemComoPago()
- ✅ `CartaoCreditoLancamentoService.php` → criarLancamentoCartao()

### 2. Controllers

- ✅ `CartoesController.php` → Endpoints de pagamento
- ✅ `FaturasController.php` → CRUD de faturas
- ✅ `LancamentosController.php` → Listagem e criação

### 3. Cálculos e Dashboards

- ✅ `DashboardController.php` → KPIs mensais
- ✅ `FinanceiroController.php` → Resumo financeiro
- ✅ `RelatoriosController.php` → Todos os relatórios

### 4. Gamificação

- ✅ `GamificationService.php` → Conquistas baseadas em lançamentos
- ✅ `AchievementService.php` → "Mês Positivo", etc.

### 5. Frontend

- ✅ Dashboard → Exibição de métricas
- ✅ Relatórios → Gráficos e exportação
- ✅ Faturas → Interface de pagamento

---

## ⚠️ RESTRIÇÕES ABSOLUTAS

### NÃO PODE:

1. ❌ Apagar lançamentos existentes
2. ❌ Alterar `data` de lançamentos já criados sem critério
3. ❌ Quebrar queries existentes sem migration
4. ❌ Perder vínculo entre `lancamento_id` e `faturas_cartao_itens`
5. ❌ Modificar históricos já consolidados
6. ❌ Remover campos sem deprecation

### DEVE:

1. ✅ Preservar dados históricos
2. ✅ Manter backward compatibility durante transição
3. ✅ Criar novos campos opcionais
4. ✅ Usar flags para diferenciar lógica antiga/nova
5. ✅ Permitir rollback seguro
6. ✅ Documentar cada mudança

---

## 📋 PRÓXIMOS PASSOS (ETAPA 2)

1. **Propor estrutura de novos campos:**
   - `mes_competencia` → Mês da despesa real
   - `mes_caixa` → Mês do pagamento (fluxo de caixa)
   - `afeta_competencia` → Se deve contar nas despesas do mês
   - `afeta_caixa` → Se afeta saldo disponível
   - `origem_tipo` → Enum: 'normal', 'cartao_credito', 'parcelamento'

2. **Criar migration NÃO destrutiva:**

   ```sql
   ALTER TABLE lancamentos
   ADD COLUMN mes_competencia DATE NULL AFTER data,
   ADD COLUMN afeta_competencia BOOLEAN DEFAULT TRUE,
   ADD COLUMN afeta_caixa BOOLEAN DEFAULT TRUE,
   ADD COLUMN origem_tipo ENUM('normal', 'cartao', 'parcelamento') DEFAULT 'normal';
   ```

3. **Implementar lógica nova SEM quebrar antiga:**
   - Novos lançamentos usam campos novos
   - Lançamentos antigos continuam funcionando
   - Queries adaptar-se gradualmente

4. **Normalizar dados antigos (opcional):**
   - Script que popula campos novos baseado em `faturas_cartao_itens`
   - Apenas para lançamentos com `cartao_credito_id`

5. **Atualizar dashboard e relatórios:**
   - Opção de visualização: "Competência" vs "Caixa"
   - Filtros para separar

---

## 🔐 CONCLUSÃO DA AUDITORIA

### Estado Atual:

✅ Mapeamento completo realizado  
⚠️ Problema confirmado: Lançamentos de cartão são criados no mês errado  
📊 Dados existentes identificados  
🎯 Áreas de impacto conhecidas

### Risco de Migração:

🟡 **MÉDIO** - Sistema tem dados históricos mas estrutura permite extensão

### Recomendação:

✅ **PROSSEGUIR** com refatoração incremental usando novos campos opcionais

---

**Documento gerado em:** 29/01/2026  
**Autor:** Engenheiro de Software Sênior (via GitHub Copilot)  
**Próximo documento:** `PROPOSTA_MIGRACAO.md`
