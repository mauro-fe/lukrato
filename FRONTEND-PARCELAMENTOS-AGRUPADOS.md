# Frontend: Agrupamento Visual de Parcelamentos

## 📋 Resumo

Implementado sistema de agrupamento visual de parcelas na página de lançamentos, permitindo que parcelamentos sejam exibidos de forma colapsável e organizada.

## 🎯 Funcionalidades

### 1. Agrupamento Automático

- Detecta lançamentos com `parcelamento_id`
- Agrupa automaticamente por parcelamento
- Exibe resumo visual com:
  - 📦 Ícone identificador
  - Descrição limpa (sem sufixo de parcela)
  - Quantidade e valor das parcelas (ex: "12x de R$ 300")
  - Progresso visual (pagas/total)
  - Percentual de conclusão

### 2. Expansão/Colapso

- Botão com ícone ➡️ (colapsado) ou ⬇️ (expandido)
- Clique expande detalhes das parcelas
- Animação suave de slide-down
- Tabela interna com todas as parcelas:
  - Número da parcela (1/12, 2/12, etc)
  - Data de vencimento
  - Valor individual
  - Status (Pago/Pendente)
  - Ações individuais

### 3. Ações por Parcela

- **Marcar como Pago/Não Pago**: Botão toggle verde/amarelo
- **Editar**: Abre modal de edição do lançamento individual
- Cada parcela é independente

### 4. Ações no Parcelamento

- **Ver Parcelas**: Expande/colapsa detalhes
- **Cancelar Parcelamento**: Deleta o parcelamento inteiro
  - Aviso sobre CASCADE DELETE
  - Confirmação obrigatória
  - Remove todas as parcelas automaticamente

## 🎨 Visualização

### Linha Agrupada (Colapsada)

```
┌────────────────────────────────────────────────────────────────────┐
│ ➡️ 📦 Notebook Dell                                                │
│    12x de R$ 300 · 4/12 pagas (33%)                                │
│ Despesa | Eletrônicos | Nubank | R$ 3,600.00 [████░░░░] | ⋮       │
└────────────────────────────────────────────────────────────────────┘
```

### Linha Expandida

```
┌────────────────────────────────────────────────────────────────────┐
│ ⬇️ 📦 Notebook Dell                                                │
│    12x de R$ 300 · 4/12 pagas (33%)                                │
│ Despesa | Eletrônicos | Nubank | R$ 3,600.00 [████░░░░] | ⋮       │
├────────────────────────────────────────────────────────────────────┤
│ 📋 Parcelas:                                                        │
│ ┌──────────────────────────────────────────────────────────────┐   │
│ │ Parcela │ Data       │ Valor     │ Status      │ Ações      │   │
│ ├─────────┼────────────┼───────────┼─────────────┼────────────┤   │
│ │ 1/12    │ 01/01/2024 │ R$ 300,00 │ ✓ Pago      │ ⚠️ ✏️      │   │
│ │ 2/12    │ 01/02/2024 │ R$ 300,00 │ ✓ Pago      │ ⚠️ ✏️      │   │
│ │ 3/12    │ 01/03/2024 │ R$ 300,00 │ ⏳ Pendente │ ✅ ✏️      │   │
│ │ ...     │ ...        │ ...       │ ...         │ ...        │   │
│ └──────────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────────┘
```

## 📝 Arquivos Modificados

### 1. `public/assets/js/admin-lancamentos-index.js`

#### STATE

```javascript
const STATE = {
  // ...
  lancamentos: [], // Armazena dados originais
};
```

#### ParcelamentoGrouper

```javascript
const ParcelamentoGrouper = {
    // Processa itens para agrupar
    processForTable(items) { ... },

    // Agrupa por parcelamento_id
    agrupar(items) { ... },

    // Toggle expandir/colapsar
    toggle(parcelamentoId) { ... },

    // Toggle pago/não pago de parcela
    async togglePago(lancamentoId, pago) { ... },

    // Deletar parcelamento inteiro
    async deletar(parcelamentoId) { ... },

    // Instala event listeners
    installListeners() { ... }
};
```

#### Modificações em buildColumns()

**Checkbox de Seleção**:

```javascript
cellClick: (e, cell) => {
  const data = cell.getRow().getData();
  // Não permitir seleção de grupos
  if (data._isParcelamentoGroup || Utils.isSaldoInicial(data)) {
    e.preventDefault();
    cell.getRow().deselect();
  }
};
```

**Coluna Descrição**:

```javascript
formatter: (cell) => {
  const data = cell.getRow().getData();

  if (data._isParcelamentoGroup) {
    // Renderizar grupo com ícone, botão toggle e info
    return `...`;
  }

  return cell.getValue() || "-";
};
```

**Coluna Valor**:

```javascript
mutator: (value, data) => {
    // Calcular total se é grupo
    if (data._isParcelamentoGroup && data._parcelas) {
        return data._parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
    }
    return value;
},
formatter: (cell) => {
    const data = cell.getRow().getData();

    if (data._isParcelamentoGroup) {
        // Mostrar total + barra de progresso
        return `...`;
    }

    return `<span class="valor-cell ${tipoClass}">${Utils.fmtMoney(cell.getValue())}</span>`;
}
```

**Coluna Ações**:

```javascript
formatter: (cell) => {
  const data = cell.getRow().getData();

  if (data._isParcelamentoGroup) {
    // Menu dropdown com Ver Parcelas e Cancelar
    return `...`;
  }

  // Botões normais de editar/excluir
  return `...`;
};
```

#### DataManager.load()

```javascript
const items = await API.fetchLancamentos({ ... });

// Armazenar no STATE
STATE.lancamentos = items;

await TableManager.renderRows(items);
```

#### TableManager.renderRows()

```javascript
renderRows: async (items) => {
  const grid = TableManager.ensureTable();
  if (!grid) return;
  await TableManager.waitForTableReady(grid);

  // AGRUPAR PARCELAMENTOS
  const processedItems = Array.isArray(items)
    ? ParcelamentoGrouper.processForTable(items)
    : [];

  grid.setData(processedItems);
  TableManager.updateSelectionInfo();
};
```

#### Inicialização

```javascript
const init = async () => {
  // Instalar sistema de agrupamento
  ParcelamentoGrouper.installInterceptor();
  ParcelamentoGrouper.installListeners();

  // ...
};
```

### 2. `public/assets/css/admin-lancamentos-index.css`

```css
/* Grupos de parcelamento */
.parcelamento-grupo {
  background-color: #f8f9fa !important;
  border-left: 3px solid var(--color-primary, #007bff) !important;
}

.parcelamento-grupo:hover {
  background-color: #e9ecef !important;
}

/* Animação de expansão */
.parcelas-detalhes {
  background-color: #ffffff;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Progress bar customizado */
.progress {
  background-color: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar {
  transition: width 0.3s ease;
}

/* Badges para status */
.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

/* Tema escuro */
[data-theme="dark"] .parcelamento-grupo {
  background-color: #2b3035 !important;
  border-left-color: var(--color-primary, #0d6efd) !important;
}

[data-theme="dark"] .parcelas-detalhes {
  background-color: #1e2125;
}
```

## 🔧 Como Funciona

### 1. Carregamento de Dados

```
API.fetchLancamentos()
    ↓
STATE.lancamentos = items (armazena originais)
    ↓
ParcelamentoGrouper.processForTable(items)
    ↓
{
    simples: [lancamento1, lancamento2, ...],
    agrupados: [
        {
            _isParcelamentoGroup: true,
            id: 'grupo_1',
            descricao: 'Notebook Dell',
            _parcelas: [parcela1, parcela2, ...]
        }
    ]
}
    ↓
Tabulator.setData([...simples, ...agrupados])
```

### 2. Renderização

```
Tabulator itera sobre items
    ↓
Para cada item:
    if (item._isParcelamentoGroup)
        → Renderizar row de grupo (descrição customizada, total, progresso)
    else
        → Renderizar row normal
```

### 3. Interação

```
Usuário clica no botão ➡️
    ↓
ParcelamentoGrouper.toggle(parcelamentoId)
    ↓
Busca parcelas em STATE.lancamentos
    ↓
Cria <tr class="parcelas-detalhes"> com tabela interna
    ↓
Insere após row do grupo
    ↓
Muda ícone para ⬇️
```

### 4. Ações

```
Usuário clica em ✅ (marcar pago)
    ↓
ParcelamentoGrouper.togglePago(lancamentoId, true)
    ↓
PUT /api/lancamentos/:id { pago: true }
    ↓
DataManager.load() → Recarrega tudo
    ↓
Grupo atualiza progresso automaticamente
```

```
Usuário clica em "Cancelar Parcelamento"
    ↓
ParcelamentoGrouper.deletar(parcelamentoId)
    ↓
Confirmação SweetAlert2
    ↓
DELETE /api/parcelamentos/:id
    ↓
CASCADE DELETE remove todas as parcelas
    ↓
DataManager.load() → Recarrega tudo
```

## ✅ Testes Recomendados

1. **Carregar página**: Ver se parcelamentos aparecem agrupados
2. **Expandir grupo**: Clicar no ➡️ e ver detalhes
3. **Marcar parcela como paga**: Verificar se progresso atualiza
4. **Editar parcela**: Abrir modal e editar
5. **Cancelar parcelamento**: Verificar confirmação e CASCADE
6. **Tema escuro**: Verificar estilos
7. **Responsivo**: Testar em mobile

## 🎯 Próximas Melhorias (Opcionais)

- [ ] Adicionar filtro específico para parcelamentos
- [ ] Permitir pagar todas as parcelas de uma vez
- [ ] Mostrar próxima parcela a vencer em destaque
- [ ] Exportar relatório de parcelamentos
- [ ] Gráfico de evolução de parcelamentos

## 📚 Referências

- [Tabulator.js Documentation](http://tabulator.info/)
- [Bootstrap 5 Dropdown](https://getbootstrap.com/docs/5.0/components/dropdowns/)
- [SweetAlert2](https://sweetalert2.github.io/)
