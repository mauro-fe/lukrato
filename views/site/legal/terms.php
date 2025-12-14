<style>
    /* ===============================
   TERMS / LEGAL PAGE (Lukrato)
   =============================== */
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

        <h1>Termos de Uso</h1>
        <p class="lk-legal-updated">Última atualização: <?= date('d/m/Y') ?></p>

        <div class="lk-legal-card">

            <h2>1. Introdução e Aceite</h2>
            <p>
                Bem-vindo ao <strong>Lukrato</strong>. Ao acessar ou utilizar a plataforma,
                você concorda com estes Termos de Uso. Caso não concorde, recomendamos que
                não utilize o sistema.
            </p>

            <h2>2. Uso do Sistema</h2>
            <p>
                O Lukrato é uma ferramenta de organização financeira pessoal. O uso deve
                ser feito de forma lícita, responsável e de acordo com a legislação vigente.
            </p>

            <h2>3. Conta e Segurança</h2>
            <p>
                Você é responsável pelas informações fornecidas no cadastro e pela
                confidencialidade de seus dados de acesso. Qualquer atividade realizada
                na sua conta é de sua responsabilidade.
            </p>

            <h2>4. Planos, Pagamentos e Cancelamento</h2>
            <p>
                O Lukrato oferece plano gratuito e plano pago (Pro). O cancelamento pode
                ser feito a qualquer momento, diretamente pelo sistema, sem fidelidade.
            </p>

            <h2>5. Responsabilidades</h2>
            <p>
                O Lukrato não substitui serviços contábeis, financeiros ou jurídicos.
                As informações exibidas servem apenas como apoio à organização financeira.
            </p>

            <h2>6. Privacidade e Proteção de Dados</h2>
            <p>
                O tratamento de dados pessoais segue a legislação vigente (LGPD).
                Para mais informações, consulte nossa
                <a href="<?= BASE_URL ?>privacidade">Política de Privacidade</a>.
            </p>

            <h2>7. Alterações e Contato</h2>
            <p>
                Estes Termos podem ser atualizados a qualquer momento. Em caso de dúvidas,
                entre em contato pelo e-mail
                <strong>lukratosistema@gmail.com</strong>.
            </p>

        </div>

        <div class="lk-legal-footer">
            <a href="<?= BASE_URL ?>/" class=" lk-btn-primary">Voltar para a Home</a>
        </div>

    </div>
</section>