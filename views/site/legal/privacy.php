<style>
    .lk-legal-page {
        background: var(--color-bg, #e6f0fa);
        padding: 60px 16px;
    }

    .lk-legal-container {
        max-width: 880px;
        margin: 0 auto;
    }

    .lk-back-home {
        display: inline-block;
        margin-bottom: 20px;
        text-decoration: none;
        color: var(--color-primary, #e67e22);
        font-weight: 600;
    }

    .lk-legal-container h1 {
        font-size: 2.2rem;
        color: var(--color-text, #1e293b);
        margin-bottom: 8px;
    }

    .lk-legal-updated {
        font-size: 0.9rem;
        color: var(--color-text-muted, #475569);
        margin-bottom: 32px;
    }

    .lk-legal-card {
        background: var(--color-surface, #f8fbff);
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    }

    .lk-legal-card h2 {
        font-size: 1.1rem;
        margin-top: 24px;
        margin-bottom: 8px;
        color: var(--color-text, #1e293b);
    }

    .lk-legal-card h2:first-child {
        margin-top: 0;
    }

    .lk-legal-card p {
        font-size: 0.95rem;
        line-height: 1.6;
        color: var(--color-text-muted, #475569);
    }

    .lk-legal-card a {
        color: var(--color-primary, #e67e22);
        font-weight: 500;
        text-decoration: none;
    }

    .lk-legal-card a:hover {
        text-decoration: underline;
    }

    .lk-legal-footer {
        margin-top: 32px;
        text-align: center;
    }

    .lk-btn-primary {
        display: inline-block;
        padding: 14px 28px;
        background: var(--color-primary, #e67e22);
        color: #fff;
        border-radius: 999px;
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .lk-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(230, 126, 34, 0.35);
    }

    /* Mobile */
    @media (max-width: 640px) {
        .lk-legal-card {
            padding: 24px;
        }

        .lk-legal-container h1 {
            font-size: 1.8rem;
        }
    }
</style>

<?php loadPageCss(); ?>

<section class="lk-legal-page">
    <div class="lk-legal-container">

        <a href="<?= BASE_URL ?>/" class="lk-back-home">← Voltar para a Home</a>

        <h1>Política de Privacidade</h1>
        <p class="lk-legal-updated">Última atualização: <?= date('d/m/Y') ?></p>

        <div class="lk-legal-card">

            <h2>1. Coleta de Dados</h2>
            <p>
                Coletamos apenas as informações necessárias para o funcionamento do
                Lukrato, como nome, e-mail e dados financeiros inseridos pelo próprio
                usuário.
            </p>

            <h2>2. Uso das Informações</h2>
            <p>
                Os dados são utilizados exclusivamente para oferecer as funcionalidades
                do sistema, melhorar a experiência do usuário e garantir a segurança da
                plataforma.
            </p>

            <h2>3. Compartilhamento de Dados</h2>
            <p>
                Não vendemos nem compartilhamos seus dados com terceiros, exceto quando
                exigido por lei ou ordem judicial.
            </p>

            <h2>4. Cookies</h2>
            <p>
                Utilizamos cookies estritamente necessários para manter sua sessão ativa
                e garantir o correto funcionamento do sistema.
            </p>

            <h2>5. Seus Direitos (LGPD)</h2>
            <p>
                Você pode solicitar a qualquer momento o acesso, correção ou exclusão de
                seus dados pessoais, conforme a Lei Geral de Proteção de Dados (LGPD).
            </p>

            <h2>6. Segurança</h2>
            <p>
                Adotamos medidas técnicas e organizacionais para proteger seus dados
                contra acessos não autorizados, perda ou uso indevido.
            </p>

            <h2>7. Contato</h2>
            <p>
                Em caso de dúvidas sobre esta Política de Privacidade ou sobre seus dados,
                entre em contato pelo e-mail
                <strong>lukratosistema@gmail.com</strong>.
            </p>

        </div>

        <div class="lk-legal-footer">
            <a href="<?= BASE_URL ?>/" class="lk-btn-primary">Voltar para a Home</a>
        </div>

    </div>
</section>