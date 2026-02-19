<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\Conta;
use Application\Models\Lancamento;

$u = Usuario::find(109);
echo "User ID: " . $u->id . PHP_EOL;
echo "Nome: " . $u->nome . PHP_EOL;
echo "is_admin: " . var_export($u->is_admin, true) . PHP_EOL;
echo "onboarding_completed_at: " . var_export($u->onboarding_completed_at, true) . PHP_EOL;

$temConta = Conta::where('user_id', 109)->exists();
$temLancamento = Lancamento::where('user_id', 109)->exists();

echo "tem_conta: " . var_export($temConta, true) . PHP_EOL;
echo "tem_lancamento: " . var_export($temLancamento, true) . PHP_EOL;

$contaCount = Conta::where('user_id', 109)->count();
$lancCount = Lancamento::where('user_id', 109)->count();
echo "conta_count: " . $contaCount . PHP_EOL;
echo "lancamento_count: " . $lancCount . PHP_EOL;
