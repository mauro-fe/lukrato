<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lukrato - Controle suas finanças de forma inteligente e simples">
    <meta name="theme-color" content="#10b981">
    <title>Lukrato - Links</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/card.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header com Logo -->
        <header class="card-header">
            <div class="logo-container">
                <img src="/assets/img/logo.png" alt="Lukrato Logo" class="logo" onerror="this.style.display='none'">
            </div>
            <h1 class="title">Lukrato</h1>
            <p class="subtitle">Controle financeiro inteligente para sua vida</p>
            <div class="stats">
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <span>+1000 usuários</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-star"></i>
                    <span>4.9★ avaliação</span>
                </div>
            </div>
        </header>

        <!-- Botão Principal (CTA) -->
        <section class="cta-section">
            <a href="/" class="btn btn-primary" target="_blank">
                <i class="fas fa-rocket"></i>
                <span>Começar Grátis Agora</span>
            </a>
        </section>

        <!-- Links Principais -->
        <section class="links-section">
            <a href="/login" class="link-card" target="_blank">
                <div class="link-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="link-content">
                    <h3>Acessar Sistema</h3>
                    <p>Já tem conta? Faça login</p>
                </div>
                <i class="fas fa-chevron-right link-arrow"></i>
            </a>

            <a href="/planos" class="link-card" target="_blank">
                <div class="link-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="link-content">
                    <h3>Ver Planos Premium</h3>
                    <p>Desbloqueie todos os recursos</p>
                </div>
                <i class="fas fa-chevron-right link-arrow"></i>
            </a>

            <a href="/recursos" class="link-card" target="_blank">
                <div class="link-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="link-content">
                    <h3>Conheça os Recursos</h3>
                    <p>Veja tudo que você pode fazer</p>
                </div>
                <i class="fas fa-chevron-right link-arrow"></i>
            </a>

            <a href="https://wa.me/5544999999999?text=Olá,%20vim%20do%20link%20da%20bio!" class="link-card" target="_blank">
                <div class="link-icon whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="link-content">
                    <h3>Suporte WhatsApp</h3>
                    <p>Tire suas dúvidas em tempo real</p>
                </div>
                <i class="fas fa-chevron-right link-arrow"></i>
            </a>
        </section>

        <!-- Recursos em Destaque -->
        <section class="features-section">
            <h2 class="section-title">Por que escolher o Lukrato?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-wallet"></i>
                    <h4>Controle Total</h4>
                    <p>Gerencie receitas e despesas</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-pie"></i>
                    <h4>Relatórios</h4>
                    <p>Visualize seus gastos</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-piggy-bank"></i>
                    <h4>Investimentos</h4>
                    <p>Acompanhe sua carteira</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <h4>Mobile First</h4>
                    <p>Use em qualquer lugar</p>
                </div>
            </div>
        </section>

        <!-- Redes Sociais -->
        <section class="social-section">
            <h2 class="section-title">Siga-nos nas redes</h2>
            <div class="social-links">
                <a href="https://instagram.com/lukrato" class="social-btn instagram" target="_blank" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://facebook.com/lukrato" class="social-btn facebook" target="_blank" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/lukrato" class="social-btn twitter" target="_blank" title="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://youtube.com/@lukrato" class="social-btn youtube" target="_blank" title="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="https://linkedin.com/company/lukrato" class="social-btn linkedin" target="_blank" title="LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="card-footer">
            <p>&copy; <?php echo date('Y'); ?> Lukrato. Todos os direitos reservados.</p>
            <div class="footer-links">
                <a href="/termos">Termos de Uso</a>
                <span>•</span>
                <a href="/privacidade">Privacidade</a>
            </div>
        </footer>
    </div>

    <script src="/assets/js/card.js"></script>
</body>
</html>
