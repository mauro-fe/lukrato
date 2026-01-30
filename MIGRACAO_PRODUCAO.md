# 🚀 GUIA DE MIGRAÇÃO PARA PRODUÇÃO

# Sistema: Lukrato - Refatoração Cartão de Crédito

# Data: 29/01/2026

## ⚠️ IMPORTANTE: FAZER BACKUP ANTES DE COMEÇAR

```sql
-- Backup da tabela lancamentos
CREATE TABLE lancamentos_backup_20260129 AS SELECT * FROM lancamentos;

-- Backup da tabela faturas_cartao_itens
CREATE TABLE faturas_cartao_itens_backup_20260129 AS SELECT * FROM faturas_cartao_itens;
```

---

## 📋 PASSO 1: Adicionar novas colunas na tabela lancamentos

```sql
-- Adicionar coluna data_competencia
ALTER TABLE `lancamentos`
ADD COLUMN `data_competencia` DATE NULL AFTER `data`;

-- Adicionar coluna afeta_competencia
ALTER TABLE `lancamentos`
ADD COLUMN `afeta_competencia` TINYINT(1) NOT NULL DEFAULT 1 AFTER `data_competencia`;

-- Adicionar coluna afeta_caixa
ALTER TABLE `lancamentos`
ADD COLUMN `afeta_caixa` TINYINT(1) NOT NULL DEFAULT 1 AFTER `afeta_competencia`;

-- Adicionar coluna origem_tipo
ALTER TABLE `lancamentos`
ADD COLUMN `origem_tipo` VARCHAR(50) NULL AFTER `afeta_caixa`;

-- Adicionar índices para performance
ALTER TABLE `lancamentos`
ADD INDEX `idx_data_competencia` (`data_competencia`),
ADD INDEX `idx_afeta_competencia` (`afeta_competencia`),
ADD INDEX `idx_afeta_caixa` (`afeta_caixa`),
ADD INDEX `idx_origem_tipo` (`origem_tipo`);
```

**✅ Verificar:** Execute `SHOW COLUMNS FROM lancamentos` e confirme que as 4 novas colunas existem.

---

## 📋 PASSO 2: Verificar se lancamento_id existe em faturas_cartao_itens

```sql
-- Verificar estrutura
SHOW COLUMNS FROM faturas_cartao_itens LIKE 'lancamento_id';

-- Se NÃO existir, adicionar:
ALTER TABLE `faturas_cartao_itens`
ADD COLUMN `lancamento_id` BIGINT(20) UNSIGNED NULL AFTER `fatura_id`,
ADD INDEX `idx_lancamento_id` (`lancamento_id`);
```

---

## 📋 PASSO 3: Normalizar dados existentes

### 3.1 - Preencher data_competencia para lançamentos existentes

```sql
-- Para lançamentos SEM cartão de crédito: data_competencia = data
UPDATE lancamentos
SET data_competencia = data
WHERE cartao_credito_id IS NULL
AND data_competencia IS NULL;

-- Para lançamentos COM cartão de crédito: usar data_compra do item de fatura
UPDATE lancamentos l
INNER JOIN faturas_cartao_itens f ON l.id = f.lancamento_id
SET l.data_competencia = f.data_compra
WHERE l.cartao_credito_id IS NOT NULL
AND l.data_competencia IS NULL;

-- Para lançamentos de cartão sem item vinculado: usar a própria data
UPDATE lancamentos
SET data_competencia = data
WHERE cartao_credito_id IS NOT NULL
AND data_competencia IS NULL;
```

### 3.2 - Corrigir flags para lançamentos de cartão

```sql
-- Lançamentos de cartão PENDENTES: afeta_caixa = FALSE
UPDATE lancamentos
SET afeta_caixa = 0,
    afeta_competencia = 1,
    origem_tipo = 'cartao_credito'
WHERE cartao_credito_id IS NOT NULL
AND pago = 0;

-- Lançamentos de cartão PAGOS: afeta_caixa = TRUE
UPDATE lancamentos
SET afeta_caixa = 1,
    afeta_competencia = 1,
    origem_tipo = 'cartao_credito'
WHERE cartao_credito_id IS NOT NULL
AND pago = 1;
```

### 3.3 - Criar lançamentos para itens de fatura órfãos (SEM lancamento_id)

**⚠️ IMPORTANTE:** Este passo é mais complexo e precisa ser feito via script PHP.

Faça upload do arquivo `cli/normalize_cartao_data.php` para o servidor e execute:

```bash
php cli/normalize_cartao_data.php
```

Este script irá:

- Criar lançamentos para itens de fatura pendentes sem lançamento vinculado
- Vincular os itens aos lançamentos criados
- Ignorar itens órfãos (usuários deletados)

---

## 📋 PASSO 4: Corrigir datas de lançamentos parcelados

### 4.1 - Corrigir campo `data` (vencimento)

Faça upload do arquivo `cli/fix_parcelas_data.php` e execute:

```bash
php cli/fix_parcelas_data.php
```

### 4.2 - Corrigir campo `data_competencia` (compra)

Faça upload do arquivo `cli/fix_parcelas_competencia.php` e execute:

```bash
php cli/fix_parcelas_competencia.php
```

---

## 📋 PASSO 5: Atualizar arquivos PHP no servidor

Faça upload dos seguintes arquivos atualizados:

### Services

- `Application/Services/CartaoCreditoLancamentoService.php`
- `Application/Services/CartaoFaturaService.php`
- `Application/Services/FaturaService.php`

### Models

- `Application/Models/Lancamento.php`

### Repositories

- `Application/Repositories/LancamentoRepository.php`

### Controllers

- `Application/Controllers/Api/DashboardController.php`
- `Application/Controllers/Api/FinanceiroController.php`

---

## 📋 PASSO 6: Verificar integridade

Execute o script de teste:

```bash
php cli/test_cartao_flow_refatorado.php
```

**Resultado esperado:**

```
✅ data_competencia existe
✅ afeta_competencia existe
✅ afeta_caixa existe
✅ origem_tipo existe
✅ lancamento_id existe em faturas_cartao_itens
✅ Itens PENDENTES com afeta_caixa=false
✅ Itens PAGOS com afeta_caixa=true
```

---

## 📊 RESUMO DAS MUDANÇAS

| Componente                 | Antes                        | Depois                                                  |
| -------------------------- | ---------------------------- | ------------------------------------------------------- |
| **Compra no cartão**       | Criava apenas item de fatura | Cria item + lançamento pendente (afeta_caixa=false)     |
| **Pagamento de fatura**    | Criava novo lançamento       | Apenas atualiza lançamento existente (afeta_caixa=true) |
| **Lançamentos parcelados** | Todas com mesma data         | Cada parcela tem data de vencimento correto             |
| **Competência**            | Usava `data`                 | Usa `data_competencia` (data da compra)                 |
| **Caixa**                  | Sempre afetava               | Só afeta quando pago (afeta_caixa=true)                 |

---

## ⚠️ ROLLBACK (se necessário)

Se algo der errado, restaurar backup:

```sql
-- Restaurar lancamentos
DROP TABLE lancamentos;
RENAME TABLE lancamentos_backup_20260129 TO lancamentos;

-- Restaurar faturas_cartao_itens
DROP TABLE faturas_cartao_itens;
RENAME TABLE faturas_cartao_itens_backup_20260129 TO faturas_cartao_itens;
```

---

## ✅ CHECKLIST DE EXECUÇÃO

- [ ] 1. Fazer backup das tabelas
- [ ] 2. Adicionar colunas na tabela lancamentos
- [ ] 3. Verificar/adicionar lancamento_id em faturas_cartao_itens
- [ ] 4. Executar UPDATE para normalizar data_competencia
- [ ] 5. Executar UPDATE para corrigir flags de cartão
- [ ] 6. Fazer upload dos scripts PHP
- [ ] 7. Executar normalize_cartao_data.php
- [ ] 8. Executar fix_parcelas_data.php
- [ ] 9. Executar fix_parcelas_competencia.php
- [ ] 10. Fazer upload dos arquivos PHP atualizados
- [ ] 11. Executar test_cartao_flow_refatorado.php
- [ ] 12. Testar criação de novo lançamento no cartão
- [ ] 13. Testar pagamento de fatura
- [ ] 14. Verificar dashboard (visão competência e caixa)

---

## 🎯 ORDEM DE EXECUÇÃO RECOMENDADA

1. **Horário de menor uso** (madrugada ou fim de semana)
2. **Fazer backup completo do banco**
3. **Executar SQLs diretamente no banco** (passos 1 e 2)
4. **Executar SQLs de normalização** (passo 3.1 e 3.2)
5. **Fazer upload dos scripts PHP** (cli/\*.php)
6. **Fazer upload dos arquivos atualizados** (Application/\*)
7. **Executar scripts de normalização** (passo 3.3, 4.1, 4.2)
8. **Testar funcionalidades**
9. **Monitorar por 24-48h**

---

## 📞 EM CASO DE DÚVIDAS

Execute o script de diagnóstico:

```bash
php cli/test_cartao_flow_refatorado.php
```

Ele mostrará o estado atual do sistema e quais correções ainda são necessárias.
