<style>
    .lk-support-button {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%);
        color: #fff;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        box-shadow: 0 8px 24px rgba(230, 126, 34, 0.35);
        transition: all var(--transition-normal);
        z-index: 9999;
        cursor: pointer;
        border: none;
        text-decoration: none;
    }

    .lk-support-button:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 12px 32px rgba(230, 126, 34, 0.45);
        background: linear-gradient(135deg, #d35400 0%, var(--color-primary) 100%);
    }

    .lk-support-button:active {
        transform: translateY(-2px) scale(1.02);
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

    .lk-support-info-email {
        font-size: var(--font-size-sm);
        color: var(--color-text-muted);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .lk-support-info-email i {
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
    }

    .swal2-popup.lk-support-modal .swal2-textarea:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--ring);
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

    /* Animação de pulso no botão */
    @keyframes pulse-support {
        0%, 100% {
            box-shadow: 0 8px 24px rgba(230, 126, 34, 0.35);
        }
        50% {
            box-shadow: 0 8px 24px rgba(230, 126, 34, 0.55);
        }
    }

    .lk-support-button {
        animation: pulse-support 3s ease-in-out infinite;
    }

    .lk-support-button:hover {
        animation: none;
    }
</style>

<?php

use Application\Lib\Auth;

// Pega o usuário logado a partir do Auth
$user = Auth::user();

$nomeUsuario  = $user?->primeiro_nome
    ?? $user?->nome
    ?? $user?->username
    ?? '';

$emailUsuario = $user?->email ?? '';
?>

<a href="#"
   class="lk-support-button"
   title="Fale com o Suporte"
   data-support-name="<?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>"
   data-support-email="<?= htmlspecialchars($emailUsuario, ENT_QUOTES, 'UTF-8') ?>"
   onclick="openSupportModal(this); return false;">
    <i class="fas fa-headset"></i>
</a>

<script>
    function openSupportModal(triggerEl) {
        const name = triggerEl?.dataset.supportName || 'Usuário';
        const email = triggerEl?.dataset.supportEmail || '';

        Swal.fire({
            title: "Fale com o Suporte",
            html: `
                <div class="lk-support-info">
                    <div class="lk-support-info-label">Enviando como:</div>
                    <div class="lk-support-info-name">${name}</div>
                    ${email ? `<div class="lk-support-info-email"><i class="fas fa-envelope"></i> ${email}</div>` : ''}
                </div>
                <textarea id="support-message"
                    class="swal2-textarea"
                    placeholder="Descreva sua dúvida, problema ou sugestão com detalhes..."></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-paper-plane"></i> Enviar',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            customClass: {
                popup: 'lk-support-modal'
            },
            didOpen: () => {
                // Foca automaticamente no textarea
                document.getElementById('support-message').focus();
            },
            preConfirm: () => {
                const msg = document.getElementById("support-message").value.trim();

                if (!msg) {
                    Swal.showValidationMessage("Por favor, escreva uma mensagem.");
                    return false;
                }

                if (msg.length < 10) {
                    Swal.showValidationMessage("A mensagem deve ter pelo menos 10 caracteres.");
                    return false;
                }

                return {
                    message: msg
                };
            }
        }).then(result => {
            if (!result.isConfirmed) return;

            sendSupportMessage(result.value);
        });
    }

    function sendSupportMessage(data) {
        // Mostra loading
        Swal.fire({
            title: 'Enviando...',
            html: 'Por favor, aguarde enquanto enviamos sua mensagem.',
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
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Mensagem enviada!",
                        html: `<p style="color: var(--color-text-muted); font-size: var(--font-size-base);">${response.message || "Em breve entraremos em contato."}</p>`,
                        confirmButtonText: 'Fechar',
                        customClass: {
                            popup: 'lk-support-modal'
                        }
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro ao enviar",
                        html: `<p style="color: var(--color-text-muted); font-size: var(--font-size-base);">${response.message || "Tente novamente."}</p>`,
                        confirmButtonText: 'Tentar novamente',
                        customClass: {
                            popup: 'lk-support-modal'
                        }
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: "error",
                    title: "Erro inesperado",
                    html: '<p style="color: var(--color-text-muted); font-size: var(--font-size-base);">Falha ao enviar sua mensagem. Verifique sua conexão e tente novamente.</p>',
                    confirmButtonText: 'Fechar',
                    customClass: {
                        popup: 'lk-support-modal'
                    }
                });
            });
    }
</script>