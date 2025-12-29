# 🎯 Guia Completo: Lançamentos com Cartão de Crédito

## 📋 Estrutura Atual

Sua tabela `lancamentos` já possui os campos necessários:

```sql
- cartao_credito_id     → ID do cartão usado (null = não é cartão)
- eh_parcelado          → true/false se é parcelado
- parcela_atual         → Número da parcela (ex: 1, 2, 3...)
- total_parcelas        → Total de parcelas (ex: 12)
- lancamento_pai_id     → ID do lançamento original (parcelas filhas referenciam o pai)
```

## 🎯 Como Funciona

### 1. **Compra À Vista no Cartão**

```
- Usuário cria lançamento de despesa
- Seleciona cartão de crédito
- Não marca como parcelado
- Sistema cria 1 lançamento:
  ✓ cartao_credito_id = 5
  ✓ eh_parcelado = false
  ✓ parcela_atual = null
  ✓ total_parcelas = null
  ✓ valor = R$ 100,00
```

### 2. **Compra Parcelada no Cartão (12x de R$ 50,00)**

```
Sistema cria 13 lançamentos:

LANÇAMENTO PAI (ID 100):
✓ cartao_credito_id = 5
✓ eh_parcelado = true
✓ parcela_atual = null
✓ total_parcelas = 12
✓ valor = R$ 600,00 (valor total)
✓ lancamento_pai_id = null
✓ data = data da compra
✓ observacao = "Compra parcelada - Netflix Premium"

PARCELA 1 (ID 101):
✓ cartao_credito_id = 5
✓ eh_parcelado = true
✓ parcela_atual = 1
✓ total_parcelas = 12
✓ valor = R$ 50,00
✓ lancamento_pai_id = 100
✓ data = data vencimento fatura (ex: 10/01/2025)
✓ descricao = "Netflix Premium (1/12)"

PARCELA 2 (ID 102):
✓ cartao_credito_id = 5
✓ eh_parcelado = true
✓ parcela_atual = 2
✓ total_parcelas = 12
✓ valor = R$ 50,00
✓ lancamento_pai_id = 100
✓ data = 10/02/2025
✓ descricao = "Netflix Premium (2/12)"

... e assim por diante até 12/12
```

## 💡 Regras de Negócio

### ✅ O que fazer:

1. **Data das parcelas** = Data de vencimento do cartão no mês correspondente
2. **Lançamento pai** = Apenas registro histórico (não conta no saldo)
3. **Parcelas** = Contam individualmente no saldo do mês
4. **Cancelamento** = Se cancelar parcela futura, cancela TODAS as seguintes
5. **Limite do cartão** = Atualizado quando parcela vence (não na compra)

### ❌ O que NÃO fazer:

1. Não debitar da conta na data da compra (cartão é "crédito")
2. Não somar o lançamento pai no saldo
3. Não permitir editar valor de parcela individual (edita o pai e recalcula)

## 🔄 Fluxo Recomendado

### **Criação de Lançamento**

```
[Modal de Lançamento]
├─ Tipo: Despesa
├─ Conta: [Dropdown com contas OU cartões]
│   └─ Se selecionar cartão:
│       ├─ Mostrar opção "Parcelar?"
│       ├─ Se sim: Input "Número de parcelas"
│       └─ Calcular automaticamente as datas
├─ Valor: R$ 600,00
├─ Categoria: Streaming
├─ Descrição: Netflix Premium
└─ [Salvar]

Backend:
├─ Se eh_parcelado = true:
│   ├─ Criar lançamento pai (registro histórico)
│   ├─ Calcular valor_parcela = valor_total / total_parcelas
│   └─ Loop criar parcelas:
│       ├─ Calcular data_parcela = dia_vencimento_cartao + X meses
│       ├─ criar_lancamento(parcela_atual, lancamento_pai_id)
│       └─ atualizar_limite_cartao()
└─ Se não parcelado:
    └─ Criar apenas 1 lançamento normal
```

### **Visualização**

```
[Lista de Lançamentos]
├─ Lançamentos normais: Mostrar normal
├─ Lançamento pai: NÃO MOSTRAR (ou mostrar com badge "Histórico")
└─ Parcelas: Mostrar com badge "3/12" e ícone de cartão
```

### **Edição**

```
Editar parcela individual:
├─ Permitir: descrição, categoria, observação
└─ NÃO permitir: valor (teria que editar o pai)

Editar lançamento pai:
├─ Se editar valor:
│   ├─ Recalcular todas as parcelas futuras não pagas
│   └─ Manter pagas com valor original
└─ Se cancelar:
    └─ Cancelar TODAS as parcelas futuras
```

## 🎨 Melhorias Sugeridas

### 1. **Modal de Lançamento Inteligente**

```javascript
// Quando selecionar cartão no dropdown
if (tipo_selecionado === "cartao_credito") {
  mostrar_opcao_parcelamento();

  if (eh_parcelado) {
    calcular_parcelas_automaticamente();
    mostrar_preview_parcelas(); // "12x de R$ 50,00"
  }
}
```

### 2. **Badge Visual na Lista**

```html
<!-- Parcela de cartão -->
<span class="badge badge-credit-card">
  <i class="fas fa-credit-card"></i>
  3/12
</span>

<!-- Compra à vista no cartão -->
<span class="badge badge-credit-card">
  <i class="fas fa-credit-card"></i>
  À vista
</span>
```

### 3. **Filtros Específicos**

```
- Mostrar apenas compras com cartão
- Mostrar apenas parcelamentos
- Agrupar parcelas do mesmo pai
```

### 4. **Fatura do Cartão**

```
[Tela: Fatura Janeiro/2025 - Nubank]
├─ Netflix Premium (3/12)    R$ 50,00
├─ Spotify (2/6)             R$ 25,00
├─ Supermercado             R$ 150,00
├─ ──────────────────────────────
└─ TOTAL DA FATURA:         R$ 225,00

[Botão: Pagar Fatura]
    └─ Cria transferência da conta vinculada
    └─ Marca parcelas como pagas
    └─ Atualiza limite disponível
```

## 📊 Exemplo Prático Completo

### **Cenário: Compra de R$ 1.200,00 em 12x**

```sql
-- Lançamento Pai (não conta no saldo)
INSERT INTO lancamentos (
    user_id, tipo, categoria_id, conta_id, cartao_credito_id,
    valor, data, descricao, eh_parcelado, total_parcelas
) VALUES (
    1, 'despesa', 10, NULL, 5,
    1200.00, '2025-12-26', 'Notebook Dell', true, 12
);

-- Parcelas (cada uma conta no mês correspondente)
INSERT INTO lancamentos (
    user_id, tipo, categoria_id, conta_id, cartao_credito_id,
    valor, data, descricao, eh_parcelado, parcela_atual, total_parcelas, lancamento_pai_id
) VALUES
(1, 'despesa', 10, NULL, 5, 100.00, '2025-01-10', 'Notebook Dell (1/12)', true, 1, 12, 100),
(1, 'despesa', 10, NULL, 5, 100.00, '2025-02-10', 'Notebook Dell (2/12)', true, 2, 12, 100),
(1, 'despesa', 10, NULL, 5, 100.00, '2025-03-10', 'Notebook Dell (3/12)', true, 3, 12, 100),
-- ... até 12/12
```

## 🚀 Próximos Passos

1. ✅ **Estrutura do banco** - JÁ EXISTE
2. ⚠️ **Modal de lançamento** - PRECISA ADICIONAR CAMPO CARTÃO + PARCELAMENTO
3. ⚠️ **Service de criação** - PRECISA LÓGICA DE PARCELAMENTO
4. ⚠️ **Visualização** - PRECISA BADGES E FILTROS
5. ⚠️ **Fatura do cartão** - NOVA FUNCIONALIDADE

---

## 💬 Dúvidas Frequentes

**P: E se eu quiser pagar uma parcela antes?**
R: Você pode marcar como paga manualmente, mas a data permanece para histórico.

**P: Posso cancelar apenas uma parcela específica?**
R: Não. Se cancelar a parcela 5, cancela 5, 6, 7... até 12. Compra foi "devolvida".

**P: O limite volta quando?**
R: Quando você PAGA a fatura (não quando compra). Compra diminui limite, pagamento aumenta.

**P: E juros/IOF?**
R: Adicione como lançamento separado quando pagar a fatura com atraso.
