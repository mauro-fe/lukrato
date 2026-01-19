<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\AssinaturaUsuario;
use Application\Models\Usuario;

// Buscar todos os usuários com email contendo "teste"
$usuarios = Usuario::where('email', 'like', '%teste%')->get();

echo "=== Usuários de teste encontrados ===\n";
foreach ($usuarios as $u) {
    echo "ID: {$u->id} | Email: {$u->email} | Nome: {$u->nome}\n";
}
echo "\n";

// Pegar o último usuário teste
$teste2 = $usuarios->last();

if (!$teste2) {
    echo "Nenhum usuário teste encontrado\n";
    exit(1);
}

echo "Processando usuário: {$teste2->nome} (ID: {$teste2->id}, Email: {$teste2->email})\n";

$assinatura = AssinaturaUsuario::where('user_id', $teste2->id)
    ->where('gateway', 'admin')
    ->latest('id')
    ->first();

if ($assinatura) {
    echo "Assinatura encontrada - ID: {$assinatura->id}\n";
    echo "Plano atual: {$assinatura->plano_id}\n";
    echo "Status: {$assinatura->status}\n";
    echo "Renova em: {$assinatura->renova_em}\n";

    // Atualizar para plano PRO
    $assinatura->plano_id = 2;
    $assinatura->save();

    echo "\n✅ Assinatura atualizada para plano_id=2 (PRO)\n";

    // Verificar se o usuário agora é PRO
    $teste2 = Usuario::find($teste2->id); // Recarregar
    echo "\n🔍 isPro(): " . ($teste2->isPro() ? 'SIM' : 'NÃO') . "\n";
} else {
    echo "Nenhuma assinatura admin encontrada\n";

    // Mostrar todas as assinaturas
    $todas = AssinaturaUsuario::where('user_id', $teste2->id)->get();
    echo "\nAssinaturas encontradas: {$todas->count()}\n";
    foreach ($todas as $a) {
        echo "  - ID: {$a->id} | Plano: {$a->plano_id} | Gateway: {$a->gateway} | Status: {$a->status}\n";
    }
}
