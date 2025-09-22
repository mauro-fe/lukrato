<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada</title>
    <style>
        :root[data-theme="dark"] {
            --color-primary: #e67e22;
            --color-secondary: #3498db;
            --color-success: #2ecc71;
            --color-warning: #f39c12;
            --color-danger: #e74c3c;
            --color-neutral: #95a5a6;

            --color-bg: #092741;
            --color-surface: #1c2c3c;
            --color-surface-muted: #213245;
            --color-btn: #203c53;
            --color-text: #f8f9fa;
            --color-text-muted: #bdc3c7;

            --glass-bg: rgba(255, 255, 255, 0.06);
            --glass-border: rgba(255, 255, 255, 0.12);
            --ring: rgba(230, 126, 34, 0.22);

            --spacing-4: 1rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;

            --radius-2xl: 24px;

            --shadow-xs: 0 1px 4px rgba(0, 0, 0, 0.25);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 10px 20px rgba(0, 0, 0, 0.35);

            --transition-fast: 0.25s;
        }

        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Usa tipografia e cores do DS */
        body {
            font-family: var(--font-primary, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif);

            /* Gradiente de fundo baseado nos tokens */
            background:
                radial-gradient(1200px 800px at 100% 0%, color-mix(in oklab, var(--color-primary) 22%, transparent) 0%, transparent 60%),
                radial-gradient(1200px 800px at 0% 100%, color-mix(in oklab, var(--color-secondary) 22%, transparent) 0%, transparent 60%),
                linear-gradient(135deg,
                    color-mix(in oklab, var(--color-bg), var(--color-primary) 6%) 0%,
                    color-mix(in oklab, var(--color-bg), var(--color-secondary) 6%) 100%);

            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text);
            overflow: hidden;
        }

        /* Container */
        .container {
            text-align: center;
            padding: var(--spacing-8);
            max-width: 640px;
            animation: lk-fade-in 0.8s ease-out;
        }

        /* Código do erro */
        .error-code {
            font-size: clamp(72px, 12vw, 150px);
            font-weight: 900;
            margin: 0;
            background: linear-gradient(45deg,
                    color-mix(in oklab, var(--color-text) 92%, transparent),
                    color-mix(in oklab, var(--color-text) 50%, transparent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(2px 2px 20px color-mix(in oklab, var(--color-text) 25%, transparent));
            animation: lk-float 3s ease-in-out infinite;
        }

        /* Títulos e textos */
        .error-message {
            font-size: clamp(var(--font-size-xl, 1.25rem), 3vw, 2rem);
            margin: var(--spacing-4) 0;
            font-weight: 700;
            text-shadow: 2px 2px 4px color-mix(in oklab, var(--color-text) 30%, transparent);
        }

        .error-description {
            font-size: var(--font-size-base, 1rem);
            margin: var(--spacing-6) 0 var(--spacing-8);
            opacity: 0.9;
            line-height: 1.6;
            color: var(--color-text-muted);
            text-shadow: 1px 1px 2px color-mix(in oklab, var(--color-text) 25%, transparent);
        }

        /* Botões */
        .buttons {
            display: flex;
            gap: var(--spacing-4);
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: var(--spacing-4) var(--spacing-6);
            text-decoration: none;
            border-radius: var(--radius-2xl);
            font-weight: 600;
            transition: transform var(--transition-fast) ease, box-shadow var(--transition-fast) ease, background var(--transition-fast) ease, color var(--transition-fast) ease, border-color var(--transition-fast) ease;
            font-size: var(--font-size-base, 1rem);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Primário */
        .btn-primary {
            background: color-mix(in oklab, var(--color-surface) 55%, transparent);
            color: var(--color-text);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: var(--color-primary);
            color: var(--color-surface);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: transparent;
        }

        /* Secundário */
        .btn-secondary {
            background: transparent;
            color: var(--color-text);
            border: 1px solid color-mix(in oklab, var(--color-text) 40%, transparent);
        }

        .btn-secondary:hover {
            border-color: var(--color-text);
            transform: translateY(-2px);
        }

        /* Decorações */
        .decoration {
            position: fixed;
            opacity: 0.08;
            font-size: clamp(120px, 30vw, 300px);
            font-weight: 900;
            color: var(--color-text);
            user-select: none;
            pointer-events: none;
        }

        .decoration-1 {
            top: -100px;
            left: -100px;
            transform: rotate(-15deg);
        }

        .decoration-2 {
            bottom: -100px;
            right: -100px;
            transform: rotate(15deg);
        }

        /* Partículas */
        .particles {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: color-mix(in oklab, var(--color-surface-muted) 50%, transparent);
            border-radius: 50%;
            animation: lk-particle-float 15s infinite linear;
            box-shadow: var(--shadow-xs);
        }

        /* Animações */
        @keyframes lk-fade-in {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes lk-float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes lk-particle-float {
            from {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            to {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .error-message {
                font-size: var(--font-size-xl, 1.25rem);
            }

            .error-description {
                font-size: var(--font-size-base, 1rem);
            }

            .decoration {
                font-size: 200px;
            }
        }
    </style>
</head>

<body>
    <!-- Partículas de fundo -->
    <div class="particles">
        <?php for ($i = 0; $i < 20; $i++): ?>
            <div class="particle" style="
                left: <?= rand(0, 100) ?>%;
                animation-delay: <?= rand(0, 15) ?>s;
                animation-duration: <?= rand(10, 20) ?>s;"></div>
        <?php endfor; ?>
    </div>

    <!-- Decorações de fundo -->
    <div class="decoration decoration-1">404</div>
    <div class="decoration decoration-2">404</div>

    <!-- Conteúdo principal -->
    <div class="container">
        <h1 class="error-code">404</h1>
        <h2 class="error-message">Ops! Página não encontrada</h2>
        <p class="error-description">
            A página que você está procurando pode ter sido removida,
            teve seu nome alterado ou está temporariamente indisponível.
        </p>
        <div class="buttons">
            <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary">Voltar ao Início</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</body>

</html>