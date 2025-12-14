<style>
    /* =========================
   LGPD PAGE (Lukrato)
   ========================= */

    .lk-legal {
        background: var(--color-bg, #e6f0fa);
        padding: 52px 16px;
    }

    .lk-legal-wrap {
        max-width: 920px;
        margin: 0 auto;
    }

    .lk-legal-hero {
        text-align: center;
        padding: 26px 18px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .65);
        border: 1px solid rgba(15, 23, 42, .10);
        backdrop-filter: blur(10px);
    }

    .lk-back-home {
        display: inline-block;
        margin-bottom: 20px;
        text-decoration: none;
        color: var(--color-primary, #e67e22);
        font-weight: 600;
    }

    .lk-legal-title {
        margin: 12px 0 8px;
        font-size: clamp(1.7rem, 3vw, 2.3rem);
        letter-spacing: -0.02em;
        color: var(--color-text, #0f172a);
        font-weight: 900;
    }

    .lk-legal-subtitle {
        margin: 0 auto;
        max-width: 60ch;
        line-height: 1.6;
        color: var(--color-text-muted, #475569);
        font-size: 1.03rem;
    }

    .lk-legal-meta {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px;
        color: var(--color-text-muted, #475569);
        font-size: .95rem;
    }

    .lk-legal-dot {
        opacity: .65;
    }

    .lk-legal-card {
        margin-top: 14px;
        padding: 20px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .70);
        border: 1px solid rgba(15, 23, 42, .10);
        backdrop-filter: blur(10px);
    }

    .lk-legal-card h2 {
        margin: 0 0 12px;
        font-size: 1.2rem;
        color: var(--color-text, #0f172a);
        font-weight: 900;
        letter-spacing: -0.01em;
    }

    .lk-legal-text {
        margin: 0 0 14px;
        color: var(--color-text-muted, #475569);
        line-height: 1.6;
    }

    .lk-legal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .lk-legal-item {
        padding: 14px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .55);
        border: 1px solid rgba(15, 23, 42, .08);
    }

    .lk-legal-item h3 {
        margin: 0 0 6px;
        font-size: 1rem;
        color: var(--color-text, #0f172a);
        font-weight: 850;
    }

    .lk-legal-item p {
        margin: 0;
        color: var(--color-text-muted, #475569);
        line-height: 1.55;
        font-size: .98rem;
    }

    .lk-legal-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .lk-legal-btn {
        height: 44px;
        padding: 0 16px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        text-decoration: none;
        transition: transform .12s ease, filter .12s ease, background .12s ease;
        border: 1px solid transparent;
    }

    .lk-legal-btn:hover {
        transform: translateY(-1px);
        filter: brightness(.98);
    }

    .lk-legal-btn-primary {
        background: var(--color-primary, #e67e22);
        color: #fff;
    }

    .lk-legal-btn-ghost {
        background: rgba(255, 255, 255, .65);
        border-color: rgba(15, 23, 42, .12);
        color: var(--color-text, #0f172a);
    }

    .lk-legal-note {
        margin: 14px 0 0;
        color: var(--color-text-muted, #475569);
        font-size: .95rem;
        line-height: 1.6;
    }

    .lk-legal-note a {
        color: var(--color-primary, #e67e22);
        font-weight: 800;
        text-decoration: none;
    }

    .lk-legal-note a:hover {
        text-decoration: underline;
    }

    .lk-legal-footer {
        margin-top: 18px;
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        color: var(--color-text-muted, #475569);
        font-size: .95rem;
    }

    .lk-legal-footer a {
        color: var(--color-text, #0f172a);
        text-decoration: none;
        font-weight: 800;
    }

    .lk-legal-footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 860px) {
        .lk-legal-grid {
            grid-template-columns: 1fr;
        }

        .lk-legal-hero {
            text-align: left;
        }

        .lk-legal-meta {
            justify-content: flex-start;
        }
    }
</style>


<?php loadPageCss(); ?>

<main class="lk-legal" role="main">
    <div class="lk-legal-wrap">
        <a href="<?= BASE_URL ?>/" class="lk-back-home">← Voltar para a Home</a>
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