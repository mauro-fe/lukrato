<style>
    .legal-page {
        padding: 48px 0;
        background: var(--color-bg);
    }

    .legal-page .container {
        width: min(900px, 95%);
        margin: 0 auto;
        background: var(--color-surface);
        padding: 32px;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
    }

    .legal-page h1 {
        margin-bottom: 16px;
        font-size: 2rem;
        color: var(--color-primary);
    }

    .legal-page h2 {
        margin-top: 24px;
        color: var(--color-text);
    }

    .legal-page p {
        margin-top: 8px;
        color: var(--color-text-muted);
        line-height: 1.6;
    }

    .legal-page a {
        color: var(--color-primary);
        text-decoration: none;
    }

    .legal-page a:hover {
        text-decoration: underline;
    }
</style>

<?php loadPageCss(); ?>

<main class="legal-page">
    <div class="container">
        <h1>Política de Privacidade</h1>
        <p>Última atualização: <?= date('d/m/Y') ?></p>

        <section>
            <h2>1. Coleta de Dados</h2>
            <p>
                Coletamos informações fornecidas diretamente por você, como nome, email e dados financeiros inseridos na
                plataforma.
            </p>
        </section>

        <section>
            <h2>2. Uso das Informações</h2>
            <p>
                Usamos seus dados para melhorar sua experiência e oferecer funcionalidades do sistema.
            </p>
        </section>

        <section>
            <h2>3. Compartilhamento</h2>
            <p>
                Não vendemos nem compartilhamos seus dados com terceiros, exceto quando exigido por lei.
            </p>
        </section>

        <section>
            <h2>4. Cookies</h2>
            <p>
                Utilizamos cookies para manter sua sessão ativa e otimizar o desempenho da plataforma.
            </p>
        </section>

        <section>
            <h2>5. Seus Direitos</h2>
            <p>
                Você pode solicitar a exclusão ou correção de seus dados a qualquer momento.
            </p>
        </section>
    </div>
</main>