<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lukrato - Em Manutenção</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #fff7ed 50%, #f8fafc 100%);
            color: #1e293b;
            padding: 1.5rem;
        }

        .container {
            text-align: center;
            max-width: 520px;
            width: 100%;
        }

        .icon-wrap {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #e67e22, #f39c12);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 30px rgba(230, 126, 34, 0.25);
            animation: pulse 2.5s ease-in-out infinite;
        }

        .icon-wrap svg {
            width: 48px;
            height: 48px;
            stroke: white;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 12px 30px rgba(230, 126, 34, 0.25);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 16px 40px rgba(230, 126, 34, 0.35);
            }
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }

        .subtitle {
            font-size: 1.05rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .reason {
            background: white;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #e67e22;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            text-align: left;
            color: #334155;
            font-size: 0.9rem;
        }

        .reason strong {
            color: #e67e22;
        }

        .estimate {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            padding: 0.6rem 1.25rem;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 2rem;
        }

        .estimate .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .progress-bar .fill {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, #e67e22, #f39c12);
            border-radius: 4px;
            animation: loading 2s ease-in-out infinite;
        }

        @keyframes loading {
            0% {
                width: 10%;
                margin-left: 0;
            }

            50% {
                width: 40%;
                margin-left: 30%;
            }

            100% {
                width: 10%;
                margin-left: 90%;
            }
        }

        .footer-text {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .footer-text a {
            color: #e67e22;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon-wrap">
            <svg viewBox="0 0 24 24">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
            </svg>
        </div>

        <h1>Estamos melhorando o Lukrato</h1>
        <p class="subtitle">
            O sistema está passando por uma manutenção programada para trazer melhorias e mais estabilidade.
            Voltaremos em breve!
        </p>

        <?php if (!empty($reason)): ?>
            <div class="reason">
                <strong>Motivo:</strong> <?= htmlspecialchars($reason) ?>
            </div>
        <?php endif; ?>

        <?php if ($estimatedMinutes): ?>
            <div class="estimate">
                <span class="dot"></span>
                Previsão de retorno: ~<?= (int) $estimatedMinutes ?> minuto<?= $estimatedMinutes > 1 ? 's' : '' ?>
            </div>
        <?php endif; ?>

        <div class="progress-bar">
            <div class="fill"></div>
        </div>

        <p class="footer-text">
            Lukrato &mdash; Controle Financeiro Pessoal<br>
            Dúvidas? <a href="mailto:contato@lukrato.com">contato@lukrato.com</a>
        </p>
    </div>
</body>

</html>