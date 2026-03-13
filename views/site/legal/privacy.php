<style>
    /* =========================
   PRIVACY PAGE MODERN (Lukrato)
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

    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }

        100% {
            background-position: 1000px 0;
        }
    }

    .lk-legal-page {
        background: var(--color-bg, #f8fafc);
        min-height: 100vh;
        padding: 80px 24px;
        position: relative;
        overflow: hidden;
    }

    .lk-legal-page::before {
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

    .lk-legal-container {
        max-width: 980px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
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
        animation: fadeInUp 0.6s ease-out;
    }

    .lk-back-home:hover {
        background: var(--color-primary, #e67e22);
        color: #fff;
        transform: translateX(-8px);
        box-shadow: 0 8px 24px rgba(230, 126, 34, 0.35);
    }

    .lk-legal-container h1 {
        font-size: clamp(2.5rem, 5vw, 3.5rem);
        background: var(--color-primary, #e67e22);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.1;
        animation: fadeInUp 0.6s ease-out 0.1s both;
    }

    .lk-legal-updated {
        font-size: 1rem;
        color: var(--color-text-muted, #64748b);
        margin-bottom: 40px;
        padding: 12px 20px;
        border-radius: 50px;
        background: rgba(230, 126, 34, 0.08);
        border: 1px solid rgba(230, 126, 34, 0.15);
        backdrop-filter: blur(10px);
        display: inline-block;
        animation: fadeInUp 0.6s ease-out 0.2s both;
    }

    .lk-legal-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 32px;
        padding: 48px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2),
            0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(20px);
        animation: fadeInUp 0.6s ease-out 0.3s both;
        position: relative;
        overflow: hidden;
    }

    .lk-legal-card::before {
        content: '🔐';
        position: absolute;
        top: -40px;
        right: -40px;
        font-size: 200px;
        opacity: 0.04;
        pointer-events: none;
        animation: float 8s ease-in-out infinite;
    }

    .lk-legal-card h2 {
        font-size: 1.4rem;
        margin-top: 36px;
        margin-bottom: 16px;
        color: var(--color-primary, #e67e22);
        font-weight: 900;
        letter-spacing: -0.02em;
        position: relative;
        padding-left: 24px;
    }

    .lk-legal-card h2::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 24px;
        background: linear-gradient(135deg, var(--color-primary, #e67e22) 0%, var(--color-secondary, #2c3e50) 100%);
        border-radius: 3px;
    }

    .lk-legal-card h2:first-child {
        margin-top: 0;
    }

    .lk-legal-card p {
        font-size: 1.05rem;
        line-height: 1.8;
        color: var(--color-text-muted, #64748b);
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
    }

    .lk-legal-card strong {
        color: var(--color-primary, #e67e22);
        font-weight: 800;
    }

    .lk-legal-card a {
        color: var(--color-primary, #e67e22);
        font-weight: 700;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        transition: border-color 0.3s ease;
    }

    .lk-legal-card a:hover {
        border-bottom-color: var(--color-primary, #e67e22);
    }

    .lk-legal-footer {
        margin-top: 48px;
        text-align: center;
        animation: fadeInUp 0.6s ease-out 0.4s both;
    }

    .lk-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 18px 40px;
        background: linear-gradient(135deg, var(--color-primary, #e67e22) 0%, #d35400 100%);
        color: #fff;
        border-radius: 50px;
        font-weight: 800;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 8px 24px rgba(230, 126, 34, 0.4);
        position: relative;
        overflow: hidden;
    }

    .lk-btn-primary::before {
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

    .lk-btn-primary:hover::before {
        width: 300px;
        height: 300px;
    }

    .lk-btn-primary:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 16px 40px rgba(230, 126, 34, 0.6);
    }

    /* Section dividers */
    .lk-legal-card h2+p {
        padding: 20px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(230, 126, 34, 0.05) 0%, rgba(44, 62, 80, 0.05) 100%);
        border: 1px solid rgba(230, 126, 34, 0.1);
        margin-bottom: 24px;
    }

    /* Mobile */
    @media (max-width: 640px) {
        .lk-legal-page {
            padding: 40px 16px;
        }

        .lk-legal-card {
            padding: 28px 20px;
        }

        .lk-legal-container h1 {
            font-size: clamp(2rem, 8vw, 2.5rem);
        }

        .lk-legal-card h2 {
            font-size: 1.2rem;
            padding-left: 20px;
        }

        .lk-btn-primary {
            width: 100%;
            padding: 16px 32px;
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
                usuário. Quando você utiliza a integração com WhatsApp ou Telegram,
                também coletamos seu número de telefone e o conteúdo das mensagens
                enviadas ao nosso chatbot para processar seus lançamentos financeiros.
            </p>

            <h2>2. Uso das Informações</h2>
            <p>
                Os dados são utilizados exclusivamente para oferecer as funcionalidades
                do sistema, melhorar a experiência do usuário e garantir a segurança da
                plataforma.
            </p>

            <h2>3. Integração com WhatsApp Business</h2>
            <p>
                O Lukrato oferece integração com a <strong>API oficial do WhatsApp Business (Meta)</strong>
                para permitir que você registre lançamentos financeiros por mensagem.
                Ao vincular seu WhatsApp ao Lukrato, você consente com os seguintes termos:
            </p>
            <p>
                <strong>Dados coletados via WhatsApp:</strong> número de telefone, conteúdo das mensagens
                enviadas ao chatbot e respostas a botões de confirmação.
            </p>
            <p>
                <strong>Finalidade:</strong> as mensagens são processadas por inteligência artificial
                para identificar e registrar automaticamente transações financeiras na sua conta Lukrato.
            </p>
            <p>
                <strong>Armazenamento:</strong> as mensagens são armazenadas de forma segura em nosso
                banco de dados para fins de processamento e histórico. Transações pendentes de confirmação
                expiram automaticamente após o prazo configurado.
            </p>
            <p>
                <strong>Opt-in / Opt-out:</strong> a vinculação do WhatsApp é totalmente voluntária.
                Você pode desvincular seu número a qualquer momento nas configurações da sua conta,
                interrompendo imediatamente o processamento de novas mensagens.
            </p>
            <p>
                <strong>Compartilhamento:</strong> o conteúdo das suas mensagens não é compartilhado
                com terceiros, exceto com o provedor de inteligência artificial para processar
                seus lançamentos. Nenhuma mensagem é utilizada para fins publicitários.
            </p>

            <h2>4. Compartilhamento de Dados</h2>
            <p>
                Não vendemos nem compartilhamos seus dados com terceiros, exceto quando
                exigido por lei ou ordem judicial, ou conforme descrito na seção de
                integração com WhatsApp Business acima.
            </p>

            <h2>5. Cookies</h2>
            <p>
                Utilizamos cookies estritamente necessários para manter sua sessão ativa
                e garantir o correto funcionamento do sistema.
            </p>

            <h2>6. Seus Direitos (LGPD)</h2>
            <p>
                Você pode solicitar a qualquer momento o acesso, correção ou exclusão de
                seus dados pessoais, conforme a Lei Geral de Proteção de Dados (LGPD).
                Isso inclui solicitar a exclusão de mensagens armazenadas via WhatsApp e
                a desvinculação do seu número de telefone.
            </p>

            <h2>7. Segurança</h2>
            <p>
                Adotamos medidas técnicas e organizacionais para proteger seus dados
                contra acessos não autorizados, perda ou uso indevido.
            </p>

            <h2>8. Contato</h2>
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