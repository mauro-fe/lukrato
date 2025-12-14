<?php

/**
 * Variáveis disponíveis:
 * @var string $nome
 * @var string $email
 * @var string $whatsapp
 * @var string $assunto
 * @var string $mensagem
 */

$escape = fn($v) => htmlspecialchars($v ?? 'Não informado', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>[Contato Lukrato] <?= $escape($assunto) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 24px;
            color: #1f2933;
        }

        .card {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .header {
            background: #111827;
            color: #ffffff;
            padding: 24px 22px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.85;
        }

        .content {
            padding: 22px;
        }

        .row {
            margin-bottom: 16px;
        }

        .label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
        }

        .value {
            font-size: 14px;
            color: #111827;
        }

        .message-box {
            margin-top: 8px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            white-space: pre-wrap;
        }

        .footer {
            background: #f3f4f6;
            padding: 14px 20px;
            font-size: 11px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="header">
            <h1>Nova mensagem de contato</h1>
            <p>Mensagem enviada através do site Lukrato</p>
        </div>

        <div class="content">
            <div class="row">
                <span class="label">Nome</span>
                <span class="value"><?= $escape($nome) ?></span>
            </div>

            <div class="row">
                <span class="label">E-mail</span>
                <span class="value"><?= $escape($email) ?></span>
            </div>

            <div class="row">
                <span class="label">WhatsApp</span>
                <span class="value"><?= $escape($whatsapp) ?></span>
            </div>

            <div class="row">
                <span class="label">Assunto</span>
                <span class="value"><?= $escape($assunto) ?></span>
            </div>

            <div class="row">
                <span class="label">Mensagem</span>
                <div class="message-box">
                    <?= nl2br($escape($mensagem)) ?>
                </div>
            </div>
        </div>

        <div class="footer">
            Este e-mail foi gerado automaticamente pela plataforma Lukrato.
        </div>
    </div>

</body>

</html>