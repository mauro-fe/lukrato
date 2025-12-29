# API de Parcelamentos - Documentação

## 📋 Visão Geral

A nova API de parcelamentos separa a lógica de parcelamentos da tabela de lançamentos, criando uma estrutura dedicada para gerenciar compras/receitas parceladas.

## 🔄 Diferença entre Lançamentos Antigos e Novos

### ❌ **Estrutura Antiga (Deprecada)**

- Parcelamentos misturados com lançamentos simples
- Campos: `eh_parcelado`, `parcela_atual`, `total_parcelas`, `lancamento_pai_id`
- Difícil de gerenciar e consultar

### ✅ **Nova Estrutura (Recomendada)**

- Tabela `parcelamentos` separada
- Cada parcela é um lançamento ligado ao parcelamento via `parcelamento_id`
- Fácil de consultar, cancelar e gerenciar

---

## 🚀 Endpoints da API

### 1. **Listar Parcelamentos**

```http
GET /api/parcelamentos
```

**Query Params:**

- `status` (opcional): `ativo`, `cancelado`, `concluido`

**Resposta:**

```json
{
  "success": true,
  "data": {
    "parcelamentos": [
      {
        "id": 1,
        "descricao": "Notebook Dell",
        "valor_total": 3600.0,
        "valor_parcela": 300.0,
        "numero_parcelas": 12,
        "parcelas_pagas": 3,
        "percentual_pago": 25,
        "valor_restante": 2700.0,
        "tipo": "saida",
        "status": "ativo",
        "data_criacao": "2024-12-01",
        "categoria": {
          "id": 5,
          "nome": "Eletrônicos",
          "icone": "laptop",
          "cor": "#3B82F6"
        },
        "conta": {
          "id": 2,
          "nome": "Cartão Nubank",
          "tipo": "credito"
        }
      }
    ]
  }
}
```

---

### 2. **Buscar Parcelamento Específico**

```http
GET /api/parcelamentos/:id
```

**Resposta:**

```json
{
  "success": true,
  "data": {
    "parcelamento": {
      "id": 1,
      "descricao": "Notebook Dell",
      "valor_total": 3600.00,
      "valor_parcela": 300.00,
      "numero_parcelas": 12,
      "parcelas_pagas": 3,
      "percentual_pago": 25,
      "valor_restante": 2700.00,
      "tipo": "saida",
      "status": "ativo",
      "data_criacao": "2024-12-01",
      "categoria": { ... },
      "conta": { ... },
      "parcelas": [
        {
          "id": 100,
          "numero_parcela": 1,
          "descricao": "Notebook Dell (Parcela 1/12)",
          "valor": 300.00,
          "data": "2024-12-05",
          "pago": true
        },
        {
          "id": 101,
          "numero_parcela": 2,
          "descricao": "Notebook Dell (Parcela 2/12)",
          "valor": 300.00,
          "data": "2025-01-05",
          "pago": true
        },
        ...
      ]
    }
  }
}
```

---

### 3. **Criar Novo Parcelamento**

```http
POST /api/parcelamentos
Content-Type: application/json
```

**Body:**

```json
{
  "descricao": "Geladeira Brastemp",
  "valor_total": 2400.0,
  "numero_parcelas": 8,
  "categoria_id": 3,
  "conta_id": 1,
  "tipo": "saida",
  "data_criacao": "2024-12-26"
}
```

**Campos:**

- `descricao` (obrigatório): Descrição do parcelamento
- `valor_total` (obrigatório): Valor total do parcelamento
- `numero_parcelas` (obrigatório): Número de parcelas (mínimo 2)
- `categoria_id` (obrigatório): ID da categoria
- `conta_id` (obrigatório): ID da conta
- `tipo` (opcional): `entrada` ou `saida` (padrão: `saida`)
- `data_criacao` (opcional): Data da primeira parcela (padrão: hoje)

**Resposta:**

```json
{
  "success": true,
  "message": "Parcelamento criado com 8 parcelas",
  "data": {
    "parcelamento": { ... },
    "total_parcelas": 8
  }
}
```

**Comportamento:**

- Cria automaticamente todos os lançamentos (parcelas)
- Distribui as parcelas mensalmente a partir da data_criacao
- Calcula valor por parcela: `valor_total / numero_parcelas`
- Todas as parcelas começam como não pagas (`pago = false`)

---

### 4. **Cancelar Parcelamento**

```http
DELETE /api/parcelamentos/:id
```

**Resposta:**

```json
{
  "success": true,
  "message": "Parcelamento cancelado com sucesso"
}
```

**Comportamento:**

- Deleta todas as parcelas **não pagas**
- Mantém parcelas já pagas no histórico
- Altera status do parcelamento para `cancelado`

---

### 5. **Marcar Parcela como Paga/Não Paga**

```http
PUT /api/parcelamentos/parcelas/:id/pagar
Content-Type: application/json
```

**Body:**

```json
{
  "pago": true
}
```

**Resposta:**

```json
{
  "success": true,
  "message": "Parcela marcada como paga",
  "data": {
    "lancamento": {
      "id": 100,
      "pago": true
    }
  }
}
```

**Comportamento:**

- Atualiza campo `pago` do lançamento
- Recalcula `parcelas_pagas` do parcelamento
- Se todas as parcelas forem pagas, status muda para `concluido`

---

## 💡 Como Usar no Frontend

### Exemplo: Criar Parcelamento

```javascript
async function criarParcelamento(dados) {
  const response = await fetch("/api/parcelamentos", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({
      descricao: dados.descricao,
      valor_total: dados.valor_total,
      numero_parcelas: dados.numero_parcelas,
      categoria_id: dados.categoria_id,
      conta_id: dados.conta_id,
      tipo: "saida",
      data_criacao: dados.data_criacao,
    }),
  });

  const result = await response.json();
  if (result.success) {
    console.log("Parcelamento criado:", result.data.parcelamento);
  }
}
```

### Exemplo: Marcar Parcela como Paga

```javascript
async function pagarParcela(lancamentoId) {
  const response = await fetch(
    `/api/parcelamentos/parcelas/${lancamentoId}/pagar`,
    {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
          .content,
      },
      body: JSON.stringify({ pago: true }),
    }
  );

  const result = await response.json();
  if (result.success) {
    console.log("Parcela paga com sucesso!");
  }
}
```

---

## 📊 Modelos de Dados

### Parcelamento

```php
$parcelamento->id
$parcelamento->user_id
$parcelamento->descricao
$parcelamento->valor_total
$parcelamento->numero_parcelas
$parcelamento->parcelas_pagas
$parcelamento->categoria_id
$parcelamento->conta_id
$parcelamento->tipo              // 'entrada' ou 'saida'
$parcelamento->status            // 'ativo', 'cancelado', 'concluido'
$parcelamento->data_criacao

// Atributos calculados
$parcelamento->valorParcela      // valor_total / numero_parcelas
$parcelamento->percentualPago    // (parcelas_pagas / numero_parcelas) * 100
$parcelamento->valorRestante     // valor pendente a pagar

// Relacionamentos
$parcelamento->usuario
$parcelamento->categoria
$parcelamento->conta
$parcelamento->lancamentos       // array de parcelas
```

### Lançamento (Parcela)

```php
$lancamento->id
$lancamento->parcelamento_id     // NULL = lançamento simples
$lancamento->numero_parcela      // 1, 2, 3...
$lancamento->descricao
$lancamento->valor
$lancamento->data
$lancamento->pago

// Métodos
$lancamento->isParcela()         // true se parcelamento_id != null
$lancamento->parcelamento        // relacionamento
```

---

## 🔧 Services Disponíveis

### ParcelamentoService

```php
use Application\Services\ParcelamentoService;

$service = new ParcelamentoService();

// Criar parcelamento
$resultado = $service->criar($userId, $dados);

// Listar parcelamentos
$resultado = $service->listar($userId, 'ativo');

// Buscar parcelamento
$resultado = $service->buscar($parcelamentoId, $userId);

// Cancelar parcelamento
$resultado = $service->cancelar($parcelamentoId, $userId);

// Marcar parcela como paga
$resultado = $service->marcarParcelaPaga($lancamentoId, $userId, true);
```

---

## ⚠️ Importante

1. **Compatibilidade:** Os lançamentos antigos com `eh_parcelado = true` ainda funcionam, mas novos parcelamentos devem usar a nova estrutura

2. **Migração:** Para migrar parcelamentos antigos para a nova estrutura, será necessário criar um script de migração

3. **Cartões de Crédito:** O `CartaoCreditoLancamentoService` ainda usa a estrutura antiga. Você pode atualizá-lo posteriormente para usar `ParcelamentoService`

4. **Frontend:** Atualize o frontend para usar os novos endpoints `/api/parcelamentos` ao invés de criar múltiplos lançamentos manualmente

---

## 📈 Benefícios da Nova Estrutura

✅ **Organização:** Separação clara entre lançamentos simples e parcelamentos  
✅ **Consultas:** Fácil buscar todos os parcelamentos ativos  
✅ **Gerenciamento:** Cancelar/editar todas as parcelas de uma vez  
✅ **Estatísticas:** Calcular percentual pago, valor restante, etc  
✅ **Performance:** Queries mais eficientes  
✅ **Manutenção:** Código mais limpo e fácil de manter
