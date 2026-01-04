# ✅ Solução: Desfazer Pagamento de Fatura do Cartão

## 📋 Análise da Estrutura Atual

### Como funciona o pagamento de fatura:

1. **Quando o usuário paga uma fatura** (`CartaoFaturaService::pagarFatura()`):

   - ✅ Marca todas as parcelas do cartão como `pago = true`
   - ✅ Adiciona `data_pagamento` nas parcelas
   - ✅ Devolve o limite ao cartão (`limite_disponivel += total`)
   - ✅ Cria um lançamento de DESPESA na conta com descrição: `"Pagamento Fatura NOME •••• XXXX - MM/YYYY"`
   - ✅ Esse lançamento tem `pago = true` (é o débito na conta)

2. **Quando o usuário desmarca esse lançamento como pago** (já existe no `LancamentoRepository::update()`):
   - ✅ Detecta que é um "Pagamento Fatura" pela descrição
   - ✅ Reduz o limite do cartão (`limite_disponivel -= valor`)
   - ✅ Desmarca todas as parcelas da fatura como não pagas
   - ✅ **DELETA o lançamento de pagamento** (isso faz o saldo voltar)

## 🎯 Problema Identificado

**O sistema JÁ TEM a funcionalidade de reverter!** 🎉

O problema é que ela está "escondida" na página de Lançamentos:

- O usuário paga a fatura no modal do cartão
- Mas para desfazer, precisa:
  1. Ir na página "Lançamentos"
  2. Encontrar o lançamento "Pagamento Fatura..."
  3. Editar e desmarcar como pago
  4. ❌ **Mas espera!** O lançamento já vem com `pago = true` e não tem opção de editar isso facilmente

## 💡 Solução Recomendada

Adicionar uma funcionalidade **direta no modal de fatura do cartão** para:

### Opção 1: Botão "Desfazer Pagamento" (RECOMENDADA)

Quando a fatura já está paga, mostrar:

```
┌─────────────────────────────────────────┐
│ ✅ Fatura Paga em 20/12/2025            │
│                                         │
│ Total Pago: R$ 170,00                   │
│ Parcelas: 2                             │
│                                         │
│ [🔄 Desfazer Pagamento]                 │
└─────────────────────────────────────────┘
```

**Vantagens:**

- ✅ Simples e direto
- ✅ Usuário não precisa ir na página de lançamentos
- ✅ Fica claro que a fatura foi paga
- ✅ Um clique para reverter

### Opção 2: Mostrar histórico de pagamentos na fatura

Adicionar uma seção no modal:

```
📜 Histórico de Pagamentos
────────────────────────────
✅ 20/12/2025 - R$ 170,00 (2 parcelas)
   [🔄 Desfazer]
```

**Vantagens:**

- ✅ Histórico completo (se pagar/desfazer várias vezes)
- ✅ Mais informativo
- ❌ Mais complexo de implementar

### Opção 3: Editar lançamento diretamente (MAIS SIMPLES)

Na página de **Lançamentos**, melhorar a edição para:

- Quando editar um lançamento "Pagamento Fatura", mostrar checkbox "Pago"
- Ao desmarcar, aplicar toda a lógica de reversão (já existe!)

**Vantagens:**

- ✅ Usa a estrutura atual
- ✅ Menos código novo
- ❌ Usuário precisa ir na página de lançamentos

## 🛠️ Implementação Recomendada: Opção 1

### 1. Backend: Criar método no `CartaoFaturaService`

```php
/**
 * Desfazer pagamento de uma fatura
 * Busca o lançamento de pagamento e o "desmarca como pago"
 * Isso triggará a lógica de reversão no LancamentoRepository
 */
public function desfazerPagamentoFatura(int $cartaoId, int $mes, int $ano, int $userId): array
{
    DB::beginTransaction();

    try {
        $cartao = CartaoCredito::where('id', $cartaoId)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Buscar o lançamento de pagamento da fatura
        $descricaoBusca = sprintf(
            'Pagamento Fatura %s •••• %s - %02d/%04d',
            $cartao->nome_cartao,
            $cartao->ultimos_digitos,
            $mes,
            $ano
        );

        $lancamentoPagamento = Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->where('tipo', 'despesa')
            ->where('descricao', 'LIKE', "%{$mes}/{$ano}%")
            ->where('pago', true)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lancamentoPagamento) {
            throw new \Exception('Pagamento não encontrado para esta fatura.');
        }

        // Usar o repository para atualizar (isso triggará a lógica de reversão)
        $repository = new \Application\Repositories\LancamentoRepository();
        $repository->update($lancamentoPagamento->id, ['pago' => false]);

        DB::commit();

        return [
            'success' => true,
            'message' => 'Pagamento desfeito com sucesso! O saldo foi restaurado.',
        ];
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

/**
 * Verificar se a fatura de um mês está paga
 */
public function faturaEstaPaga(int $cartaoId, int $mes, int $ano, int $userId): ?array
{
    $cartao = CartaoCredito::where('id', $cartaoId)
        ->where('user_id', $userId)
        ->first();

    if (!$cartao) {
        return null;
    }

    // Buscar o lançamento de pagamento
    $lancamentoPagamento = Lancamento::where('user_id', $userId)
        ->where('cartao_credito_id', $cartaoId)
        ->where('tipo', 'despesa')
        ->where('descricao', 'LIKE', "%{$mes}/{$ano}%")
        ->where('pago', true)
        ->orderBy('id', 'desc')
        ->first();

    if (!$lancamentoPagamento) {
        return null;
    }

    return [
        'pago' => true,
        'data_pagamento' => $lancamentoPagamento->data_pagamento,
        'valor' => $lancamentoPagamento->valor,
        'lancamento_id' => $lancamentoPagamento->id,
    ];
}
```

### 2. Controller: Adicionar rota

```php
// Application/Controllers/Api/CartoesController.php

public function desfazerPagamentoFatura(int $id): void
{
    try {
        $data = $this->getJsonData();
        $mes = (int) ($data['mes'] ?? 0);
        $ano = (int) ($data['ano'] ?? 0);

        if (!$mes || !$ano) {
            throw new \Exception('Mês e ano são obrigatórios');
        }

        $userId = $this->getUserId();
        $service = new CartaoFaturaService();

        $result = $service->desfazerPagamentoFatura($id, $mes, $ano, $userId);

        $this->jsonResponse($result);
    } catch (\Exception $e) {
        $this->jsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}
```

### 3. Rota

```php
// routes/web.php ou api.php
$router->post('/api/cartoes/{id}/fatura/desfazer-pagamento', 'Api\CartoesController@desfazerPagamentoFatura');
```

### 4. Frontend: Modificar modal de fatura

No `cartoes-manager.js`, modificar `criarConteudoModal()` para verificar se está paga:

```javascript
async verFatura(cartaoId, mes = null, ano = null) {
    // ... código atual ...

    // NOVO: Verificar se fatura está paga
    const statusResponse = await fetch(
        `${this.baseUrl}api/cartoes/${cartaoId}/fatura/status?mes=${mes}&ano=${ano}`,
        {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }
    );

    let statusPagamento = null;
    if (statusResponse.ok) {
        statusPagamento = await statusResponse.json();
    }

    this.mostrarModalFatura(fatura, parcelamentos, statusPagamento, cartaoId, mes, ano);
}

criarConteudoModal(fatura, parcelamentos, statusPagamento, cartaoId, mes, ano) {
    // Se a fatura está paga, mostrar diferente
    if (statusPagamento && statusPagamento.pago) {
        return this.criarModalFaturaPaga(fatura, statusPagamento, cartaoId, mes, ano);
    }

    // ... código atual para fatura não paga ...
}

criarModalFaturaPaga(fatura, status, cartaoId, mes, ano) {
    return `
        <div class="modal-fatura-header">
            <!-- ... header igual ... -->
        </div>

        <div class="modal-fatura-body">
            <div class="fatura-paga-info">
                <div class="status-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>✅ Fatura Paga</h3>
                <p class="data-pagamento">Pago em ${this.formatDate(status.data_pagamento)}</p>

                <div class="pagamento-detalhes">
                    <div class="detalhe-item">
                        <span class="label">Valor Pago:</span>
                        <strong class="valor">${this.formatMoney(status.valor)}</strong>
                    </div>
                    <div class="detalhe-item">
                        <span class="label">Parcelas:</span>
                        <strong>${fatura.parcelas.length}</strong>
                    </div>
                </div>

                <div class="fatura-actions">
                    <button class="btn-desfazer-pagamento"
                            onclick="cartoesManager.desfazerPagamento(${cartaoId}, ${mes}, ${ano})">
                        <i class="fas fa-undo"></i>
                        Desfazer Pagamento
                    </button>
                </div>

                <div class="aviso-reversao">
                    <i class="fas fa-info-circle"></i>
                    <span>Ao desfazer, o saldo será devolvido à conta e as parcelas voltarão a ficar pendentes.</span>
                </div>
            </div>

            <!-- Ainda mostrar lista de parcelas (apenas visualização) -->
            <div class="fatura-parcelas-pagas">
                <h4>📋 Parcelas Pagas</h4>
                ${fatura.parcelas.map(p => `
                    <div class="parcela-item paga">
                        <span class="desc">${this.escapeHtml(p.descricao)}</span>
                        <span class="valor">${this.formatMoney(p.valor)}</span>
                        <span class="status">✅ Paga</span>
                    </div>
                `).join('')}
            </div>
        </div>

        <div class="modal-fatura-footer">
            <button class="btn-fechar-fatura">Fechar</button>
        </div>
    `;
}

async desfazerPagamento(cartaoId, mes, ano) {
    const confirmado = await Swal.fire({
        title: 'Desfazer pagamento?',
        html: `
            <p>Esta ação irá:</p>
            <ul style="text-align: left;">
                <li>✅ Devolver o valor à conta</li>
                <li>✅ Marcar as parcelas como não pagas</li>
                <li>✅ Reduzir o limite disponível do cartão</li>
            </ul>
            <p><strong>Tem certeza?</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, desfazer',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33'
    });

    if (!confirmado.isConfirmed) return;

    try {
        const response = await fetch(
            `${this.baseUrl}api/cartoes/${cartaoId}/fatura/desfazer-pagamento`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ mes, ano })
            }
        );

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Pagamento desfeito!',
                text: data.message,
                timer: 2000
            });

            // Fechar modal e recarregar
            const modal = document.querySelector('.modal-fatura-overlay');
            if (modal) {
                this.fecharModalFatura(modal);
            }

            await this.carregarCartoes();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        await Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: error.message
        });
    }
}
```

### 5. CSS para o modal de fatura paga

```css
/* public/assets/css/cartoes-modern.css */

.fatura-paga-info {
  text-align: center;
  padding: 2rem;
}

.fatura-paga-info .status-icon {
  font-size: 4rem;
  color: #10b981;
  margin-bottom: 1rem;
}

.fatura-paga-info h3 {
  font-size: 1.5rem;
  color: #10b981;
  margin-bottom: 0.5rem;
}

.data-pagamento {
  color: #6b7280;
  margin-bottom: 2rem;
}

.pagamento-detalhes {
  background: #f9fafb;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.detalhe-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.detalhe-item:last-child {
  margin-bottom: 0;
}

.btn-desfazer-pagamento {
  background: #ef4444;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-desfazer-pagamento:hover {
  background: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.aviso-reversao {
  background: #fef3c7;
  border-left: 3px solid #f59e0b;
  padding: 1rem;
  margin-top: 1.5rem;
  border-radius: 4px;
  display: flex;
  align-items: start;
  gap: 0.75rem;
  text-align: left;
}

.aviso-reversao i {
  color: #f59e0b;
  margin-top: 2px;
}

.fatura-parcelas-pagas {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
}

.fatura-parcelas-pagas h4 {
  margin-bottom: 1rem;
  color: #374151;
}

.parcela-item.paga {
  background: #f0fdf4;
  border-left: 3px solid #10b981;
  opacity: 0.8;
}
```

## 🎨 Fluxo Visual do Usuário

### Antes (Fatura não paga):

```
┌──────────────────────────────────┐
│ teste6 •••• 1234                 │
│ Março/2026                       │
├──────────────────────────────────┤
│ Total a Pagar: R$ 170,00         │
│ Vencimento: 10/03/2026           │
│                                  │
│ ☐ LANÇAMENTOS                    │
│ ☑ teste1 (2/5)    R$ 120,00     │
│ ☑ teste9 (2/6)    R$ 50,00      │
│                                  │
│ Total: R$ 170,00                 │
│ [✓ Pagar Parcelas Selecionadas]  │
└──────────────────────────────────┘
```

### Depois (Fatura paga):

```
┌──────────────────────────────────┐
│ teste6 •••• 1234                 │
│ Março/2026                       │
├──────────────────────────────────┤
│        ✅                         │
│    Fatura Paga                   │
│  Pago em 20/12/2025              │
│                                  │
│ Valor Pago: R$ 170,00            │
│ Parcelas: 2                      │
│                                  │
│ [🔄 Desfazer Pagamento]          │
│                                  │
│ ⚠️ Ao desfazer, o saldo será     │
│    devolvido à conta             │
└──────────────────────────────────┘
```

## ✅ Resumo

**Você já tem 90% do código pronto!**

A lógica de reversão está implementada em `LancamentoRepository::update()`.

Só falta:

1. ✅ Método para buscar se fatura está paga
2. ✅ Método para desfazer pagamento (reutiliza a lógica existente)
3. ✅ Botão no modal de fatura
4. ✅ CSS para o estado "paga"

**Quer que eu implemente isso para você?** 🚀
