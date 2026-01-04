<?php

/**
 * Script de Refatoração: Lançamentos como Fonte Única da Verdade
 * 
 * Execute: php cli/refactor_parcelamentos.php
 * 
 * Objetivo: Garantir que `lancamentos` seja a fonte única da verdade
 * e `parcelamentos` seja apenas auxiliar para agrupamento
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  REFATORAÇÃO: Lançamentos como Fonte Única da Verdade\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $schema = DB::schema();

    // 1. AJUSTAR TABELA LANCAMENTOS
    echo "[1/5] Ajustando tabela lancamentos...\n";

    // Adicionar colunas se não existirem
    if (!$schema->hasColumn('lancamentos', 'parcelamento_id')) {
        DB::statement("ALTER TABLE lancamentos ADD COLUMN parcelamento_id INT UNSIGNED NULL AFTER cartao_credito_id");
        echo "  ✓ Coluna parcelamento_id adicionada\n";
    } else {
        echo "  • Coluna parcelamento_id já existe\n";
    }

    if (!$schema->hasColumn('lancamentos', 'numero_parcela')) {
        DB::statement("ALTER TABLE lancamentos ADD COLUMN numero_parcela INT NULL AFTER parcelamento_id");
        echo "  ✓ Coluna numero_parcela adicionada\n";
    } else {
        echo "  • Coluna numero_parcela já existe\n";
    }

    // Adicionar índices
    try {
        DB::statement("ALTER TABLE lancamentos ADD INDEX idx_parcelamento_id (parcelamento_id)");
        echo "  ✓ Índice em parcelamento_id criado\n";
    } catch (Exception $e) {
        echo "  • Índice parcelamento_id já existe\n";
    }

    try {
        DB::statement("ALTER TABLE lancamentos ADD INDEX idx_cartao_credito_id (cartao_credito_id)");
        echo "  ✓ Índice em cartao_credito_id criado\n";
    } catch (Exception $e) {
        echo "  • Índice cartao_credito_id já existe\n";
    }

    // 2. AJUSTAR TABELA PARCELAMENTOS
    echo "\n[2/5] Ajustando tabela parcelamentos...\n";

    if (!$schema->hasColumn('parcelamentos', 'cartao_credito_id')) {
        DB::statement("ALTER TABLE parcelamentos ADD COLUMN cartao_credito_id INT UNSIGNED NULL AFTER conta_id");
        echo "  ✓ Coluna cartao_credito_id adicionada\n";
    } else {
        echo "  • Coluna cartao_credito_id já existe\n";
    }

    if (!$schema->hasColumn('parcelamentos', 'status')) {
        DB::statement("ALTER TABLE parcelamentos ADD COLUMN status ENUM('ativo', 'concluido', 'cancelado') DEFAULT 'ativo' AFTER tipo");
        echo "  ✓ Coluna status adicionada\n";
    } else {
        echo "  • Coluna status já existe\n";
    }

    // 3. CORRIGIR TIPOS DE COLUNAS
    echo "\n[3/5] Corrigindo tipos de colunas (INT UNSIGNED)...\n";

    try {
        DB::statement("ALTER TABLE lancamentos MODIFY COLUMN user_id INT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE lancamentos MODIFY COLUMN categoria_id INT UNSIGNED NULL");
        DB::statement("ALTER TABLE lancamentos MODIFY COLUMN conta_id INT UNSIGNED NULL");
        DB::statement("ALTER TABLE lancamentos MODIFY COLUMN cartao_credito_id INT UNSIGNED NULL");
        DB::statement("ALTER TABLE lancamentos MODIFY COLUMN parcelamento_id INT UNSIGNED NULL");
        echo "  ✓ Tipos corrigidos em lancamentos\n";

        DB::statement("ALTER TABLE parcelamentos MODIFY COLUMN user_id INT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE parcelamentos MODIFY COLUMN categoria_id INT UNSIGNED NULL");
        DB::statement("ALTER TABLE parcelamentos MODIFY COLUMN conta_id INT UNSIGNED NULL");
        DB::statement("ALTER TABLE parcelamentos MODIFY COLUMN cartao_credito_id INT UNSIGNED NULL");
        echo "  ✓ Tipos corrigidos em parcelamentos\n";
    } catch (Exception $e) {
        echo "  ⚠ Aviso ao corrigir tipos: " . $e->getMessage() . "\n";
    }

    // 4. ADICIONAR CHAVES ESTRANGEIRAS
    echo "\n[4/5] Adicionando chaves estrangeiras...\n";

    // Limpar dados inválidos ANTES de criar FKs
    $cleaned = DB::table('lancamentos')
        ->whereNotNull('parcelamento_id')
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('parcelamentos')
                ->whereRaw('parcelamentos.id = lancamentos.parcelamento_id');
        })
        ->update(['parcelamento_id' => null]);

    if ($cleaned > 0) {
        echo "  • {$cleaned} lançamentos com parcelamento_id inválido foram limpos\n";
    }

    try {
        DB::statement("
            ALTER TABLE lancamentos 
            ADD CONSTRAINT fk_lancamentos_parcelamento 
            FOREIGN KEY (parcelamento_id) 
            REFERENCES parcelamentos(id) 
            ON DELETE CASCADE
        ");
        echo "  ✓ FK lancamentos→parcelamentos criada (ON DELETE CASCADE)\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  • FK lancamentos→parcelamentos já existe\n";
        } else {
            echo "  ⚠ Erro ao criar FK lancamentos→parcelamentos: " . $e->getMessage() . "\n";
        }
    }

    try {
        DB::statement("
            ALTER TABLE lancamentos 
            ADD CONSTRAINT fk_lancamentos_cartao_credito 
            FOREIGN KEY (cartao_credito_id) 
            REFERENCES cartoes_credito(id) 
            ON DELETE SET NULL
        ");
        echo "  ✓ FK lancamentos→cartoes_credito criada\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  • FK lancamentos→cartoes_credito já existe\n";
        } else {
            echo "  ⚠ Erro ao criar FK lancamentos→cartoes_credito: " . $e->getMessage() . "\n";
        }
    }

    try {
        DB::statement("
            ALTER TABLE parcelamentos 
            ADD CONSTRAINT fk_parcelamentos_cartao_credito 
            FOREIGN KEY (cartao_credito_id) 
            REFERENCES cartoes_credito(id) 
            ON DELETE SET NULL
        ");
        echo "  ✓ FK parcelamentos→cartoes_credito criada\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  • FK parcelamentos→cartoes_credito já existe\n";
        } else {
            echo "  ⚠ Erro ao criar FK parcelamentos→cartoes_credito: " . $e->getMessage() . "\n";
        }
    }

    // 5. VERIFICAÇÃO FINAL
    echo "\n[5/5] Verificação final...\n";

    $totalLancamentos = DB::table('lancamentos')->count();
    $lancamentosComParcelamento = DB::table('lancamentos')->whereNotNull('parcelamento_id')->count();
    $totalParcelamentos = DB::table('parcelamentos')->count();

    echo "  • Total de lançamentos: {$totalLancamentos}\n";
    echo "  • Lançamentos parcelados: {$lancamentosComParcelamento}\n";
    echo "  • Total de parcelamentos: {$totalParcelamentos}\n";

    // RESUMO FINAL
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ✅ REFATORAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "ARQUITETURA FINANCEIRA:\n\n";
    echo "📊 lancamentos = FONTE DA VERDADE\n";
    echo "   • Contém TODAS as movimentações financeiras\n";
    echo "   • Cada parcela = 1 lançamento individual\n";
    echo "   • Usado para: saldo, relatórios, gráficos, fatura\n\n";

    echo "📁 parcelamentos = AUXILIAR (agrupamento)\n";
    echo "   • Serve apenas para agrupar parcelas visualmente\n";
    echo "   • NÃO usado para cálculos financeiros\n";
    echo "   • Facilita UX (mostrar '3/12' ao invés de 12 linhas)\n\n";

    echo "🔗 RELACIONAMENTO:\n";
    echo "   parcelamentos (1) ←→ (N) lancamentos\n";
    echo "      (cabeçalho)         (parcelas individuais)\n\n";

    echo "⚠️  IMPORTANTE:\n";
    echo "   • Sempre criar lançamentos ao parcelar\n";
    echo "   • Sempre usar lancamentos para cálculos\n";
    echo "   • CASCADE deleta lançamentos ao deletar parcelamento\n\n";
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n\n";
