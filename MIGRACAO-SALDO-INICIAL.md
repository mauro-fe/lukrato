# Migração: Saldo Inicial para Campo na Tabela

## 📋 Resumo

Migração do armazenamento de saldo inicial de **lançamentos** para um **campo dedicado** na tabela `contas`.

**Data:** 24/12/2025  
**Status:** ✅ Concluído

---

## ❌ Problema Anterior

### Como funcionava:

- Saldo inicial era armazenado como um **lançamento especial** com `eh_saldo_inicial = 1`
- Aparecia na lista de lançamentos (causando confusão)
- Poluía relatórios e estatísticas
- Precisava filtrar `eh_saldo_inicial = 0` em várias queries

### Exemplo de lançamento de saldo inicial:

```sql
INSERT INTO lancamentos (
    user_id, tipo, data, categoria_id, conta_id,
    descricao, valor, eh_saldo_inicial
) VALUES (
    1, 'receita', '2025-01-01', NULL, 11,
    'Saldo inicial da conta Guardado', 2413.87, 1
);
```

---

## ✅ Solução Implementada

### Novo campo na tabela contas:

```sql
ALTER TABLE contas ADD COLUMN saldo_inicial DECIMAL(15,2) DEFAULT 0;
```

### Como funciona agora:

- Saldo inicial armazenado diretamente no campo `contas.saldo_inicial`
- Não cria mais lançamentos fictícios
- Listas e relatórios mostram apenas transações reais
- Cálculo de saldo mais limpo e intuitivo

---

## 🔧 Arquivos Modificados

### 1. Migration

**Arquivo:** `database/migrations/2025_12_24_add_saldo_inicial_to_contas.php`

- ✅ Adiciona campo `saldo_inicial` na tabela `contas`
- ✅ Migra dados dos 10 lançamentos de saldo inicial existentes
- ✅ Mantém rollback funcional

### 2. Model

**Arquivo:** `Application/Models/Conta.php`

- ✅ Adicionado `saldo_inicial` ao `$fillable`
- ✅ Adicionado cast para `float`

### 3. Service Principal

**Arquivo:** `Application/Services/ContaService.php`

**Mudanças:**

- ✅ Método `criarConta()`: Salva saldo diretamente no campo
- ✅ Método `atualizarConta()`: Atualiza campo ao invés de lançamento
- ❌ **REMOVIDO:** `criarSaldoInicial()` - 60 linhas
- ❌ **REMOVIDO:** `atualizarSaldoInicial()` - 60 linhas
- ✅ Método `calcularSaldos()`: Busca do campo ao invés de lançamentos

### 4. Services Auxiliares

**SaldoInicialService** (`Application/Services/SaldoInicialService.php`):

- Reescrito completamente (100 → 60 linhas)
- `createOrUpdate()`: Atualiza campo direto
- `getSaldo()`: Busca do campo
- `delete()`: Seta campo para 0

**ContaBalanceService** (`Application/Services/ContaBalanceService.php`):

- `getInitialBalances()`: Busca de `Conta::pluck('saldo_inicial')`
- Removido filtro `eh_saldo_inicial = 0` de receitas e despesas

### 5. Repositories

**Arquivo:** `Application/Repositories/LancamentoRepository.php`

- Removido parâmetro `$excludeInitialBalance` de `countByMonth()`

---

## 📊 Dados Migrados

### Antes da migração:

```
✅ 10 lançamentos de saldo inicial encontrados
✅ Migrados para campo contas.saldo_inicial
✅ 9 lançamentos deletados após migração
```

### Contas com saldo inicial:

| Conta           | Saldo Inicial |
| --------------- | ------------- |
| Guardado        | R$ 2.413,87   |
| Lucro vendas    | R$ 631,13     |
| Rifa meu amor   | R$ 560,13     |
| Vendas          | R$ 433,66     |
| Vendas pai e vó | R$ 22,23      |
| Passar o mês    | R$ 0,01       |
| Criptomoedas    | R$ 1.100,00   |
| Dolar           | R$ 900,00     |

---

## 🧪 Testes Executados

### Script de teste: `cli/test_saldo_inicial.php`

**Resultados:**

- ✅ Teste 1: Verificar contas existentes com saldo inicial
- ✅ Teste 2: Confirmar ausência de lançamentos de saldo inicial
- ✅ Teste 3: Criar nova conta com saldo inicial (não cria lançamento)
- ✅ Teste 4: Calcular saldos com ContaBalanceService

### Script de limpeza: `cli/cleanup_saldo_inicial.php`

- ✅ Deletados 9 lançamentos de saldo inicial antigos
- ✅ Dados preservados no campo `contas.saldo_inicial`

---

## 🎯 Benefícios

### 1. **UX Melhor**

- ❌ Antes: "Saldo inicial" aparecia na lista de lançamentos
- ✅ Agora: Lista mostra apenas transações reais

### 2. **Relatórios Mais Precisos**

- ❌ Antes: Saldo inicial contava como receita nos gráficos
- ✅ Agora: Estatísticas refletem apenas movimentações reais

### 3. **Código Mais Limpo**

- ❌ Antes: Filtrar `eh_saldo_inicial = 0` em 10+ lugares
- ✅ Agora: Queries diretas sem filtros extras

### 4. **Performance**

- ❌ Antes: JOINs e SUMs complexos para buscar saldo inicial
- ✅ Agora: SELECT direto do campo

### 5. **Semântica Correta**

- ❌ Antes: Saldo inicial fingindo ser transação
- ✅ Agora: Saldo inicial como propriedade da conta

---

## 📝 Queries Antes vs Depois

### Buscar saldo inicial

**Antes:**

```php
$saldosIniciais = Lancamento::where('user_id', $userId)
    ->whereIn('conta_id', $contaIds)
    ->where('eh_saldo_inicial', 1)
    ->selectRaw("
        conta_id,
        SUM(CASE WHEN tipo = 'despesa' THEN -valor ELSE valor END) as total
    ")
    ->groupBy('conta_id')
    ->pluck('total', 'conta_id')
    ->all();
```

**Depois:**

```php
$saldosIniciais = Conta::whereIn('id', $contaIds)
    ->pluck('saldo_inicial', 'id')
    ->all();
```

### Listar lançamentos

**Antes:**

```php
$lancamentos = Lancamento::where('user_id', $userId)
    ->where('eh_transferencia', 0)
    ->where('eh_saldo_inicial', 0) // ⚠️ Filtro necessário
    ->orderBy('data', 'desc')
    ->get();
```

**Depois:**

```php
$lancamentos = Lancamento::where('user_id', $userId)
    ->where('eh_transferencia', 0)
    ->orderBy('data', 'desc')
    ->get();
```

---

## 🔄 Rollback (se necessário)

A migration tem rollback completo:

```bash
# Se precisar voltar atrás
php cli/migrate.php down 2025_12_24_add_saldo_inicial_to_contas
```

**O que o rollback faz:**

1. Recria lançamentos de saldo inicial
2. Remove campo `saldo_inicial` da tabela `contas`
3. Restaura estado anterior

---

## ✅ Checklist de Conclusão

- [x] Migration criada e executada
- [x] Model atualizado (fillable + cast)
- [x] Services atualizados (ContaService, SaldoInicialService, ContaBalanceService)
- [x] Repositories atualizados (LancamentoRepository)
- [x] Dados migrados (10 lançamentos → campo)
- [x] Lançamentos antigos deletados (9 removidos)
- [x] Testes executados (100% sucesso)
- [x] Queries simplificadas (sem filtro eh_saldo_inicial)
- [x] Documentação criada

---

## 🚀 Próximos Passos

### Para o usuário testar:

1. **Criar nova conta com saldo inicial**

   - Ir em Contas → Nova Conta
   - Preencher saldo inicial
   - Verificar que não aparece como lançamento

2. **Verificar lista de lançamentos**

   - Confirmar que "Saldo inicial" não aparece mais
   - Lista deve conter apenas transações reais

3. **Verificar relatórios**

   - Gráficos e estatísticas não devem incluir saldo inicial
   - Apenas receitas e despesas reais

4. **Testar edição de conta**
   - Editar saldo inicial de uma conta existente
   - Verificar que atualiza corretamente

---

## 📞 Suporte

**Scripts úteis:**

- `php cli/test_saldo_inicial.php` - Testar sistema
- `php cli/cleanup_saldo_inicial.php` - Limpar lançamentos antigos (já executado)

**Em caso de problemas:**

1. Verificar logs: `storage/logs/app-YYYY-MM-DD.log`
2. Testar rollback se necessário
3. Reportar issue com detalhes

---

**Migração concluída com sucesso! 🎉**
