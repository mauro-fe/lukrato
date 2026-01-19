<?php

// Simular chamada à API com sessão ativa

session_start();

// Verificar se tem usuário logado
if (empty($_SESSION['user_id'])) {
    echo "❌ Nenhum usuário na sessão! Simulando login...\n";
    $_SESSION['user_id'] = 31;
    $_SESSION['usuario_logged_in'] = true;
}

echo "✅ User ID na sessão: {$_SESSION['user_id']}\n\n";

// Agora fazer a chamada HTTP real
$url = 'http://localhost/lukrato/public/api/gamification/achievements?month=2026-01';

echo "🌐 Chamando: $url\n\n";

// Criar contexto com cookies da sessão
$sessionId = session_id();
$sessionName = session_name();

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: {$sessionName}={$sessionId}\r\n"
    ]
];

$context = stream_context_create($opts);
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Erro ao fazer requisição!\n";
    print_r($http_response_header);
    exit;
}

echo "📄 Response Headers:\n";
foreach ($http_response_header as $header) {
    echo "  $header\n";
}

echo "\n📦 Response Body:\n";
echo $response;
echo "\n";
