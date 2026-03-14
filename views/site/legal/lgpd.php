<?php loadPageCss(); ?>

<section class="lk-legal-page" data-page="lgpd">
    <div class="lk-legal-container">
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
                <a class="lk-legal-btn lk-legal-btn-primary" href="<?= BASE_URL ?>#contato">
                    Solicitar por e-mail
                </a>
            </div>

            <p class="lk-legal-note">
                Este resumo complementa a <a href="<?= BASE_URL ?>privacidade">Política de Privacidade</a> e os <a
                    href="<?= BASE_URL ?>termos">Termos de
                    Uso</a>.
            </p>
        </section>

    </div>
</section>