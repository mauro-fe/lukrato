<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lukrato - Controle suas finanças de forma inteligente e simples">
    <meta name="theme-color" content="#e67e22">
    <title>Lukrato - Links</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/card.css">
    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/vendor/lucide-compat.css">
    <script src="<?= BASE_URL ?>assets/js/lucide.min.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <!-- Header com Logo -->
        <header class="card-header">
            <div class="logo-container">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato Logo" class="logo" onerror="this.style.display='none'">
            </div>
            <h1 class="title">Lukrato</h1>
            <p class="subtitle">Controle financeiro inteligente para sua vida</p>
        </header>

        <!-- Botão Principal (CTA) -->
        <section class="cta-section">
            <a href="<?= BASE_URL ?>" class="btn btn-primary" target="_blank">
                <i data-lucide="rocket"></i>
                <span>Começar Grátis Agora</span>
            </a>
        </section>

        <!-- Links Principais -->
        <section class="links-section">
            <a href="<?= BASE_URL ?>login" class="link-card" target="_blank">
                <div class="link-icon">
                    <i data-lucide="log-in"></i>
                </div>
                <div class="link-content">
                    <h3>Acessar Sistema</h3>
                    <p>Já tem conta? Faça login</p>
                </div>
                <i data-lucide="chevron-right" class="link-arrow"></i>
            </a>

            <a href="<?= BASE_URL ?>#planos" class="link-card" target="_blank">
                <div class="link-icon">
                    <i data-lucide="crown"></i>
                </div>
                <div class="link-content">
                    <h3>Ver Planos Premium</h3>
                    <p>Desbloqueie todos os recursos</p>
                </div>
                <i data-lucide="chevron-right" class="link-arrow"></i>
            </a>

            <a href="<?= BASE_URL ?>#funcionalidades" class="link-card" target="_blank">
                <div class="link-icon">
                    <i data-lucide="line-chart"></i>
                </div>
                <div class="link-content">
                    <h3>Conheça os Recursos</h3>
                    <p>Veja tudo que você pode fazer</p>
                </div>
                <i data-lucide="chevron-right" class="link-arrow"></i>
            </a>

            <a href="https://wa.me/5544999506302?text=Olá,%20vim%20do%20link%20da%20bio!" class="link-card" target="_blank">
                <div class="link-icon whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="link-content">
                    <h3>Suporte WhatsApp</h3>
                    <p>Tire suas dúvidas em tempo real</p>
                </div>
                <i data-lucide="chevron-right" class="link-arrow"></i>
            </a>
        </section>

        <!-- Recursos em Destaque -->
        <section class="features-section">
            <h2 class="section-title">Por que escolher o Lukrato?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i data-lucide="wallet"></i>
                    <h4>Controle Total</h4>
                    <p>Gerencie receitas e despesas</p>
                </div>
                <div class="feature-item">
                    <i data-lucide="pie-chart"></i>
                    <h4>Relatórios</h4>
                    <p>Visualize seus gastos</p>
                </div>
                <div class="feature-item">
                    <i data-lucide="piggy-bank"></i>
                    <h4>Economize</h4>
                    <p>Acompanhe seus objetivos</p>
                </div>
                <div class="feature-item">
                    <i data-lucide="smartphone"></i>
                    <h4>Mobile First</h4>
                    <p>Use em qualquer lugar</p>
                </div>
            </div>
        </section>

        <!-- Redes Sociais -->
        <section class="social-section">
            <h2 class="section-title">Siga-nos nas redes</h2>
            <div class="social-links">
                <a href="https://instagram.com/lukrato.oficial" class="social-btn instagram" target="_blank" rel="noopener noreferrer" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://tiktok.com/@lukrato.oficial" class="social-btn tiktok" target="_blank" rel="noopener noreferrer" title="TikTok">
                    <i class="fab fa-tiktok"></i>
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="card-footer">
            <p>&copy; <?php echo date('Y'); ?> Lukrato. Todos os direitos reservados.</p>
            <div class="footer-links">
                <a href="<?= BASE_URL ?>termos">Termos de Uso</a>
                <span>•</span>
                <a href="<?= BASE_URL ?>privacidade">Privacidade</a>
            </div>
        </footer>
    </div>

    <?= vite_scripts('site/card/index.js') ?>
    <script src="<?= BASE_URL ?>assets/js/lucide-init.js"></script>
</body>

</html>