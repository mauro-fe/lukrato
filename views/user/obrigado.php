<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado - <?= htmlspecialchars($nome_clinica ?? 'Clínica') ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        :root {
            --primary-color: #2c7873;
            --secondary-color: #52de97;
            --accent-color: #f6c23e;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        .thank-you-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }

        .thank-you-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 2rem 2rem;
            text-align: center;
            position: relative;
        }

        .thank-you-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-60px);
            }
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
            position: relative;
            z-index: 1;
        }

        .thank-you-body {
            padding: 2.5rem;
            text-align: center;
        }

        .clinic-name {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .message {
            color: var(--text-dark);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary-color);
        }

        .info-box h5 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .info-box p {
            color: var(--text-light);
            margin: 0;
            font-size: 0.95rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-custom {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary-custom:hover {
            background: transparent;
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 120, 115, 0.3);
        }

        .btn-outline-custom {
            background: transparent;
            border-color: var(--text-light);
            color: var(--text-light);
        }

        .btn-outline-custom:hover {
            background: var(--text-light);
            color: white;
            transform: translateY(-2px);
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.1);
            animation: floatAround 15s infinite ease-in-out;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 20%;
            right: 10%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            bottom: 20%;
            left: 15%;
            animation-delay: 4s;
        }

        .floating-element:nth-child(4) {
            bottom: 10%;
            right: 20%;
            animation-delay: 6s;
        }

        @keyframes floatAround {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            33% {
                transform: translateY(-20px) rotate(120deg);
            }

            66% {
                transform: translateY(10px) rotate(240deg);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        @media (max-width: 768px) {
            .thank-you-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .thank-you-body {
                padding: 2rem 1.5rem;
            }

            .success-icon {
                font-size: 3rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-custom {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Elementos flutuantes de fundo -->
    <div class="floating-elements">
        <i class="fas fa-heart floating-element" style="font-size: 2rem;"></i>
        <i class="fas fa-star floating-element" style="font-size: 1.5rem;"></i>
        <i class="fas fa-check-circle floating-element" style="font-size: 2.5rem;"></i>
        <i class="fas fa-smile floating-element" style="font-size: 2rem;"></i>
    </div>

    <div class="container">
        <div class="thank-you-card animate__animated animate__fadeInUp">
            <!-- Cabeçalho -->
            <div class="thank-you-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="mb-0 animate__animated animate__fadeInDown animate__delay-1s">
                    Obrigado!
                </h1>
                <p class="mb-0 mt-2 animate__animated animate__fadeInUp animate__delay-1s">
                    Suas respostas foram enviadas com sucesso
                </p>
            </div>

            <!-- Corpo -->
            <div class="thank-you-body">
                <div class="clinic-name animate__animated animate__fadeInUp animate__delay-2s">
                    <i class="fas fa-clinic-medical me-2"></i>
                    <?= htmlspecialchars($nome_clinica) ?>
                </div>

                <div class="message animate__animated animate__fadeInUp animate__delay-2s">
                    <?= htmlspecialchars($message ?? 'Recebemos suas informações e em breve entraremos em contato.') ?>
                </div>

                <!-- Caixa de informações -->
                <div class="info-box animate__animated animate__fadeInUp animate__delay-3s">
                    <h5><i class="fas fa-info-circle me-2"></i>Próximos Passos</h5>
                    <p>
                        Nossa equipe irá analisar suas respostas e entrar em contato em breve.
                        Mantenha seus dados de contato atualizados para que possamos falar com você.
                    </p>
                </div>

                <!-- Informações adicionais -->
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center animate__animated animate__fadeInLeft animate__delay-4s">
                            <div class="me-3">
                                <i class="fas fa-shield-alt text-success fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Dados Seguros</h6>
                                <small class="text-muted">Suas informações estão protegidas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center animate__animated animate__fadeInRight animate__delay-4s">
                            <div class="me-3">
                                <i class="fas fa-clock text-primary fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Resposta Rápida</h6>
                                <small class="text-muted">Retorno em até 24h</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de ação -->
                <div class="action-buttons animate__animated animate__fadeInUp animate__delay-5s">
                    <a href="<?= BASE_URL ?>" class="btn-custom btn-primary-custom">
                        <i class="fas fa-home"></i>
                        Página Inicial
                    </a>
                    <a href="tel:<?= htmlspecialchars($telefone ?? '') ?>" class="btn-custom btn-outline-custom">
                        <i class="fas fa-phone"></i>
                        Contato Direto
                    </a>
                </div>

                <!-- Rodapé -->
                <div class="mt-4 pt-3 border-top animate__animated animate__fadeIn animate__delay-6s">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Enviado em <?= date('d/m/Y \à\s H:i') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Confetti effect -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        // Efeito de confetti quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            // Pequeno delay para deixar a animação aparecer primeiro
            setTimeout(() => {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: {
                        y: 0.6
                    },
                    colors: ['#2c7873', '#52de97', '#667eea', '#764ba2']
                });
            }, 1000);

            // Segundo confetti
            setTimeout(() => {
                confetti({
                    particleCount: 50,
                    angle: 60,
                    spread: 55,
                    origin: {
                        x: 0
                    },
                    colors: ['#2c7873', '#52de97']
                });
                confetti({
                    particleCount: 50,
                    angle: 120,
                    spread: 55,
                    origin: {
                        x: 1
                    },
                    colors: ['#667eea', '#764ba2']
                });
            }, 1500);
        });

        // Adiciona interatividade aos botões
        document.querySelectorAll('.btn-custom').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.classList.add('pulse');
            });

            btn.addEventListener('mouseleave', function() {
                this.classList.remove('pulse');
            });
        });

        // Auto-redirect após 30 segundos (opcional)
        let autoRedirectTimer = setTimeout(() => {
            if (confirm('Deseja ser redirecionado para a página inicial?')) {
                window.location.href = '<?= BASE_URL ?>';
            }
        }, 30000);

        // Cancelar auto-redirect se o usuário interagir com a página
        document.addEventListener('click', () => {
            clearTimeout(autoRedirectTimer);
        });

        // Efeito de digitação no título (opcional)
        function typeWriterEffect(element, text, speed = 100) {
            let i = 0;
            element.innerHTML = '';

            function typeWriter() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, speed);
                }
            }
            typeWriter();
        }
    </script>
</body>

</html>