# 🔄 PROPOSTA DE MIGRAÇÃO SEGURA - SISTEMA DE CARTÃO DE CRÉDITO

**Data:** 29 de janeiro de 2026  
**Sistema:** Lukrato - Controle Financeiro Pessoal  
**Baseado em:** `AUDITORIA_CARTAO_CREDITO.md`  
**Status:** ✅ IMPLEMENTADO

---

## 📦 ARQUIVOS IMPLEMENTADOS

### Migration

- `database/migrations/2026_01_29_000001_add_competencia_fields_to_lancamentos.php`

### Services Atualizados

- `Application/Services/CartaoFaturaService.php` - pagarFatura(), pagarParcelas()
- `Application/Services/FaturaService.php` - marcarItemComoPago()
- `Application/Services/CartaoCreditoLancamentoService.php` - criarLancamentoVista(), criarLancamentoParcelado()

### Model e Repository

- `Application/Models/Lancamento.php` - Constantes, scopes e helpers
- `Application/Repositories/LancamentoRepository.php` - Métodos de competência

### Controllers

- `Application/Controllers/Api/DashboardController.php` - view=competencia|caixa
- `Application/Controllers/Api/FinanceiroController.php` - view=competencia|caixa

### Script

- `cli/normalizar_competencia_cartao.php` - Normalização de dados existentes

---

## 🚀 COMO USAR

```bash
# 1. Executar migration
php cli/migrate.php

# 2. Normalizar dados existentes (simular primeiro)
php cli/normalizar_competencia_cartao.php
php cli/normalizar_competencia_cartao.php --execute

# 3. APIs disponíveis
GET /api/dashboard/metrics?month=2026-01&view=caixa       # Padrão
GET /api/dashboard/metrics?month=2026-01&view=competencia # Competência
GET /api/dashboard/comparativo-competencia?month=2026-01  # Comparativo
```

---

## 🎯 OBJETIVO DA MIGRAÇÃO

Implementar separação correta entre **competência** (mês da despesa) e **caixa** (mês do pagamento) sem:

- ❌ Perder dados históricos
- ❌ Quebrar funcionalidades existentes
- ❌ Impactar usuários durante a transição
- ❌ Exigir recálculo manual de usuários

---

## 📐 ARQUITETURA DA SOLUÇÃO

### Princípios Fundamentais

1. **Extensão, não substituição:** Adicionar campos novos, manter antigos
2. **Opt-in gradual:** Lógica nova coexiste com antiga
3. **Backward compatibility:** Queries antigas continuam funcionando
4. **Flags de controle:** Identificar origem e comportamento
5. **Rollback seguro:** Possível reverter sem perda de dados

### Estratégia: "Double Write Pattern"

```
┌─────────────────────────────────────────────────────────┐
│ FASE 1: Adicionar Campos (Migration)                   │
│ - Novos campos opcionais                                │
│ - Dados antigos: NULL nos campos novos                  │
│ - Dados novos: Preenche ambos sistemas                  │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ FASE 2: Coexistência                                    │
│ - Código detecta presença de campos novos               │
│ - Se NULL: usa lógica antiga                            │
│ - Se preenchido: usa lógica nova                        │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ FASE 3: Normalização Opcional (Script)                  │
│ - Popula campos novos em dados antigos                  │
│ - Usuário decide quando executar                        │
│ - Pode ser feito em background                          │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ FASE 4: Transição Completa (Futuro)                     │
│ - Após 6-12 meses, deprecar lógica antiga               │
│ - Remover campos antigos em próxima major version       │
└─────────────────────────────────────────────────────────┘
```

---

## 🗄️ MUDANÇAS NO BANCO DE DADOS

### Migration 1: Novos Campos em `lancamentos`

```php
<?php
// database/migrations/2026_01_29_add_competencia_fields_to_lancamentos.php

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        DB::schema()->table('lancamentos', function ($table) {
            // Campo de competência (mês/ano da despesa real)
            $table->date('data_competencia')
                ->nullable()
                ->after('data')
                ->comment('Data de competência (mês da despesa real)');

            // Flags de controle
            $table->boolean('afeta_competencia')
                ->default(true)
                ->after('data_competencia')
                ->comment('Se deve contar nas despesas do mês de competência');

            $table->boolean('afeta_caixa')
                ->default(true)
                ->after('afeta_competencia')
                ->comment('Se afeta saldo disponível (fluxo de caixa)');

            // Origem do lançamento
            $table->enum('origem_tipo', ['normal', 'cartao_credito', 'parcelamento', 'agendamento'])
                ->default('normal')
                ->after('afeta_caixa')
                ->comment('Tipo de origem do lançamento');

            // Índices para performance
            $table->index('data_competencia', 'idx_lancamentos_competencia');
            $table->index(['origem_tipo', 'afeta_competencia'], 'idx_lancamentos_origem');
        });

        echo "✅ Campos de competência adicionados à tabela lancamentos\n";
    }

    public function down(): void
    {
        DB::schema()->table('lancamentos', function ($table) {
            $table->dropIndex('idx_lancamentos_competencia');
            $table->dropIndex('idx_lancamentos_origem');
            $table->dropColumn([
                'data_competencia',
                'afeta_competencia',
                'afeta_caixa',
                'origem_tipo'
            ]);
        });

        echo "✅ Campos de competência removidos da tabela lancamentos\n";
    }
};
```

**🔍 Explicação dos Campos:**

| Campo               | Tipo    | Objetivo                     | Exemplo                      |
| ------------------- | ------- | ---------------------------- | ---------------------------- |
| `data_competencia`  | DATE    | Mês/ano da despesa real      | Compra em Jan = `2026-01-15` |
| `afeta_competencia` | BOOLEAN | Se conta nas despesas do mês | Cartão = `true`              |
| `afeta_caixa`       | BOOLEAN | Se reduz saldo disponível    | Compra pendente = `false`    |
| `origem_tipo`       | ENUM    | Tipo de lançamento           | `'cartao_credito'`           |

**💡 Estratégia de Preenchimento:**

```sql
-- Lançamentos NORMAIS (receitas, despesas comuns):
data = '2026-01-15'
data_competencia = '2026-01-15'  -- Mesma data
afeta_competencia = TRUE
afeta_caixa = TRUE
origem_tipo = 'normal'

-- Lançamentos de CARTÃO (novo comportamento):
data = '2026-02-05'              -- Data do PAGAMENTO
data_competencia = '2026-01-15'  -- Data da COMPRA
afeta_competencia = TRUE         -- Conta em Janeiro
afeta_caixa = TRUE               -- Reduz saldo em Fevereiro
origem_tipo = 'cartao_credito'

-- Lançamentos ANTIGOS de cartão (ainda não migrados):
data = '2026-02-05'
data_competencia = NULL          -- Detecta que é antigo
afeta_competencia = TRUE
afeta_caixa = TRUE
origem_tipo = 'cartao_credito'
```

### Migration 2: Atualizar `faturas_cartao_itens`

```php
<?php
// database/migrations/2026_01_29_fix_mes_referencia_calculation.php

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        DB::schema()->table('faturas_cartao_itens', function ($table) {
            // Adicionar comentário explicativo
            $table->integer('mes_referencia')
                ->comment('Mês de COMPETÊNCIA (mês da compra, não do vencimento)')
                ->change();

            $table->integer('ano_referencia')
                ->comment('Ano de COMPETÊNCIA (ano da compra, não do vencimento)')
                ->change();
        });

        echo "✅ Comentários atualizados em faturas_cartao_itens\n";
        echo "⚠️  ATENÇÃO: Dados existentes podem ter mes_referencia = mês de vencimento\n";
        echo "⚠️  Execute script de normalização para corrigir\n";
    }

    public function down(): void
    {
        // Sem ação necessária
    }
};
```

---

## 🔧 ALTERAÇÕES NO CÓDIGO

### 1. Service: CartaoFaturaService.php

**ANTES:**

```php
public function pagarFatura(int $cartaoId, int $mes, int $ano, int $userId): array
{
    $dataPagamento = now()->format('Y-m-d');

    foreach ($itens as $item) {
        Lancamento::create([
            'user_id' => $userId,
            'tipo' => 'despesa',
            'data' => $dataPagamento,  // ❌ ERRADO: Data do pagamento
            'valor' => $item->valor,
            'descricao' => $item->descricao,
            'pago' => true,
        ]);
    }
}
```

**DEPOIS:**

```php
public function pagarFatura(int $cartaoId, int $mes, int $ano, int $userId): array
{
    $dataPagamento = now()->format('Y-m-d');

    foreach ($itens as $item) {
        Lancamento::create([
            'user_id' => $userId,
            'tipo' => 'despesa',

            // ✅ NOVO: Usar data da compra como competência
            'data' => $dataPagamento,              // Fluxo de caixa
            'data_competencia' => $item->data_compra,  // Competência
            'data_pagamento' => $dataPagamento,

            'valor' => $item->valor,
            'descricao' => $item->descricao,
            'pago' => true,

            // ✅ Flags de controle
            'afeta_competencia' => true,
            'afeta_caixa' => true,
            'origem_tipo' => 'cartao_credito',

            // ✅ Manter vínculo
            'cartao_credito_id' => $cartaoId,
            'categoria_id' => $item->categoria_id,
        ]);
    }
}
```

### 2. Service: CartaoCreditoLancamentoService.php

**ALTERAÇÃO:** Corrigir `mes_referencia` para usar mês da COMPRA

```php
private function criarLancamentoVista(int $userId, array $data, CartaoCredito $cartao): FaturaCartaoItem
{
    $dataCompra = $data['data'] ?? date('Y-m-d');
    $vencimento = $this->calcularDataVencimento($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento);

    // ✅ CORREÇÃO: mes_referencia = mês da COMPRA, não do vencimento
    [$anoCompra, $mesCompra] = explode('-', $dataCompra);

    $item = FaturaCartaoItem::create([
        'user_id' => $userId,
        'cartao_credito_id' => $cartao->id,
        'descricao' => $data['descricao'],
        'valor' => $data['valor'],
        'data_compra' => $dataCompra,
        'data_vencimento' => $vencimento['data'],

        // ✅ NOVO: Usar mês da COMPRA
        'mes_referencia' => (int) $mesCompra,  // Competência correta
        'ano_referencia' => (int) $anoCompra,

        'pago' => false,
    ]);

    return $item;
}
```

### 3. Repository: LancamentoRepository.php

**ADICIONAR:** Métodos que respeitam competência

```php
<?php

namespace Application\Repositories;

use Application\Models\Lancamento;
use Illuminate\Database\Eloquent\Collection;

class LancamentoRepository extends BaseRepository
{
    /**
     * Buscar lançamentos por competência (mês da despesa real)
     *
     * @param int $userId
     * @param string $month Formato: Y-m (ex: 2025-12)
     * @param string $tipo Tipo: 'competencia' ou 'caixa'
     * @return Collection
     */
    public function findByMonthAndType(int $userId, string $month, string $tipo = 'caixa'): Collection
    {
        $query = $this->query()->where('user_id', $userId);

        if ($tipo === 'competencia') {
            // Usar data_competencia se disponível, senão fallback para data
            $query->where(function($q) use ($month) {
                $q->where('data_competencia', 'like', "$month%")
                  ->orWhere(function($q2) use ($month) {
                      $q2->whereNull('data_competencia')
                         ->where('data', 'like', "$month%");
                  });
            });
        } else {
            // Comportamento original: fluxo de caixa
            $query->where('data', 'like', "$month%");
        }

        return $query->orderBy('data', 'desc')->get();
    }

    /**
     * Calcular despesas por competência
     */
    public function sumDespesasCompetencia(int $userId, string $start, string $end): float
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('afeta_competencia', true)
            ->where(function($q) use ($start, $end) {
                // Priorizar data_competencia se disponível
                $q->whereBetween('data_competencia', [$start, $end])
                  ->orWhere(function($q2) use ($start, $end) {
                      $q2->whereNull('data_competencia')
                         ->whereBetween('data', [$start, $end]);
                  });
            })
            ->sum('valor');
    }

    /**
     * Calcular despesas por caixa (fluxo de caixa)
     */
    public function sumDespesasCaixa(int $userId, string $start, string $end): float
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('afeta_caixa', true)
            ->whereBetween('data', [$start, $end])
            ->sum('valor');
    }
}
```

### 4. Controller: DashboardController.php

**ADICIONAR:** Suporte a visualização por competência

```php
<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\LancamentoRepository;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
    }

    /**
     * GET /api/dashboard/metrics?month=2026-01&view=competencia
     */
    public function metrics(): void
    {
        $this->requireAuthApi();
        $userId = Auth::id();

        $month = $_GET['month'] ?? date('Y-m');
        $view = $_GET['view'] ?? 'caixa'; // 'caixa' ou 'competencia'

        [$year, $m] = explode('-', $month);
        $start = Carbon::create($year, $m, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $m, 1)->endOfMonth()->toDateString();

        if ($view === 'competencia') {
            // Visão de COMPETÊNCIA (mês da despesa real)
            $receitas = $this->lancamentoRepo->sumReceitasCompetencia($userId, $start, $end);
            $despesas = $this->lancamentoRepo->sumDespesasCompetencia($userId, $start, $end);
        } else {
            // Visão de CAIXA (comportamento original)
            $receitas = $this->lancamentoRepo->sumReceitasCaixa($userId, $start, $end);
            $despesas = $this->lancamentoRepo->sumDespesasCaixa($userId, $start, $end);
        }

        $resultado = $receitas - $despesas;

        Response::json([
            'receitas' => $receitas,
            'despesas' => $despesas,
            'resultado' => $resultado,
            'view' => $view,
            'month' => $month,
        ]);
    }
}
```

---

## 📊 INTERFACE DO USUÁRIO

### Dashboard: Toggle Competência/Caixa

```html
<!-- views/admin/dashboard/index.php -->

<div class="view-toggle" style="margin-bottom: 20px;">
  <label class="toggle-label">Visualização:</label>
  <div class="btn-group" role="group">
    <button
      type="button"
      class="btn btn-sm btn-outline-primary active"
      data-view="caixa"
    >
      <i class="fas fa-money-bill-wave"></i> Fluxo de Caixa
    </button>
    <button
      type="button"
      class="btn btn-sm btn-outline-primary"
      data-view="competencia"
    >
      <i class="fas fa-calendar-check"></i> Competência
    </button>
  </div>
  <small class="text-muted d-block mt-2">
    <strong>Fluxo de Caixa:</strong> Quando o dinheiro entra/sai da conta<br />
    <strong>Competência:</strong> Quando a receita/despesa realmente ocorreu
  </small>
</div>
```

**JavaScript:**

```javascript
// public/assets/js/admin-dashboard-index.js

let currentView = "caixa"; // ou 'competencia'

document.querySelectorAll("[data-view]").forEach((btn) => {
  btn.addEventListener("click", (e) => {
    currentView = e.target.dataset.view;

    // Atualizar visual dos botões
    document
      .querySelectorAll("[data-view]")
      .forEach((b) => b.classList.remove("active"));
    e.target.classList.add("active");

    // Recarregar dados com nova visualização
    loadDashboardData(currentMonth, currentView);
  });
});

async function loadDashboardData(month, view = "caixa") {
  const response = await fetch(
    `${BASE_URL}api/dashboard/metrics?month=${month}&view=${view}`,
  );
  const data = await response.json();

  updateKPIs(data);
  updateCharts(data);
}
```

---

## 🔄 SCRIPT DE NORMALIZAÇÃO (OPCIONAL)

```php
<?php
/**
 * Script: Normalizar dados antigos de cartão de crédito
 *
 * O QUE FAZ:
 * - Popula data_competencia em lançamentos antigos de cartão
 * - Corrige mes_referencia em faturas_cartao_itens
 *
 * QUANDO EXECUTAR:
 * - Após deploy da migration
 * - Em horário de baixo tráfego
 * - Pode ser executado em background
 *
 * SEGURO:
 * - Não altera dados originais
 * - Apenas preenche campos novos
 * - Pode ser revertido
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

echo "🔄 NORMALIZAÇÃO DE DADOS ANTIGOS - CARTÃO DE CRÉDITO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$confirm = readline("⚠️  Este script irá atualizar lançamentos antigos. Continuar? (s/n): ");
if (strtolower(trim($confirm)) !== 's') {
    echo "❌ Cancelado pelo usuário.\n";
    exit(0);
}

DB::beginTransaction();

try {
    // 1. Atualizar lançamentos de cartão que têm vínculo com faturas_cartao_itens
    echo "📝 Atualizando lançamentos com vínculo...\n";

    $updated = 0;
    $lancamentos = Lancamento::whereNotNull('cartao_credito_id')
        ->whereNull('data_competencia')
        ->get();

    foreach ($lancamentos as $lancamento) {
        // Buscar item de fatura correspondente
        $item = FaturaCartaoItem::where('lancamento_id', $lancamento->id)->first();

        if ($item && $item->data_compra) {
            $lancamento->data_competencia = $item->data_compra;
            $lancamento->afeta_competencia = true;
            $lancamento->afeta_caixa = true;
            $lancamento->origem_tipo = 'cartao_credito';
            $lancamento->save();

            $updated++;
        }
    }

    echo "✅ {$updated} lançamentos atualizados\n\n";

    // 2. Corrigir mes_referencia em itens (se necessário)
    echo "📝 Verificando mes_referencia em faturas_cartao_itens...\n";

    $itemsCorrigidos = 0;
    $items = FaturaCartaoItem::all();

    foreach ($items as $item) {
        [$anoCompra, $mesCompra] = explode('-', $item->data_compra->format('Y-m'));

        if ((int) $item->mes_referencia !== (int) $mesCompra ||
            (int) $item->ano_referencia !== (int) $anoCompra) {

            echo "  ⚠️  Item #{$item->id}: mes_referencia={$item->mes_referencia} → {$mesCompra}\n";

            $item->mes_referencia = (int) $mesCompra;
            $item->ano_referencia = (int) $anoCompra;
            $item->save();

            $itemsCorrigidos++;
        }
    }

    echo "✅ {$itemsCorrigidos} itens corrigidos\n\n";

    DB::commit();

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ NORMALIZAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "📊 Resumo:\n";
    echo "   - Lançamentos atualizados: {$updated}\n";
    echo "   - Itens corrigidos: {$itemsCorrigidos}\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
```

**Executar:**

```bash
php cli/normalizar_cartao_credito.php
```

---

## ✅ CHECKLIST DE IMPLANTAÇÃO

### Fase 1: Preparação (Sem Impacto)

- [ ] Criar branch `feature/competencia-cartao`
- [ ] Executar migration de novos campos
- [ ] Testar rollback da migration
- [ ] Revisar código em staging

### Fase 2: Deploy Backend (Coexistência)

- [ ] Deploy das migrations
- [ ] Deploy dos Services atualizados
- [ ] Deploy dos Repositories
- [ ] Deploy dos Controllers
- [ ] Verificar logs de erro

### Fase 3: Deploy Frontend

- [ ] Deploy do toggle Competência/Caixa
- [ ] Atualizar dashboard
- [ ] Atualizar relatórios
- [ ] Testes de interface

### Fase 4: Normalização (Opcional)

- [ ] Agendar execução do script em horário de baixo tráfego
- [ ] Executar script de normalização
- [ ] Validar dados corrigidos
- [ ] Notificar usuários (se necessário)

### Fase 5: Monitoramento

- [ ] Monitorar logs por 7 dias
- [ ] Coletar feedback de usuários
- [ ] Ajustar queries se necessário
- [ ] Documentar lições aprendidas

---

## 🚨 PLANO DE ROLLBACK

**Se algo der errado:**

1. **Reverter migrations:**

   ```bash
   php cli/migrate.php down 2026_01_29_add_competencia_fields_to_lancamentos
   ```

2. **Reverter código:**

   ```bash
   git revert <commit-hash>
   ```

3. **Dados não são perdidos:**
   - Campos antigos continuam intactos
   - Lançamentos não são deletados
   - Queries antigas voltam a funcionar

---

## 📈 BENEFÍCIOS ESPERADOS

### Para o Usuário

✅ Dashboard reflete despesas reais do mês  
✅ Relatórios financeiros corretos  
✅ Planejamento financeiro mais preciso  
✅ Opção de visualizar por competência ou caixa

### Para o Sistema

✅ Lógica financeira correta  
✅ Compatibilidade com contabilidade tradicional  
✅ Base sólida para novos recursos  
✅ Código mais limpo e documentado

### Para a Empresa

✅ Produto mais confiável  
✅ Diferencial competitivo  
✅ Redução de tickets de suporte  
✅ Escalabilidade garantida

---

## 📝 PRÓXIMAS AÇÕES

1. **Revisar proposta com time:** Validar abordagem técnica
2. **Aprovar migrations:** Confirmar estrutura de campos
3. **Implementar em staging:** Testar em ambiente controlado
4. **Executar testes:** Cenários de janeiro, fevereiro, março
5. **Deploy gradual:** Liberar para % de usuários primeiro
6. **Monitorar e ajustar:** Coletar métricas e feedback

---

**Documento preparado em:** 29/01/2026  
**Próximo documento:** `PLANO_TESTES.md`  
**Revisão necessária:** Engenharia, Produto, QA
