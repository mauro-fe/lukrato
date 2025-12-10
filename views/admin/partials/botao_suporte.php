<style>
    .lk-support-button {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 58px;
        height: 58px;
        background: var(--color-primary);
        color: #fff;
        border-radius: var(--radius-full, 50%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        z-index: 9999;
        cursor: pointer;
    }

    .lk-support-button:hover {
        background-color: color-mix(in srgb,
                var(--glass-bg) 5%,
                var(--color-primary) 70%);
        transform: translateY(-2px);
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
   onclick="openSupportModal(this)">
    <i class="fas fa-headset"></i>
</a>

<script>
    function openSupportModal(triggerEl) {
        const name = triggerEl?.dataset.supportName || '';
        const email = triggerEl?.dataset.supportEmail || '';

        Swal.fire({
            title: "Fale com o Suporte",
            html: `
            <div style="text-align:left; font-size:13px; margin-bottom:8px;">
                <div style="margin-bottom:6px; color:#4b5563;">
                    Enviando como:<br>
                    <strong>${name || 'Usuário'}</strong><br>
                    <span style="font-size:12px; color:#6b7280;">${email}</span>
                </div>
                <textarea id="support-message"
                    class="swal2-textarea"
                    placeholder="Descreva sua dúvida, problema ou sugestão..."></textarea>
            </div>
        `,
            showCancelButton: true,
            confirmButtonText: "Enviar",
            cancelButtonText: "Cancelar",
            preConfirm: () => {
                const msg = document.getElementById("support-message").value.trim();

                if (!msg) {
                    Swal.showValidationMessage("Por favor, escreva uma mensagem.");
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
                        text: response.message || "Em breve entraremos em contato."
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro ao enviar",
                        text: response.message || "Tente novamente."
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: "error",
                    title: "Erro inesperado",
                    text: "Falha ao enviar sua mensagem."
                });
            });
    }
</script>

</script>