<?php

/**
 * CLI: Configurar webhook do Telegram Bot.
 *
 * Uso:
 *   php cli/setup_telegram_webhook.php --set --url=https://seudominio.com/api/webhook/telegram
 *   php cli/setup_telegram_webhook.php --delete
 *   php cli/setup_telegram_webhook.php --info
 *
 * Env vars necessárias:
 *   TELEGRAM_BOT_TOKEN
 *   TELEGRAM_WEBHOOK_SECRET (opcional, recomendado)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\AI\Telegram\TelegramService;

$telegram = new TelegramService();

if (!$telegram->isConfigured()) {
    echo "❌ TELEGRAM_BOT_TOKEN não configurado.\n";
    exit(1);
}

$action = null;
$url    = '';

foreach ($argv as $arg) {
    if ($arg === '--set')    $action = 'set';
    if ($arg === '--delete') $action = 'delete';
    if ($arg === '--info')   $action = 'info';
    if (str_starts_with($arg, '--url=')) $url = substr($arg, 6);
}

if ($action === null) {
    echo "Uso:\n";
    echo "  php cli/setup_telegram_webhook.php --set --url=https://seudominio.com/api/webhook/telegram\n";
    echo "  php cli/setup_telegram_webhook.php --delete\n";
    echo "  php cli/setup_telegram_webhook.php --info\n";
    exit(0);
}

switch ($action) {
    case 'set':
        if ($url === '') {
            echo "❌ --url é obrigatório para --set\n";
            exit(1);
        }

        $secret = TelegramService::getWebhookSecret();
        echo "📡 Registrando webhook: {$url}\n";

        if ($secret !== '') {
            echo "🔒 Secret token configurado\n";
        }

        $result = $telegram->setWebhook($url, $secret);

        if ($result['ok'] ?? false) {
            echo "✔ Webhook registrado com sucesso!\n";
        } else {
            echo "❌ Falha: " . ($result['description'] ?? 'Erro desconhecido') . "\n";
            exit(1);
        }
        break;

    case 'delete':
        echo "🗑️ Removendo webhook...\n";
        $result = $telegram->deleteWebhook();

        if ($result['ok'] ?? false) {
            echo "✔ Webhook removido.\n";
        } else {
            echo "❌ Falha: " . ($result['description'] ?? 'Erro desconhecido') . "\n";
            exit(1);
        }
        break;

    case 'info':
        echo "🤖 Bot info:\n";
        $me = $telegram->getMe();

        if ($me['ok'] ?? false) {
            $bot = $me['result'] ?? [];
            echo "  Nome: " . ($bot['first_name'] ?? '-') . "\n";
            echo "  Username: @" . ($bot['username'] ?? '-') . "\n";
            echo "  ID: " . ($bot['id'] ?? '-') . "\n";
        } else {
            echo "❌ Falha ao obter info: " . ($me['description'] ?? 'Erro desconhecido') . "\n";
        }
        break;
}
