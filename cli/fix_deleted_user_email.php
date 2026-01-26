<?php

/**
 * Script para corrigir email de usuário que foi deletado mas o email não foi anonimizado
 * Uso: php cli/fix_deleted_user_email.php <email>
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as DB;

// Verificar argumento
$email = $argv[1] ?? null;

if (!$email) {
    echo "❌ Uso: php cli/fix_deleted_user_email.php <email>\n";
    echo "   Exemplo: php cli/fix_deleted_user_email.php usuario@email.com\n";
    exit(1);
}

echo "🔍 Procurando usuário com email: {$email}\n";

// Buscar usuário incluindo soft-deleted
$user = Usuario::withTrashed()->where('email', $email)->first();

if (!$user) {
    echo "❌ Usuário não encontrado com este email.\n";
    exit(1);
}

echo "✅ Usuário encontrado:\n";
echo "   ID: {$user->id}\n";
echo "   Nome: {$user->nome}\n";
echo "   Email: {$user->email}\n";
echo "   Deleted at: " . ($user->deleted_at ?? 'NULL (não deletado)') . "\n";

if ($user->deleted_at) {
    echo "\n⚠️  Este usuário foi soft-deleted em {$user->deleted_at}\n";
    echo "   O email deveria ter sido anonimizado mas não foi.\n";
}

echo "\n🔄 Deseja anonimizar o email deste usuário? (s/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);

if (trim($line) !== 's' && trim($line) !== 'S') {
    echo "❌ Operação cancelada.\n";
    exit(0);
}

// Anonimizar
$anonymizedEmail = 'deleted_' . time() . '_' . substr(md5((string) $user->id), 0, 8) . '@excluido.local';

echo "📝 Anonimizando email...\n";
echo "   De: {$user->email}\n";
echo "   Para: {$anonymizedEmail}\n";

$user->email = $anonymizedEmail;
$user->nome = 'Usuário Removido';
$user->google_id = null;

// Se não estiver deletado, marcar como deletado também
if (!$user->deleted_at) {
    $user->deleted_at = now();
    echo "   Marcando como deletado: {$user->deleted_at}\n";
}

$user->save();

echo "\n✅ Email anonimizado com sucesso!\n";
echo "   Agora o email {$email} está liberado para novo cadastro.\n";

fclose($handle);
