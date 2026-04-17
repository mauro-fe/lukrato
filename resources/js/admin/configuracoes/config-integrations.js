import {
    resolveTelegramLinkEndpoint,
    resolveTelegramStatusEndpoint,
    resolveTelegramUnlinkEndpoint,
    resolveWhatsAppLinkEndpoint,
    resolveWhatsAppStatusEndpoint,
    resolveWhatsAppUnlinkEndpoint,
    resolveWhatsAppVerifyEndpoint,
} from '../api/endpoints/integrations.js';
import { apiFetch, apiGet, getErrorMessage } from '../shared/api.js';
import { getCsrfToken } from '../perfil/profile-common.js';

function getEl(id) {
    return document.getElementById(id);
}

function setDisplay(id, displayValue) {
    const element = getEl(id);
    if (element) {
        element.style.display = displayValue;
    }
}

export function initConfigIntegrations(context) {
    const endpoints = {
        whatsappStatus: resolveWhatsAppStatusEndpoint(),
        whatsappLink: resolveWhatsAppLinkEndpoint(),
        whatsappVerify: resolveWhatsAppVerifyEndpoint(),
        whatsappUnlink: resolveWhatsAppUnlinkEndpoint(),
        telegramStatus: resolveTelegramStatusEndpoint(),
        telegramLink: resolveTelegramLinkEndpoint(),
        telegramUnlink: resolveTelegramUnlinkEndpoint(),
    };

    function buildIntegrationFormData(fields = {}) {
        const formData = new FormData();

        Object.entries(fields).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                formData.append(key, value);
            }
        });

        formData.append('csrf_token', getCsrfToken(context));
        return formData;
    }

    async function loadWhatsAppStatus() {
        const statusEl = getEl('whatsapp-status');
        if (!statusEl) {
            return;
        }

        try {
            const response = await apiGet(endpoints.whatsappStatus);
            const data = response?.data || {};

            if (data.linked) {
                statusEl.innerHTML = '<span class="status-indicator linked"></span><span class="status-text">Vinculado</span>';
                setDisplay('whatsapp-not-linked', 'none');
                setDisplay('whatsapp-verify', 'none');
                setDisplay('whatsapp-linked', '');

                const maskedPhone = getEl('whatsapp-masked-phone');
                if (maskedPhone) {
                    maskedPhone.textContent = data.phone || '';
                }
                return;
            }

            statusEl.innerHTML = '<span class="status-indicator not-linked"></span><span class="status-text">Nao vinculado</span>';
            setDisplay('whatsapp-not-linked', '');
            setDisplay('whatsapp-verify', 'none');
            setDisplay('whatsapp-linked', 'none');
        } catch {
            // status loading errors stay silent by design
        }
    }

    getEl('btn-whatsapp-link')?.addEventListener('click', async () => {
        const phone = getEl('whatsapp-phone')?.value?.trim();
        if (!phone) {
            return;
        }

        const button = getEl('btn-whatsapp-link');
        if (button) {
            button.disabled = true;
        }

        try {
            const response = await apiFetch(endpoints.whatsappLink, {
                method: 'POST',
                body: buildIntegrationFormData({ phone }),
            });

            if (response?.success) {
                setDisplay('whatsapp-not-linked', 'none');
                setDisplay('whatsapp-verify', '');

                const verifyMessage = getEl('whatsapp-verify-msg');
                if (verifyMessage) {
                    verifyMessage.textContent = response.message || '';
                }
                return;
            }

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage({ data: response }, 'Erro desconhecido'),
                    confirmButtonColor: '#e67e22',
                });
            }
        } catch (error) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage(error, 'Erro de conexao'),
                    confirmButtonColor: '#e67e22',
                });
            }
        } finally {
            if (button) {
                button.disabled = false;
            }
        }
    });

    getEl('btn-whatsapp-verify')?.addEventListener('click', async () => {
        const phone = getEl('whatsapp-phone')?.value?.trim();
        const code = getEl('whatsapp-code')?.value?.trim();
        if (!phone || !code) {
            return;
        }

        const button = getEl('btn-whatsapp-verify');
        if (button) {
            button.disabled = true;
        }

        try {
            const response = await apiFetch(endpoints.whatsappVerify, {
                method: 'POST',
                body: buildIntegrationFormData({ phone, code }),
            });

            if (response?.success) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Vinculado!',
                        text: response.message,
                        confirmButtonColor: '#e67e22',
                        timer: 2500,
                        timerProgressBar: true,
                    });
                }
                void loadWhatsAppStatus();
                return;
            }

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage({ data: response }, 'Erro desconhecido'),
                    confirmButtonColor: '#e67e22',
                });
            }
        } catch (error) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage(error, 'Erro de conexao'),
                    confirmButtonColor: '#e67e22',
                });
            }
        } finally {
            if (button) {
                button.disabled = false;
            }
        }
    });

    getEl('btn-whatsapp-unlink')?.addEventListener('click', async () => {
        const confirmation = window.Swal
            ? await Swal.fire({
                icon: 'warning',
                title: 'Desvincular WhatsApp?',
                text: 'Voce nao podera mais enviar lancamentos pelo WhatsApp.',
                showCancelButton: true,
                confirmButtonText: 'Desvincular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
            })
            : { isConfirmed: window.confirm('Desvincular WhatsApp?') };

        if (!confirmation.isConfirmed) {
            return;
        }

        try {
            const response = await apiFetch(endpoints.whatsappUnlink, {
                method: 'POST',
                body: buildIntegrationFormData(),
            });

            if (response?.success) {
                void loadWhatsAppStatus();
            }
        } catch {
            // silent by design
        }
    });

    let telegramLinkPending = false;
    let telegramLinkPollTimer = null;
    let telegramLinkCountdownTimer = null;
    let telegramLinkExpiresAt = 0;
    let telegramLinkSuccessToastShown = false;

    function clearTelegramLinkTimers() {
        if (telegramLinkPollTimer) window.clearInterval(telegramLinkPollTimer);
        if (telegramLinkCountdownTimer) window.clearInterval(telegramLinkCountdownTimer);
        telegramLinkPollTimer = null;
        telegramLinkCountdownTimer = null;
        telegramLinkExpiresAt = 0;
    }

    function setTelegramLinkMeta(statusText = '', countdownText = '') {
        const statusCopy = getEl('telegram-link-status-copy');
        const countdown = getEl('telegram-link-countdown');
        if (statusCopy) statusCopy.textContent = statusText;
        if (countdown) countdown.textContent = countdownText;
    }

    function renderTelegramQr(data = {}) {
        const wrapper = getEl('telegram-qr-wrapper');
        const image = getEl('telegram-qr-image');
        if (!wrapper || !image) {
            return;
        }

        if (data.qr_code_data_uri) {
            image.src = data.qr_code_data_uri;
            wrapper.classList.add('is-visible');
            return;
        }

        image.removeAttribute('src');
        wrapper.classList.remove('is-visible');
    }

    function renderTelegramAwaitingState(data = {}) {
        telegramLinkPending = true;
        telegramLinkSuccessToastShown = false;

        const statusEl = getEl('telegram-status');
        if (statusEl) {
            statusEl.innerHTML = '<span class="status-indicator pending"></span><span class="status-text">Aguardando confirmacao</span>';
        }

        setDisplay('telegram-not-linked', 'none');
        setDisplay('telegram-code-generated', '');
        setDisplay('telegram-linked', 'none');

        const codeInput = getEl('telegram-code-display');
        const botLink = getEl('telegram-bot-link');
        if (codeInput) codeInput.value = data.code || '';
        if (botLink) botLink.href = data.bot_url || '#';

        setTelegramLinkMeta('Abra o bot, envie o codigo e aguarde a confirmacao.', '');
        renderTelegramQr(data);
    }

    async function loadTelegramStatus(options = {}) {
        const { preservePending = false, showToast = false } = options;
        const statusEl = getEl('telegram-status');
        if (!statusEl) {
            return;
        }

        try {
            const response = await apiGet(endpoints.telegramStatus);
            const data = response?.data || {};

            if (data.linked) {
                telegramLinkPending = false;
                clearTelegramLinkTimers();
                statusEl.innerHTML = '<span class="status-indicator linked"></span><span class="status-text">Vinculado</span>';

                setDisplay('telegram-not-linked', 'none');
                setDisplay('telegram-code-generated', 'none');
                setDisplay('telegram-linked', '');

                const linkedHandle = getEl('telegram-linked-handle');
                if (linkedHandle) {
                    linkedHandle.textContent = data.username ? `Chat ${data.username}` : 'Pronto para uso';
                }

                setTelegramLinkMeta('', '');
                renderTelegramQr();

                if (showToast && !telegramLinkSuccessToastShown && window.Swal) {
                    telegramLinkSuccessToastShown = true;
                    Swal.fire({
                        icon: 'success',
                        title: 'Telegram vinculado',
                        text: 'Agora voce pode usar o bot normalmente.',
                        confirmButtonColor: '#0ea5e9',
                        timer: 2500,
                        timerProgressBar: true,
                    });
                }
                return;
            }

            if (preservePending && telegramLinkPending) {
                statusEl.innerHTML = '<span class="status-indicator pending"></span><span class="status-text">Aguardando confirmacao</span>';
                setDisplay('telegram-not-linked', 'none');
                setDisplay('telegram-code-generated', '');
                setDisplay('telegram-linked', 'none');
                return;
            }

            telegramLinkPending = false;
            clearTelegramLinkTimers();
            statusEl.innerHTML = '<span class="status-indicator not-linked"></span><span class="status-text">Nao vinculado</span>';
            setDisplay('telegram-not-linked', '');
            setDisplay('telegram-code-generated', 'none');
            setDisplay('telegram-linked', 'none');

            const linkedHandle = getEl('telegram-linked-handle');
            if (linkedHandle) {
                linkedHandle.textContent = '';
            }

            setTelegramLinkMeta('', '');
            renderTelegramQr();
        } catch {
            // status loading errors stay silent by design
        }
    }

    function startTelegramLinkTracking(expiresIn = 600) {
        clearTelegramLinkTimers();
        telegramLinkExpiresAt = Date.now() + Math.max(1, expiresIn) * 1000;

        const updateCountdown = () => {
            const remainingMs = telegramLinkExpiresAt - Date.now();
            if (remainingMs <= 0) {
                clearTelegramLinkTimers();
                telegramLinkPending = false;
                setTelegramLinkMeta('Codigo expirado. Gere um novo para continuar.', '');

                const statusEl = getEl('telegram-status');
                if (statusEl) {
                    statusEl.innerHTML = '<span class="status-indicator not-linked"></span><span class="status-text">Codigo expirado</span>';
                }
                return;
            }

            const totalSeconds = Math.ceil(remainingMs / 1000);
            const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            setTelegramLinkMeta('Abra o bot, envie o codigo e aguarde a confirmacao.', `Expira em ${minutes}:${seconds}`);
        };

        updateCountdown();
        telegramLinkCountdownTimer = window.setInterval(updateCountdown, 1000);
        telegramLinkPollTimer = window.setInterval(() => {
            void loadTelegramStatus({ preservePending: true, showToast: true });
        }, 5000);
    }

    getEl('btn-telegram-link')?.addEventListener('click', async () => {
        const button = getEl('btn-telegram-link');
        const regenerateButton = getEl('btn-telegram-regenerate');
        if (button) button.disabled = true;
        if (regenerateButton) regenerateButton.disabled = true;

        try {
            const response = await apiFetch(endpoints.telegramLink, {
                method: 'POST',
                body: buildIntegrationFormData(),
            });

            if (response?.success) {
                const data = response.data || {};
                renderTelegramAwaitingState(data);
                startTelegramLinkTracking(data.expires_in || 600);
                return;
            }

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage({ data: response }, 'Erro desconhecido'),
                    confirmButtonColor: '#e67e22',
                });
            }
        } catch (error) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage(error, 'Erro de conexao'),
                    confirmButtonColor: '#e67e22',
                });
            }
        } finally {
            if (button) button.disabled = false;
            if (regenerateButton) regenerateButton.disabled = false;
        }
    });

    getEl('btn-telegram-regenerate')?.addEventListener('click', () => {
        getEl('btn-telegram-link')?.click();
    });

    getEl('btn-copy-telegram-code')?.addEventListener('click', () => {
        const input = getEl('telegram-code-display');
        const button = getEl('btn-copy-telegram-code');
        if (!input?.value || !button) {
            return;
        }

        const originalIcon = button.innerHTML;
        navigator.clipboard.writeText(input.value).then(() => {
            button.innerHTML = '<i data-lucide="check"></i>';
            button.style.color = '#22c55e';
            window.lucide?.createIcons?.();
            window.setTimeout(() => {
                button.innerHTML = originalIcon;
                button.style.color = '';
                window.lucide?.createIcons?.();
            }, 2000);
        }).catch(() => {
            input.select();
            document.execCommand('copy');
        });
    });

    getEl('btn-telegram-unlink')?.addEventListener('click', async () => {
        const confirmation = window.Swal
            ? await Swal.fire({
                icon: 'warning',
                title: 'Desvincular Telegram?',
                text: 'Voce nao podera mais enviar lancamentos pelo Telegram.',
                showCancelButton: true,
                confirmButtonText: 'Desvincular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
            })
            : { isConfirmed: window.confirm('Desvincular Telegram?') };

        if (!confirmation.isConfirmed) {
            return;
        }

        try {
            const response = await apiFetch(endpoints.telegramUnlink, {
                method: 'POST',
                body: buildIntegrationFormData(),
            });

            if (response?.success) {
                telegramLinkPending = false;
                clearTelegramLinkTimers();
                void loadTelegramStatus();
            }
        } catch {
            // silent by design
        }
    });

    if (getEl('whatsapp-card')) {
        void loadWhatsAppStatus();
    }

    if (getEl('telegram-card')) {
        void loadTelegramStatus();
    }
}
