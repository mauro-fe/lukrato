<style>
<<<<<<< HEAD
.lk-support-button {
    position: fixed !important;
    bottom: 24px !important;
    right: 24px !important;
    left: auto !important;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%);
    color: var(--color-text) !important;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    box-shadow: none !important;
    transition: all var(--transition-normal);
    z-index: 9999 !important;
    cursor: pointer;
    border: none;
    text-decoration: none;
    overflow: visible !important;
}

.lk-support-button::before {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: 100%;
    background: linear-gradient(135deg, var(--color-primary), #d35400);
    opacity: 0;
    transition: opacity var(--transition-normal);
    z-index: -1;
    filter: blur(12px);
}

.lk-support-button:hover::before {
    opacity: 0.6;
    animation: glow-pulse 2s ease-in-out infinite;
}

.lk-support-button:hover {
    transform: translateY(-4px) scale(1.05) rotate(-5deg);
    box-shadow: 0 5px 20px rgba(230, 126, 34, 0.45);
    background: linear-gradient(135deg, #d35400 0%, var(--color-primary) 100%);
}

.lk-support-button:active {
    transform: translateY(-2px) scale(1.02) rotate(0deg);
}

.lk-support-button i {
    animation: headset-bounce 2s ease-in-out infinite;
}

.lk-support-button:hover i {
    animation: headset-wiggle 0.5s ease-in-out;
}

/* Tooltip animado */
.lk-support-button::after {
    content: '💬 Fale com o suporte' !important;
    position: absolute !important;
    right: 100% !important;
    top: 50% !important;
    margin-right: 12px !important;
    transform: translateY(-50%) translateX(10px) !important;
    background: var(--color-surface) !important;
    color: var(--color-text) !important;
    padding: 0.75rem 1.25rem !important;
    border-radius: var(--radius-md) !important;
    font-size: var(--font-size-sm) !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
    opacity: 0 !important;
    pointer-events: none !important;
    box-shadow: var(--shadow-lg) !important;
    border: 2px solid var(--color-primary) !important;
    transition: all var(--transition-normal) !important;
    font-family: var(--font-primary) !important;
    z-index: 10000 !important;
}

.lk-support-button:hover::after {
    opacity: 1 !important;
    transform: translateY(-50%) translateX(0) !important;
}

/* Customização do SweetAlert2 */
.swal2-popup.lk-support-modal {
    background: var(--color-surface);
    border-radius: var(--radius-xl);
    padding: 2rem;
    box-shadow: var(--shadow-xl);
    border: 1px solid var(--glass-border);
}

.swal2-popup.lk-support-modal .swal2-title {
    color: var(--color-text);
    font-size: var(--font-size-2xl);
    font-weight: 700;
    margin-bottom: 1.5rem;
    font-family: var(--font-primary);
}

.swal2-html-container {
    overflow: visible !important;
}

.lk-support-info {
    text-align: left;
    padding: 1rem;
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    margin-bottom: 1.25rem;
}

.lk-support-info-label {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.lk-support-info-name {
    font-size: var(--font-size-base);
    color: var(--color-text);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.lk-support-info-email,
.lk-support-info-tel {
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.lk-support-info-email i,
.lk-support-info-tel i {
    color: var(--color-primary);
}

.swal2-popup.lk-support-modal .swal2-textarea {
    background: var(--color-bg);
    border: 2px solid var(--glass-border);
    border-radius: var(--radius-md);
    color: var(--color-text);
    font-size: var(--font-size-base);
    font-family: var(--font-primary);
    padding: 1rem;
    min-height: 140px;
    resize: vertical;
    transition: all var(--transition-fast);
    width: 100%;
    margin: 0 auto;
}

.swal2-popup.lk-support-modal .swal2-textarea:focus {
    outline: none;
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.15);
    transform: scale(1.01);
}

.swal2-popup.lk-support-modal .swal2-textarea::placeholder {
    color: var(--color-text-muted);
    opacity: 0.7;
}

.swal2-popup.lk-support-modal .swal2-actions {
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.swal2-popup.lk-support-modal .swal2-confirm {
    background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%);
    color: #fff;
    border: none;
    border-radius: var(--radius-md);
    padding: 0.75rem 2rem;
    font-size: var(--font-size-base);
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
    transition: all var(--transition-fast);
    position: relative;
    overflow: hidden;
}

.swal2-popup.lk-support-modal .swal2-confirm::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.swal2-popup.lk-support-modal .swal2-confirm:hover::before {
    left: 100%;
}

.swal2-popup.lk-support-modal .swal2-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(230, 126, 34, 0.4);
}

.swal2-popup.lk-support-modal .swal2-cancel {
    background: var(--color-surface-muted);
    color: var(--color-text);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    padding: 0.75rem 2rem;
    font-size: var(--font-size-base);
    font-weight: 500;
    transition: all var(--transition-fast);
}

.swal2-popup.lk-support-modal .swal2-cancel:hover {
    background: var(--color-bg);
    transform: translateY(-1px);
}

.swal2-popup.lk-support-modal .swal2-validation-message {
    background: var(--color-danger);
    color: #fff;
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
}

/* Close button customizado */
.swal2-popup.lk-support-modal .swal2-close {
    color: var(--color-text-muted);
    font-size: 2rem;
    transition: all var(--transition-fast);
}

.swal2-popup.lk-support-modal .swal2-close:hover {
    color: var(--color-danger);
    transform: rotate(90deg);
}

/* Animações */
@keyframes pulse-support {

    0%,
    100% {
        box-shadow: 0 2px 10px 10px rgba(230, 126, 34, 0.35);
=======
    .lk-support-button {
        position: fixed !important;
        bottom: 24px !important;
        right: 24px !important;
        left: auto !important;
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%);
        color: var(--color-text) !important;
        border-radius: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        box-shadow: none !important;
        transition: all var(--transition-normal);
        z-index: 9999 !important;
        cursor: pointer;
        border: none;
        text-decoration: none;
        overflow: visible !important;
>>>>>>> 6bf6af1d243d52eb377952db9b5dcbd007ecd6cc
    }

    50% {
        box-shadow: 0 2px 10px 10px rgba(230, 126, 34, 0.55);
    }
}

@keyframes headset-bounce {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-3px);
    }
}

@keyframes headset-wiggle {

    0%,
    100% {
        transform: rotate(0deg);
    }

    25% {
        transform: rotate(-15deg);
    }

    75% {
        transform: rotate(15deg);
    }
}

@keyframes glow-pulse {

    0%,
    100% {
        opacity: 0.6;
    }

    50% {
        opacity: 0.8;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes pulse-dot {

    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }

    50% {
        opacity: 0.5;
        transform: scale(1.3);
    }
}

.lk-support-button {
    animation: pulse-support 3s ease-in-out infinite;
}

.lk-support-button:hover {
    animation: none;
}

/* Responsivo - Botão menor em mobile */
@media (max-width: 768px) {
    .lk-support-button {
        width: 48px !important;
        height: 48px !important;
        font-size: 20px !important;
        bottom: 20px !important;
        right: 16px !important;
    }

    .lk-support-button::after {
        display: none !important;
    }

    /* Modal responsivo */
    .swal2-popup.lk-support-modal {
        width: calc(100vw - 32px) !important;
        max-width: 500px !important;
        padding: 1.5rem !important;
        margin: 16px !important;
    }

    .swal2-popup.lk-support-modal .swal2-title {
        font-size: 1.5rem !important;
        margin-bottom: 1rem !important;
    }

    .lk-support-info {
        padding: 0.75rem !important;
        margin-bottom: 1rem !important;
    }

    .lk-support-info-label {
        font-size: 0.7rem !important;
    }

    .lk-support-info-name {
        font-size: 0.9rem !important;
    }

    .lk-support-info-email,
    .lk-support-info-tel {
        font-size: 0.85rem !important;
    }

    .lk-contact-preference {
        flex-direction: column !important;
        gap: 0.75rem !important;
    }

    .lk-radio {
        padding: 0.75rem !important;
        min-height: 48px !important;
    }

    .swal2-popup.lk-support-modal .swal2-textarea {
        min-height: 120px !important;
        font-size: 0.9rem !important;
        padding: 0.75rem !important;
    }

    .swal2-popup.lk-support-modal .swal2-actions {
        flex-direction: column !important;
        gap: 0.5rem !important;
        width: 100% !important;
    }

    .swal2-popup.lk-support-modal .swal2-confirm,
    .swal2-popup.lk-support-modal .swal2-cancel {
        width: 100% !important;
        margin: 0 !important;
    }
}

@media (max-width: 576px) {
    .lk-support-button {
        width: 44px !important;
        height: 44px !important;
        font-size: 18px !important;
        bottom: 18px !important;
        right: 12px !important;
    }

    .swal2-popup.lk-support-modal {
        width: calc(100vw - 24px) !important;
        padding: 1.25rem !important;
    }

    .swal2-popup.lk-support-modal .swal2-title {
        font-size: 1.25rem !important;
    }

    .lk-support-info {
        padding: 0.625rem !important;
    }
}

/* Animação de entrada do modal */
.swal2-popup.lk-support-modal.swal2-show {
    animation: scaleIn 0.3s ease-out;
}

.swal2-popup.lk-support-modal.swal2-hide {
    animation: fadeInUp 0.2s ease-in reverse;
}

.lk-support-info {
    animation: fadeInUp 0.4s ease-out 0.1s backwards;
}

.lk-contact-preference {
    animation: fadeInUp 0.4s ease-out 0.15s backwards;
}

.swal2-popup.lk-support-modal .swal2-textarea {
    animation: fadeInUp 0.4s ease-out 0.2s backwards;
}

.swal2-popup.lk-support-modal .swal2-actions {
    animation: fadeInUp 0.4s ease-out 0.3s backwards;
}

/* Preferência de contato */
.lk-contact-preference {
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    flex-wrap: wrap;
}

.lk-radio {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border-radius: var(--radius-md);
    border: 2px solid var(--glass-border);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all var(--transition-fast);
    user-select: none;
    flex: 1;
    padding: var(--spacing-1);
}

.lk-radio:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.2);
}



.lk-radio input {
    display: none;
}

.lk-radio span {
    color: var(--color-text);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: .5rem;
    width: 100%;
    justify-content: center;
}

.lk-radio .whats {
    gap: 1rem;

}

.lk-radio i {
    color: var(--color-text-muted);
    font-size: 1.1rem;
    transition: all var(--transition-fast);
}


/* Quando marcado */
.lk-radio input:checked+span {
    color: var(--color-primary);
}

.lk-radio input:checked~i,
.lk-radio:has(input:checked) i {
    color: var(--color-primary);
}

.lk-radio:has(input:checked) {
    background: linear-gradient(135deg, rgba(230, 126, 34, 0.1), rgba(211, 84, 0, 0.05));
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
}

/* Badge de preferência */
.lk-preference-label {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.lk-preference-label i {
    color: var(--color-primary);
}

.fa-whatsapp {
    color: #25D366 !important;

}
</style>

<?php

use Application\Lib\Auth;
use Application\Models\Telefone;
use Application\Models\Ddd;

// Pega o usuário logado a partir do Auth
$user = Auth::user();

$nomeUsuario  = $user?->nome;
$emailUsuario = $user?->email ?? '';
$telUsuario = '';
$ddd = '';
$telefoneModel = Telefone::where('id_usuario', $user->id_usuario ?? $user->id ?? null)->first();

$telUsuario = $telefoneModel->numero ?? '';
$ddd = $telefoneModel->ddd->codigo ?? '';

?>

<a href="#" class="lk-support-button" title="Fale com o Suporte"
    data-support-name="<?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>"
    data-support-email="<?= htmlspecialchars($emailUsuario, ENT_QUOTES, 'UTF-8') ?>"
    data-support-tel="<?= htmlspecialchars($telUsuario, ENT_QUOTES, 'UTF-8') ?>"
    data-support-cod="<?= htmlspecialchars($ddd, ENT_QUOTES, 'UTF-8') ?>"
    onclick="openSupportModal(this); return false;">
    <i class="fas fa-headset"></i>
</a>

<script>
function openSupportModal(triggerEl) {
    const name = triggerEl?.dataset.supportName || 'Usuário';
    const email = triggerEl?.dataset.supportEmail || '';
    const telefone = triggerEl?.dataset.supportTel || '';
    const codigo = triggerEl?.dataset.supportCod || '';

    Swal.fire({
        title: '<i class="fas fa-headset" style="color: var(--color-primary); font-size: 26px; margin-right: 8px;"></i> Fale com o Suporte',
        html: `
                <div class="lk-support-info">
                    <div class="lk-support-info-label">Enviando como:</div>
                    <div class="lk-support-info-name">${name}</div>
                    ${email ? `<div class="lk-support-info-email"><i class="fas fa-envelope"></i> ${email}</div>` : ''}
                  ${telefone ? `
    <div class="lk-support-info-tel">
        <i class="fas fa-phone"></i> (${codigo}) ${telefone}
    </div>
` : ''}

                </div>
                
                <div class="lk-preference-label">
                    <i class="fas fa-reply"></i>
                    Como prefere receber o retorno?
                </div>
                <div class="lk-contact-preference">
                    <label class="lk-radio">
                        <input type="radio" name="retorno" value="whatsapp">
                        <span class="whats"><i class="fab fa-whatsapp" style="width: 0 !important;height: 0 !important;"></i>WhatsApp</span>
                    </label>

                    <label class="lk-radio">
                        <input type="radio" name="retorno" value="email" checked>
                        <span><i class="fas fa-envelope"></i>E-mail</span>
                    </label>
                </div>
                
                <textarea id="support-message"
                    class="swal2-textarea"
                    placeholder="Descreva sua dúvida, problema ou sugestão com detalhes... Retornaremos o mais breve possível! 😊"></textarea>
            `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Enviar Mensagem',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        showCloseButton: true,
        customClass: {
            popup: 'lk-support-modal'
        },
        width: '600px',
        didOpen: () => {
            const textarea = document.getElementById('support-message');

            // Foca automaticamente no textarea
            textarea.focus();

            // Animação de digitação
            textarea.addEventListener('keydown', function() {
                this.style.transform = 'scale(1.01)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
            });

            // Adiciona efeito visual nos radio buttons
            const radios = document.querySelectorAll('.lk-radio');
            radios.forEach(radio => {
                radio.addEventListener('click', function() {
                    // Remove animação de todos
                    radios.forEach(r => r.style.animation = 'none');
                    // Adiciona animação ao clicado
                    this.style.animation = 'scaleIn 0.3s ease-out';
                    setTimeout(() => {
                        this.style.animation = '';
                    }, 300);
                });
            });
        },
        preConfirm: () => {
            const msg = document.getElementById("support-message").value.trim();
            const retorno = document.querySelector('input[name="retorno"]:checked')?.value;

            if (!msg) {
                Swal.showValidationMessage("✍️ Por favor, escreva uma mensagem para continuarmos!");
                return false;
            }

            if (msg.length < 10) {
                Swal.showValidationMessage(
                    "📝 A mensagem precisa ter pelo menos 10 caracteres para nos ajudar melhor!");
                return false;
            }

            if (!retorno) {
                Swal.showValidationMessage("📱 Por favor, selecione como prefere receber o retorno!");
                return false;
            }

            return {
                message: msg,
                retorno: retorno
            };
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        sendSupportMessage(result.value);
    });
}

function sendSupportMessage(data) {
    // Mensagens motivacionais para o loading
    const loadingMessages = [
        '✨ Conectando você com nosso time...',
        '📨 Preparando sua mensagem...',
        '🚀 Estamos quase lá...'
    ];
    let messageIndex = 0;

    const loadingInterval = setInterval(() => {
        messageIndex = (messageIndex + 1) % loadingMessages.length;
        const loadingText = document.querySelector('.swal2-html-container');
        if (loadingText) {
            loadingText.innerHTML =
                `<p style="color: var(--color-text-muted); font-size: var(--font-size-base); animation: fadeInUp 0.3s ease-out;">${loadingMessages[messageIndex]}</p>`;
        }
    }, 1500);

    Swal.fire({
        title: '✨ Enviando sua mensagem',
        html: `<p style="color: var(--color-text-muted); font-size: var(--font-size-base);">${loadingMessages[0]}</p>`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        customClass: {
            popup: 'lk-support-modal'
        }
    });

    fetch("<?= rtrim(BASE_URL, '/') ?>/api/suporte/enviar", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(response => {
            clearInterval(loadingInterval);

            if (response.success) {
                const retornoIcon = data.retorno === 'whatsapp' ?
                    '<i class="fab fa-whatsapp"></i>' :
                    '<i class="fas fa-envelope" style="color: var(--color-primary);"></i>';

                Swal.fire({
                    icon: "success",
                    title: "🎉 Mensagem enviada com sucesso!",
                    html: `
                        <div style="text-align: center;">
                            <p style="color: var(--color-text); font-size: var(--font-size-base); margin-bottom: 1rem;">
                                ${response.message || "Recebemos sua mensagem!"}
                            </p>
                            <div style="padding: 1rem; background: var(--glass-bg); border-radius: var(--radius-md); border: 1px solid var(--glass-border); backdrop-filter: var(--glass-backdrop);">
                                <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); margin: 0 0 0.5rem 0;">
                                    ${retornoIcon} Você receberá retorno via <strong style="color: var(--color-primary);">${data.retorno === 'whatsapp' ? 'WhatsApp' : 'E-mail'}</strong>
                                </p>
                                <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); margin: 0;">
                                    ⏰ Responderemos em até <strong style="color: var(--color-primary);">24h úteis</strong>
                                </p>
                            </div>
                        </div>
                    `,
                    confirmButtonText: '👍 Entendido',
                    customClass: {
                        popup: 'lk-support-modal'
                    },
                    timer: 6000,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "😕 Ops! Algo deu errado",
                    html: `
                        <p style="color: var(--color-text-muted); font-size: var(--font-size-base);">
                            ${response.message || "Não conseguimos enviar sua mensagem."}
                        </p>
                        <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); margin-top: 1rem;">
                            💡 Tente novamente ou entre em contato por outro canal.
                        </p>
                    `,
                    confirmButtonText: '🔄 Tentar novamente',
                    showCancelButton: true,
                    cancelButtonText: 'Fechar',
                    customClass: {
                        popup: 'lk-support-modal'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.querySelector('.lk-support-button').click();
                    }
                });
            }
        })
        .catch(() => {
            clearInterval(loadingInterval);

            Swal.fire({
                icon: "error",
                title: "🔌 Erro de conexão",
                html: `
                    <p style="color: var(--color-text-muted); font-size: var(--font-size-base);">
                        Não conseguimos conectar ao servidor.
                    </p>
                    <div style="margin-top: 1rem; padding: 1rem; background: var(--glass-bg); border-radius: var(--radius-md); text-align: left; backdrop-filter: var(--glass-backdrop); border: 1px solid var(--glass-border);">
                        <p style="color: var(--color-text); font-size: var(--font-size-sm); margin: 0 0 0.75rem 0; font-weight: 600;">
                            <i class="fas fa-lightbulb" style="color: var(--color-primary);"></i> Possíveis soluções:
                        </p>
                        <ul style="color: var(--color-text-muted); font-size: var(--font-size-sm); margin: 0; padding-left: 1.5rem; line-height: 1.6;">
                            <li>Verifique sua conexão com a internet</li>
                            <li>Tente recarregar a página</li>
                            <li>Entre em contato diretamente por email</li>
                        </ul>
                    </div>
                `,
                confirmButtonText: '🔄 Tentar novamente',
                showCancelButton: true,
                cancelButtonText: 'Fechar',
                customClass: {
                    popup: 'lk-support-modal'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    sendSupportMessage(data);
                }
            });
        })

}
</script>