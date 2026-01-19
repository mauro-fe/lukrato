<?php
// Teste simples de sessão
session_start();

if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = uniqid('sess_', true);
    echo "Sessão criada: " . $_SESSION['test'] . "\n";
} else {
    echo "Sessão já existe: " . $_SESSION['test'] . "\n";
}

echo "ID da sessão: " . session_id() . "\n";
echo "Path de sessão: " . ini_get('session.save_path') . "\n";
echo "Handler: " . ini_get('session.save_handler') . "\n";
