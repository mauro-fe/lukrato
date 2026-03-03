/**
 * ============================================================================
 * LUKRATO — Support Button
 * ============================================================================
 * Modal de suporte via SweetAlert2 com envio de mensagens.
 * Extraído de: views/admin/partials/botao-suporte.php
 * ============================================================================
 */

/**
 * Abre o modal de suporte com dados do usuário.
 * @param {HTMLElement} triggerEl - Elemento que disparou o modal (contém data attributes).
 */
function openSupportModal(triggerEl) {
    const name = triggerEl?.dataset.supportName || 'Usuário';
    const email = triggerEl?.dataset.supportEmail || '';
    const telefone = triggerEl?.dataset.supportTel || '';
    const codigo = triggerEl?.dataset.supportCod || '';

    Swal.fire({
        title: '<i data-lucide="headphones" style="color: var(--color-primary); font-size: 26px; margin-right: 8px;"></i> Fale com o Suporte',
        html: `
            <div class="lk-support-info">
                <div class="lk-support-info-label">Enviando como:</div>
                <div class="lk-support-info-name">${name}</div>
                ${email ? `<div class="lk-support-info-email"><i data-lucide="mail"></i> ${email}</div>` : ''}
                ${telefone ? `
                    <div class="lk-support-info-tel">
                        <i data-lucide="phone"></i> (${codigo}) ${telefone}
                    </div>
                ` : ''}
            </div>

            <div class="lk-preference-label">
                <i data-lucide="reply"></i>
                Como prefere receber o retorno?
            </div>
            <div class="lk-contact-preference">
                <label class="lk-radio">
                    <input type="radio" name="retorno" value="whatsapp">
                    <span class="whats"><i class="fab fa-whatsapp" style="width: 0 !important;height: 0 !important;"></i>WhatsApp</span>
                </label>

                <label class="lk-radio">
                    <input type="radio" name="retorno" value="email" checked>
                    <span><i data-lucide="mail"></i>E-mail</span>
                </label>
            </div>

            <textarea id="support-message"
                class="swal2-textarea"
                placeholder="Descreva sua dúvida, problema ou sugestão com detalhes... Retornaremos o mais breve possível! 😊"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="send"></i> Enviar Mensagem',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
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
            textarea.addEventListener('keydown', function () {
                this.style.transform = 'scale(1.01)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
            });

            // Efeito visual nos radio buttons
            const radios = document.querySelectorAll('.lk-radio');
            radios.forEach(radio => {
                radio.addEventListener('click', function () {
                    radios.forEach(r => r.style.animation = 'none');
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
                Swal.showValidationMessage("📝 A mensagem precisa ter pelo menos 10 caracteres para nos ajudar melhor!");
                return false;
            }

            if (!retorno) {
                Swal.showValidationMessage("📱 Por favor, selecione como prefere receber o retorno!");
                return false;
            }

            return { message: msg, retorno: retorno };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        sendSupportMessage(result.value);
    });
}

/**
 * Envia a mensagem de suporte via API.
 * @param {object} data - Objeto com { message, retorno }.
 */
function sendSupportMessage(data) {
    const baseUrl = (document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/$/, '');

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
        didOpen: () => { Swal.showLoading(); },
        customClass: { popup: 'lk-support-modal' }
    });

    fetch(`${baseUrl}/api/suporte/enviar`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
        .then(r => r.json())
        .then(response => {
            clearInterval(loadingInterval);

            if (response.success) {
                const retornoIcon = data.retorno === 'whatsapp'
                    ? '<i class="fab fa-whatsapp"></i>'
                    : '<i data-lucide="mail" style="color: var(--color-primary);"></i>';

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
                    customClass: { popup: 'lk-support-modal' },
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
                    customClass: { popup: 'lk-support-modal' }
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
                            <i data-lucide="lightbulb" style="color: var(--color-primary);"></i> Possíveis soluções:
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
                customClass: { popup: 'lk-support-modal' }
            }).then((result) => {
                if (result.isConfirmed) {
                    sendSupportMessage(data);
                }
            });
        });
}
