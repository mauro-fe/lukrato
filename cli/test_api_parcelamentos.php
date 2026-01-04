<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Controllers\Api\ParcelamentosController;
use Application\Lib\Auth;

// Simular autenticação
$_SESSION['user_id'] = 1;
$_SESSION['admin_username'] = 'teste';

echo "=== TESTE: API /api/parcelamentos ===\n\n";

// Criar controller
$controller = new ParcelamentosController();

// Simular requisição GET
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['status'] = 'ativo';

echo "Chamando index()...\n";

// Capturar output
ob_start();
$controller->index();
$output = ob_get_clean();

echo "Resposta:\n";
echo $output;
echo "\n\n";

// Decodificar JSON
$response = json_decode($output, true);

if ($response && isset($response['data']['parcelamentos'])) {
    $count = count($response['data']['parcelamentos']);
    echo "✅ Retornou {$count} parcelamentos\n";

    foreach ($response['data']['parcelamentos'] as $p) {
        echo "\n- {$p['descricao']}\n";
        echo "  ID: {$p['id']}\n";
        echo "  Total: R$ {$p['valor_total']}\n";
        echo "  Parcelas: {$p['parcelas_pagas']}/{$p['numero_parcelas']}\n";
        echo "  Status: {$p['status']}\n";
    }
} else {
    echo "❌ Erro na resposta\n";
}
