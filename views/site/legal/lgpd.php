<style>
    /* =========================
   LGPD PAGE MODERN (Lukrato)
   ========================= */

    @keyframes fadeInUp {
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
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .lk-legal {
        background: var(--color-bg, #f8fafc);
        min-height: 100vh;
        padding: 80px 24px;
        position: relative;
        overflow: hidden;
    }

    .lk-legal::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(230, 126, 34, 0.03) 1px, transparent 1px);
        background-size: 50px 50px;
        animation: float 20s ease-in-out infinite;
        pointer-events: none;
    }

    .lk-legal-wrap {
        max-width: 1000px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .lk-legal-hero {
        text-align: center;
        padding: 60px 40px;
        border-radius: 32px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(20px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2),
            0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        animation: fadeInUp 0.8s ease-out;
        position: relative;
        overflow: hidden;
    }

    .lk-legal-hero::before {
        content: '🔒';
        position: absolute;
        top: -30px;
        right: -30px;
        font-size: 180px;
        opacity: 0.05;
        pointer-events: none;
    }

    .lk-back-home {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 32px;
        padding: 12px 24px;
        text-decoration: none;
        color: var(--color-primary, #e67e22);
        font-weight: 700;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 50px;
        border: 1px solid rgba(230, 126, 34, 0.2);
        backdrop-filter: blur(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .lk-back-home:hover {
        background: var(--color-primary, #e67e22);
        color: #fff;
        transform: translateX(-8px);
        box-shadow: 0 8px 24px rgba(230, 126, 34, 0.35);
    }

    .lk-legal-title {
        margin: 0 0 16px;
        font-size: clamp(2.5rem, 5vw, 3.5rem);
        letter-spacing: -0.03em;
        background: var(--color-primary, #e67e22);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 900;
        line-height: 1.1;
        animation: fadeInUp 0.8s ease-out 0.2s both;
    }

    .lk-legal-subtitle {
        margin: 0 auto 24px;
        max-width: 65ch;
        line-height: 1.7;
        color: var(--color-text-muted, #64748b);
        font-size: 1.15rem;
        animation: fadeInUp 0.8s ease-out 0.3s both;
    }

    .lk-legal-meta {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        padding: 16px;
        border-radius: 16px;
        background: rgba(230, 126, 34, 0.08);
        border: 1px solid rgba(230, 126, 34, 0.15);
        color: var(--color-text-muted, #64748b);
        font-size: 0.95rem;
        animation: fadeInUp 0.8s ease-out 0.4s both;
    }

    .lk-legal-meta strong {
        color: var(--color-primary, #e67e22);
        font-weight: 800;
    }

    .lk-legal-dot {
        opacity: 0.4;
    }

    .lk-legal-card {
        margin-top: 32px;
        padding: 40px;
        border-radius: 32px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(20px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        animation: fadeInUp 0.8s ease-out 0.5s both;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .lk-legal-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
    }

    .lk-legal-card h2 {
        margin: 0 0 24px;
        font-size: 1.8rem;
        background: linear-gradient(135deg, var(--color-primary, #e67e22) 0%, var(--color-secondary, #2c3e50) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 900;
        letter-spacing: -0.02em;
    }

    .lk-legal-text {
        margin: 0 0 24px;
        color: var(--color-text-muted, #64748b);
        line-height: 1.8;
        font-size: 1.05rem;
    }

    .lk-legal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .lk-legal-item {
        padding: 24px;
        border-radius: 24px;
        background: linear-gradient(135deg, rgba(230, 126, 34, 0.05) 0%, rgba(44, 62, 80, 0.05) 100%);
        border: 1px solid rgba(230, 126, 34, 0.15);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .lk-legal-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(230, 126, 34, 0.1) 0%, rgba(44, 62, 80, 0.1) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .lk-legal-item:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 16px 40px rgba(230, 126, 34, 0.25);
        border-color: rgba(230, 126, 34, 0.3);
    }

    .lk-legal-item:hover::before {
        opacity: 1;
    }

    .lk-legal-item h3 {
        margin: 0 0 12px;
        font-size: 1.15rem;
        color: var(--color-primary, #e67e22);
        font-weight: 900;
        position: relative;
        z-index: 1;
    }

    .lk-legal-item p {
        margin: 0;
        color: var(--color-text-muted, #64748b);
        line-height: 1.7;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }

    .lk-legal-actions {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: center;
    }

    .lk-legal-btn {
        height: 56px;
        padding: 0 32px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-weight: 800;
        font-size: 1.05rem;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .lk-legal-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
    }

    .lk-legal-btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .lk-legal-btn:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
    }

    .lk-legal-btn-primary {
        background: linear-gradient(135deg, var(--color-primary, #e67e22) 0%, #d35400 100%);
        color: #fff;
        box-shadow: 0 8px 24px rgba(230, 126, 34, 0.4);
    }

    .lk-legal-btn-primary:hover {
        box-shadow: 0 16px 40px rgba(230, 126, 34, 0.6);
    }

    .lk-legal-btn-ghost {
        background: rgba(255, 255, 255, 0.8);
        border-color: rgba(230, 126, 34, 0.2);
        color: var(--color-primary, #e67e22);
    }

    .lk-legal-note {
        margin: 28px 0 0;
        padding: 20px;
        border-radius: 16px;
        background: rgba(230, 126, 34, 0.06);
        border-left: 4px solid var(--color-primary, #e67e22);
        color: var(--color-text-muted, #64748b);
        font-size: 0.98rem;
        line-height: 1.8;
    }

    .lk-legal-note a {
        color: var(--color-primary, #e67e22);
        font-weight: 800;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        transition: border-color 0.3s ease;
    }

    .lk-legal-note a:hover {
        border-bottom-color: var(--color-primary, #e67e22);
    }

    .lk-legal-footer {
        margin-top: 48px;
        padding: 32px;
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        border-radius: 24px;
        background: var(--color-surface, #ffffff);
        border: 1px solid var(--color-card-border, rgba(0, 0, 0, 0.05));
        backdrop-filter: blur(10px);
        color: var(--color-text-muted, #64748b);
        font-size: 0.95rem;
        animation: fadeInUp 0.8s ease-out 0.6s both;
    }

    .lk-legal-footer a {
        color: var(--color-primary, #e67e22);
        text-decoration: none;
        font-weight: 800;
        border-bottom: 2px solid transparent;
        transition: border-color 0.3s ease;
    }

    .lk-legal-footer a:hover {
        border-bottom-color: var(--color-primary, #e67e22);
    }

    @media (max-width: 860px) {
        .lk-legal {
            padding: 40px 16px;
        }

        .lk-legal-hero {
            padding: 40px 24px;
            text-align: left;
        }

        .lk-legal-card {
            padding: 28px 20px;
        }

        .lk-legal-grid {
            grid-template-columns: 1fr;
        }

        .lk-legal-meta {
            justify-content: flex-start;
        }

        .lk-legal-title {
            font-size: clamp(2rem, 8vw, 2.5rem);
        }

        .lk-legal-btn {
            width: 100%;
        }
    }
</style>


<?php loadPageCss(); ?>

<main class="lk-legal" role="main">
    <div class="lk-legal-wrap">
        <a href="<?= BASE_URL ?>" class="lk-back-home">← Voltar para a Home</a>
        <header class="lk-legal-hero">
            <h1 class="lk-legal-title">Proteção de Dados</h1>
            <p class="lk-legal-subtitle">
                Tratamos dados pessoais com transparência e segurança, conforme a Lei nº 13.709/2018 (LGPD).
            </p>

            <div class="lk-legal-meta">
                <span>Atualizado em <?= date('d/m/Y') ?></span>
                <span class="lk-legal-dot">•</span>
                <span>Contato: <strong>lukratosistema@gmail.com</strong></span>
            </div>
        </header>

        <section class="lk-legal-card">
            <h2>Em resumo</h2>
            <div class="lk-legal-grid">
                <div class="lk-legal-item">
                    <h3>O que coletamos</h3>
                    <p>Nome, e-mail e dados inseridos por você no sistema (ex.: lançamentos e categorias).</p>
                </div>

                <div class="lk-legal-item">
                    <h3>Por que usamos</h3>
                    <p>Para autenticação, funcionamento do painel e atendimento quando você solicitar.</p>
                </div>

                <div class="lk-legal-item">
                    <h3>Como protegemos</h3>
                    <p>Aplicamos medidas de segurança para reduzir riscos de acesso indevido e vazamentos.</p>
                </div>

                <div class="lk-legal-item">
                    <h3>Seus direitos</h3>
                    <p>Acesso, correção e exclusão de dados. Você pode solicitar a qualquer momento.</p>
                </div>
            </div>
        </section>

        <section class="lk-legal-card">
            <h2>Como solicitar (LGPD)</h2>
            <p class="lk-legal-text">
                Para pedidos de acesso, correção ou exclusão de dados, envie uma solicitação pelo e-mail abaixo.
            </p>

            <div class="lk-legal-actions">
                <a class="lk-legal-btn lk-legal-btn-primary"
                    href="mailto:lukratosistema@gmail.com?subject=Solicita%C3%A7%C3%A3o%20LGPD%20-%20Lukrato">
                    Solicitar por e-mail
                </a>
            </div>

            <p class="lk-legal-note">
                Este resumo complementa a <a href="<?= BASE_URL ?>privacidade">Política de Privacidade</a> e os <a
                    href="<?= BASE_URL ?>termos">Termos de
                    Uso</a>.
            </p>
        </section>

        <footer class="lk-legal-footer">
            <span>© 2025 Lukrato</span>
        </footer>

    </div>
</main>