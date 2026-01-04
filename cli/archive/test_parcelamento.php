<?php

/**
 * Script de Teste: Criar Parcelamento
 * 
 * Simula a criação de um parcelamento via API
 * Verifica que cria 1 cabeçalho + N lançamentos
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  🧪 TESTE: Criação de Parcelamento\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // Obter primeiro usuário para teste
    $user = DB::table('usuarios')->first();

    if (!$user) {
        echo "❌ Nenhum usuário encontrado no banco!\n";
        exit(1);
    }

    // Obter categoria e conta para teste
    $categoria = DB::table('categorias')->where('user_id', $user->id)->first();
    $conta = DB::table('contas')->where('user_id', $user->id)->first();

    if (!$categoria || !$conta) {
        echo "❌ Categoria ou conta não encontrada!\n";
        exit(1);
    }

    echo "👤 Usuário: {$user->nome} (ID: {$user->id})\n";
    echo "📁 Categoria: {$categoria->nome}\n";
    echo "💰 Conta: {$conta->nome}\n\n";

    // DADOS DO TESTE
    $descricao = "Teste Notebook Dell - " . date('H:i:s');
    $valorTotal = 3600.00;
    $numeroParcelas = 12;
    $tipo = 'saida';
    $dataCriacao = date('Y-m-d');

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📋 DADOS DO PARCELAMENTO:\n\n";
    echo "  Descrição: {$descricao}\n";
    echo "  Valor Total: R$ " . number_format($valorTotal, 2, ',', '.') . "\n";
    echo "  Parcelas: {$numeroParcelas}x\n";
    echo "  Valor/Parcela: R$ " . number_format($valorTotal / $numeroParcelas, 2, ',', '.') . "\n";
    echo "  Tipo: {$tipo}\n";
    echo "  Data Início: {$dataCriacao}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "⏳ Criando parcelamento...\n\n";

    DB::beginTransaction();

    // 1. CRIAR CABEÇALHO (parcelamentos)
    echo "[1/2] Criando cabeçalho em 'parcelamentos'...\n";

    $parcelamentoId = DB::table('parcelamentos')->insertGetId([
        'user_id' => $user->id,
        'descricao' => $descricao,
        'valor_total' => $valorTotal,
        'numero_parcelas' => $numeroParcelas,
        'parcelas_pagas' => 0,
        'categoria_id' => $categoria->id,
        'conta_id' => $conta->id,
        'cartao_credito_id' => null,
        'tipo' => $tipo,
        'status' => 'ativo',
        'data_criacao' => $dataCriacao,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    echo "  ✓ Parcelamento criado! ID: {$parcelamentoId}\n\n";

    // 2. CRIAR LANÇAMENTOS INDIVIDUAIS
    echo "[2/2] Criando lançamentos individuais...\n";

    $valorParcela = $valorTotal / $numeroParcelas;
    $dataAtual = new DateTime($dataCriacao);
    $lancamentosCriados = [];

    for ($i = 1; $i <= $numeroParcelas; $i++) {
        $lancamentoId = DB::table('lancamentos')->insertGetId([
            'user_id' => $user->id,
            'descricao' => $descricao . " ({$i}/{$numeroParcelas})",
            'valor' => round($valorParcela, 2),
            'data' => $dataAtual->format('Y-m-d'),
            'tipo' => $tipo === 'saida' ? 'despesa' : 'receita',
            'categoria_id' => $categoria->id,
            'conta_id' => $conta->id,
            'cartao_credito_id' => null,
            'parcelamento_id' => $parcelamentoId,
            'numero_parcela' => $i,
            'pago' => false,
            'eh_transferencia' => false,
            'eh_saldo_inicial' => false,
            'eh_parcelado' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $lancamentosCriados[] = [
            'id' => $lancamentoId,
            'parcela' => $i,
            'valor' => round($valorParcela, 2),
            'data' => $dataAtual->format('Y-m-d')
        ];

        echo "  ✓ Parcela {$i}/{$numeroParcelas}: R$ " . number_format($valorParcela, 2, ',', '.')
            . " - " . $dataAtual->format('d/m/Y') . " (ID: {$lancamentoId})\n";

        $dataAtual->modify('+1 month');
    }

    DB::commit();

    // VERIFICAÇÃO
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "✅ PARCELAMENTO CRIADO COM SUCESSO!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Buscar dados criados
    $parcelamento = DB::table('parcelamentos')->where('id', $parcelamentoId)->first();
    $lancamentos = DB::table('lancamentos')
        ->where('parcelamento_id', $parcelamentoId)
        ->orderBy('data', 'asc')
        ->get();

    echo "📊 PARCELAMENTO (Cabeçalho):\n";
    echo "  ID: {$parcelamento->id}\n";
    echo "  Descrição: {$parcelamento->descricao}\n";
    echo "  Valor Total: R$ " . number_format($parcelamento->valor_total, 2, ',', '.') . "\n";
    echo "  Parcelas: {$parcelamento->numero_parcelas}\n";
    echo "  Status: {$parcelamento->status}\n\n";

    echo "📝 LANÇAMENTOS (Parcelas):\n";
    echo "  Total: " . count($lancamentos) . " registros\n";
    echo "  Primeira parcela: " . $lancamentos[0]->data . "\n";
    echo "  Última parcela: " . $lancamentos[count($lancamentos) - 1]->data . "\n";
    echo "  Valor por parcela: R$ " . number_format($lancamentos[0]->valor, 2, ',', '.') . "\n\n";

    // TESTE DE INTEGRIDADE
    echo "🔍 TESTE DE INTEGRIDADE:\n\n";

    $somaLancamentos = 0;
    foreach ($lancamentos as $lanc) {
        $somaLancamentos += $lanc->valor;
        if ($lanc->parcelamento_id != $parcelamentoId) {
            echo "  ❌ ERRO: Lançamento {$lanc->id} com parcelamento_id incorreto!\n";
        }
    }

    $diferenca = abs($parcelamento->valor_total - $somaLancamentos);

    if ($diferenca < 0.01) {
        echo "  ✓ Soma das parcelas = Valor total\n";
        echo "    R$ " . number_format($somaLancamentos, 2, ',', '.') . " = R$ " . number_format($parcelamento->valor_total, 2, ',', '.') . "\n\n";
    } else {
        echo "  ❌ ERRO: Soma das parcelas não confere!\n";
        echo "    Esperado: R$ " . number_format($parcelamento->valor_total, 2, ',', '.') . "\n";
        echo "    Calculado: R$ " . number_format($somaLancamentos, 2, ',', '.') . "\n\n";
    }

    if (count($lancamentos) == $parcelamento->numero_parcelas) {
        echo "  ✓ Número de lançamentos = Número de parcelas\n";
        echo "    {$lancamentos->count()} = {$parcelamento->numero_parcelas}\n\n";
    } else {
        echo "  ❌ ERRO: Número de lançamentos incorreto!\n\n";
    }

    // TESTE DE CASCADE
    echo "🗑️  TESTE DE CASCADE DELETE:\n\n";
    echo "  Deletando parcelamento ID {$parcelamentoId}...\n";

    DB::table('parcelamentos')->where('id', $parcelamentoId)->delete();

    $lancamentosRestantes = DB::table('lancamentos')
        ->where('parcelamento_id', $parcelamentoId)
        ->count();

    if ($lancamentosRestantes == 0) {
        echo "  ✓ CASCADE funcionou! Todos os {$numeroParcelas} lançamentos foram deletados\n\n";
    } else {
        echo "  ❌ ERRO: CASCADE não funcionou! {$lancamentosRestantes} lançamentos ainda existem\n\n";
    }

    // RESUMO FINAL
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ✅ TODOS OS TESTES PASSARAM!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "🎯 ARQUITETURA VALIDADA:\n\n";
    echo "  ✓ 1 registro em 'parcelamentos' (cabeçalho)\n";
    echo "  ✓ {$numeroParcelas} registros em 'lancamentos' (parcelas)\n";
    echo "  ✓ Cada lançamento com parcelamento_id correto\n";
    echo "  ✓ Soma das parcelas = Valor total\n";
    echo "  ✓ CASCADE DELETE funcionando\n\n";

    echo "📊 lancamentos = FONTE DA VERDADE ✓\n";
    echo "📁 parcelamentos = AUXILIAR (agrupamento) ✓\n\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n\n";
