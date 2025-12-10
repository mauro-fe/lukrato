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
        <h1>Termos de Uso</h1>
        <p>Última atualização: <?= date('d/m/Y') ?></p>

        <section>
            <h2>1. Introdução</h2>
            <p>
                Bem-vindo ao Lukrato! Ao criar uma conta, você concorda com estes Termos de Uso.
                Leia com atenção antes de continuar.
            </p>
        </section>

        <section>
            <h2>2. Uso do Sistema</h2>
            <p>
                O Lukrato oferece ferramentas para gestão financeira pessoal.
                Você deve utilizar o sistema de maneira lícita e responsável.
            </p>
        </section>

        <section>
            <h2>3. Privacidade e Segurança</h2>
            <p>
                Seus dados são tratados conforme nossa Política de Privacidade, disponível em:
                <a href="<?= BASE_URL ?>/privacidade">Política de Privacidade</a>
            </p>
        </section>

        <section>
            <h2>4. Responsabilidades</h2>
            <p>
                Você é responsável pelas informações cadastradas na plataforma e
                por manter suas credenciais de acesso em segurança.
            </p>
        </section>

        <section>
            <h2>5. Alterações nos Termos</h2>
            <p>
                O Lukrato pode atualizar estes Termos a qualquer momento.
                Mudanças importantes serão notificadas aos usuários.
            </p>
        </section>
    </div>
</main>