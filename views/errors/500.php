<?php
// No início do arquivo 500.php
if (isset($exception)) {
    error_log("Erro 500: " . $exception->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erro Interno do Servidor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            position: relative;
            z-index: 10;
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
            animation: pulse 2s ease-in-out infinite;
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
            color: #f5576c;
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

        /* Ícone de engrenagem quebrada */
        .gear-container {
            margin: 2rem auto;
            position: relative;
            width: 100px;
            height: 100px;
        }

        .gear {
            position: absolute;
            width: 60px;
            height: 60px;
            animation: rotate-broken 4s ease-in-out infinite;
            opacity: 0.8;
        }

        .gear-1 {
            top: 0;
            left: 0;
        }

        .gear-2 {
            bottom: 0;
            right: 0;
            animation-direction: reverse;
            animation-duration: 3s;
        }

        .gear svg {
            width: 100%;
            height: 100%;
            fill: rgba(255, 255, 255, 0.8);
            filter: drop-shadow(2px 2px 10px rgba(0, 0, 0, 0.3));
        }

        @keyframes rotate-broken {
            0% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(90deg);
            }

            30% {
                transform: rotate(85deg);
            }

            50% {
                transform: rotate(180deg);
            }

            55% {
                transform: rotate(175deg);
            }

            75% {
                transform: rotate(270deg);
            }

            80% {
                transform: rotate(265deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Efeito de falha/glitch */
        .glitch {
            position: relative;
            display: inline-block;
        }

        .glitch::before,
        .glitch::after {
            content: '500';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #fff 30%, rgba(255, 255, 255, 0.5));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .glitch::before {
            animation: glitch-1 0.5s infinite linear alternate-reverse;
            clip: rect(0, 900px, 50px, 0);
        }

        .glitch::after {
            animation: glitch-2 0.5s infinite linear alternate-reverse;
            clip: rect(50px, 900px, 100px, 0);
        }

        @keyframes glitch-1 {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-3px);
            }
        }

        @keyframes glitch-2 {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(3px);
            }
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

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        /* Fundo animado */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .bg-animation span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }

        .bg-animation span:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }

        .bg-animation span:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }

        .bg-animation span:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }

        .bg-animation span:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }

        .bg-animation span:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }

        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }

            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
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
        }
    </style>
</head>

<body>
    <!-- Animação de fundo -->
    <div class="bg-animation">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Conteúdo principal -->
    <div class="container">
        <div class="gear-container">
            <div class="gear gear-1">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z" />
                </svg>
            </div>
            <div class="gear gear-2">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z" />
                </svg>
            </div>
        </div>

        <h1 class="error-code glitch">500</h1>
        <h2 class="error-message">Erro Interno do Servidor</h2>
        <p class="error-description">
            Desculpe! Algo deu errado em nossos servidores.
            Nossa equipe já foi notificada e está trabalhando para resolver o problema.
        </p>
        <div class="buttons">
            <a href="/" class="btn btn-primary">Página Inicial</a>
            <a href="javascript:window.location.reload()" class="btn btn-secondary">Tentar Novamente</a>
        </div>
    </div>
</body>

</html>