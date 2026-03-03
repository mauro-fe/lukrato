<?php

/**
 * Teste de envio de email via SMTP
 * Uso: php cli/test_email.php [email_destino]
 */

require __DIR__ . '/../bootstrap.php';

use Application\Services\Communication\MailService;

echo "=== Teste de Email SMTP ===\n\n";

// Mostra configuração (sem a senha)
echo "Host:       " . ($_ENV['MAIL_HOST'] ?? 'NÃO DEFINIDO') . "\n";
echo "Port:       " . ($_ENV['MAIL_PORT'] ?? 'NÃO DEFINIDO') . "\n";
echo "Encryption: " . ($_ENV['MAIL_ENCRYPTION'] ?? 'NÃO DEFINIDO') . "\n";
echo "Username:   " . ($_ENV['MAIL_USERNAME'] ?? 'NÃO DEFINIDO') . "\n";
echo "From:       " . ($_ENV['MAIL_FROM'] ?? 'NÃO DEFINIDO') . "\n";
echo "From Name:  " . ($_ENV['MAIL_FROM_NAME'] ?? 'NÃO DEFINIDO') . "\n";
echo "Password:   " . (empty($_ENV['MAIL_PASSWORD']) ? 'NÃO DEFINIDA' : '***definida***') . "\n";
echo "\n";

$mail = new MailService();

if (!$mail->isConfigured()) {
    echo "❌ MailService NÃO está configurado. Verifique as variáveis MAIL_* no .env\n";
    exit(1);
}

echo "✅ MailService configurado!\n\n";

// Email de destino: argumento ou o próprio remetente
$destino = $argv[1] ?? ($_ENV['MAIL_FROM'] ?? $_ENV['MAIL_USERNAME']);

echo "Enviando email de teste para: {$destino}\n";

try {
    $result = $mail->send(
        $destino,
        'Teste Lukrato',
        '✅ Teste de Email - Lukrato',
        '<h2 style="color:#e67e22;">Teste de Email</h2><p>Se você está lendo isso, o envio de emails está funcionando corretamente no localhost! 🎉</p><p><small>Enviado em: ' . date('d/m/Y H:i:s') . '</small></p>',
        'Teste de Email - Se voce esta lendo isso, o envio de emails esta funcionando corretamente no localhost!'
    );

    if ($result) {
        echo "\n✅ Email enviado com SUCESSO!\n";
        echo "Verifique a caixa de entrada de: {$destino}\n";
    } else {
        echo "\n❌ Falha ao enviar (retornou false).\n";
    }
} catch (\Throwable $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
}
