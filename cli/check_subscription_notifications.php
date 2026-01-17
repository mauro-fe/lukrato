<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\Notificacao;
use Application\Models\AssinaturaUsuario;

echo "=== Verificando Notificações de Assinatura ===" . PHP_EOL . PHP_EOL;

$notificacoes = Notificacao::where('tipo', 'like', '%subscription%')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($notificacoes->isEmpty()) {
    echo "Nenhuma notificação de assinatura encontrada." . PHP_EOL;
} else {
    foreach ($notificacoes as $n) {
        echo "ID: {$n->id}" . PHP_EOL;
        echo "User ID: {$n->user_id}" . PHP_EOL;
        echo "Tipo: {$n->tipo}" . PHP_EOL;
        echo "Título: {$n->titulo}" . PHP_EOL;
        echo "Mensagem: {$n->mensagem}" . PHP_EOL;
        echo "Link: {$n->link}" . PHP_EOL;
        echo "Lida: " . ($n->lida ? 'Sim' : 'Não') . PHP_EOL;
        echo "Criada em: {$n->created_at}" . PHP_EOL;
        echo str_repeat('-', 50) . PHP_EOL;
    }
}

echo PHP_EOL . "=== Status das Assinaturas PRO ===" . PHP_EOL . PHP_EOL;

$assinaturas = AssinaturaUsuario::whereHas('plano', fn($q) => $q->where('code', 'pro'))
    ->with(['usuario', 'plano'])
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($assinaturas as $a) {
    echo "ID: {$a->id}" . PHP_EOL;
    echo "Usuário: " . ($a->usuario?->nome ?? 'N/A') . " (ID: {$a->user_id})" . PHP_EOL;
    echo "Status: {$a->status}" . PHP_EOL;
    echo "Renova em: {$a->renova_em}" . PHP_EOL;
    echo "isPro(): " . ($a->usuario?->isPro() ? 'Sim' : 'Não') . PHP_EOL;
    echo str_repeat('-', 50) . PHP_EOL;
}
