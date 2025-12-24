<?php

session_start();

echo "=== DEBUG CSRF ===\n\n";
echo "Session ID: " . session_id() . "\n";
echo "Tokens na sessão:\n";

if (isset($_SESSION['csrf_tokens'])) {
    foreach ($_SESSION['csrf_tokens'] as $tokenId => $data) {
        echo "  [$tokenId]:\n";
        echo "    Token: " . substr($data['value'], 0, 20) . "...\n";
        echo "    Criado em: " . date('Y-m-d H:i:s', $data['time']) . "\n";
        echo "    Expira em: " . date('Y-m-d H:i:s', $data['time'] + 1200) . "\n";
        echo "    Tempo restante: " . (1200 - (time() - $data['time'])) . " segundos\n";
        echo "    Expirado? " . (time() - $data['time'] > 1200 ? 'SIM' : 'NÃO') . "\n";
    }
} else {
    echo "  Nenhum token encontrado\n";
}

echo "\n=== DADOS DA SESSÃO ===\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'não definido') . "\n";
echo "Todas as chaves: " . implode(', ', array_keys($_SESSION)) . "\n";
