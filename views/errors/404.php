<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        overflow: hidden;
    }

    .container {
        text-align: center;
        padding: 2rem;
        max-width: 600px;
        animation: fadeIn 0.8s ease-out;
    }

    .error-code {
        font-size: 150px;
        font-weight: 900;
        margin: 0;
        background: linear-gradient(45deg, #fff 30%, rgba(255, 255, 255, 0.5));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(2px 2px 20px rgba(0, 0, 0, 0.3));
        animation: float 3s ease-in-out infinite;
    }

    .error-message {
        font-size: 2rem;
        margin: 1rem 0;
        font-weight: 600;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .error-description {
        font-size: 1.1rem;
        margin: 1.5rem 0 2.5rem;
        opacity: 0.9;
        line-height: 1.6;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    .buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-block;
        padding: 1rem 2rem;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: 2px solid #fff;
        backdrop-filter: blur(10px);
    }

    .btn-primary:hover {
        background: #fff;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .btn-secondary {
        background: transparent;
        color: #fff;
        border: 2px solid rgba(255, 255, 255, 0.5);
    }

    .btn-secondary:hover {
        border-color: #fff;
        transform: translateY(-2px);
    }

    .decoration {
        position: fixed;
        opacity: 0.1;
        font-size: 300px;
        font-weight: 900;
        color: #fff;
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

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-20px);
        }
    }

    /* Animação de partículas */
    .particles {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }

    .particle {
        position: absolute;
        width: 10px;
        height: 10px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        animation: particle-float 15s infinite linear;
    }

    @keyframes particle-float {
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
        .error-code {
            font-size: 100px;
        }

        .error-message {
            font-size: 1.5rem;
        }

        .error-description {
            font-size: 1rem;
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
            <a href="/" class="btn btn-primary">Voltar ao Início</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</body>

</html>