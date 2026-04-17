import { apiFetch, getErrorMessage } from '../../../admin/shared/api.js';
import {
    resolveGoogleCancelEndpoint,
    resolveGoogleConfirmEndpoint,
    resolveGooglePendingEndpoint,
} from '../../api/endpoints/auth.js';

function findElements(root) {
    return {
        avatar: root.querySelector('[data-google-user-avatar]'),
        avatarFallback: root.querySelector('[data-google-user-avatar-fallback]'),
        name: root.querySelector('[data-google-user-name]'),
        email: root.querySelector('[data-google-user-email]'),
        status: root.querySelector('[data-google-confirm-status]'),
        actionControls: Array.from(root.querySelectorAll('[data-google-action]')),
    };
}

function createPageState(root) {
    return {
        loginUrl: root.dataset.loginUrl || '/login',
        pendingUrl: resolveGooglePendingEndpoint(),
        confirmUrl: resolveGoogleConfirmEndpoint(),
        cancelUrl: resolveGoogleCancelEndpoint(),
    };
}

function setStatus(elements, message, isError = false) {
    if (!elements.status) {
        return;
    }

    if (!message) {
        elements.status.hidden = true;
        elements.status.textContent = '';
        elements.status.classList.remove('is-error');
        return;
    }

    elements.status.hidden = false;
    elements.status.textContent = message;
    elements.status.classList.toggle('is-error', isError);
}

function setBusy(elements, busy) {
    elements.actionControls.forEach((control) => {
        control.classList.toggle('is-busy', busy);

        if ('disabled' in control) {
            control.disabled = busy;
        }

        if (busy) {
            control.setAttribute('aria-disabled', 'true');
            return;
        }

        control.removeAttribute('aria-disabled');
    });
}

function renderPendingUser(elements, pendingUser) {
    const name = pendingUser?.name || 'Conta Google';
    const email = pendingUser?.email || 'Email indisponivel';
    const picture = pendingUser?.picture || '';

    if (elements.name) {
        elements.name.textContent = name;
    }

    if (elements.email) {
        elements.email.textContent = email;
    }

    if (elements.avatarFallback) {
        elements.avatarFallback.textContent = name.trim().charAt(0) || 'G';
    }

    if (!elements.avatar) {
        return;
    }

    if (picture) {
        elements.avatar.src = picture;
        elements.avatar.hidden = false;
        elements.avatarFallback?.setAttribute('hidden', 'hidden');
        return;
    }

    elements.avatar.hidden = true;
    elements.avatar.removeAttribute('src');
    elements.avatarFallback?.removeAttribute('hidden');
}

async function requestJson(url) {
    const response = await apiFetch(url, { method: 'GET' }, { suppressErrorLogging: true });
    return response?.data ?? response;
}

function syncActionUrls(pageState, payload) {
    const actions = payload?.actions && typeof payload.actions === 'object'
        ? payload.actions
        : {};
    const confirmUrl = typeof actions.confirm_url === 'string' ? actions.confirm_url.trim() : '';
    const cancelUrl = typeof actions.cancel_url === 'string' ? actions.cancel_url.trim() : '';

    if (confirmUrl !== '') {
        pageState.confirmUrl = confirmUrl;
    }

    if (cancelUrl !== '') {
        pageState.cancelUrl = cancelUrl;
    }
}

function getRedirectFromError(error) {
    return error?.data?.errors?.redirect || error?.data?.redirect || null;
}

async function hydratePendingUser(pageState, elements) {
    try {
        const payload = await requestJson(pageState.pendingUrl);
        if (!payload?.pending_user) {
            throw new Error('Cadastro Google pendente nao encontrado.');
        }

        syncActionUrls(pageState, payload);
        renderPendingUser(elements, payload.pending_user);
        setStatus(elements, '');
    } catch (error) {
        setStatus(elements, getErrorMessage(error, 'Nao foi possivel carregar os dados da sua conta Google.'), true);
        window.setTimeout(() => {
            window.location.href = pageState.loginUrl;
        }, 1500);
    }
}

async function handleAction(pageState, elements, event) {
    event.preventDefault();

    const control = event.currentTarget;
    if (!(control instanceof HTMLElement) || control.getAttribute('aria-disabled') === 'true') {
        return;
    }

    const action = control.dataset.googleAction || 'confirm';
    const endpoint = action === 'confirm'
        ? pageState.confirmUrl
        : pageState.cancelUrl;
    const href = endpoint || pageState.loginUrl;

    setBusy(elements, true);
    setStatus(
        elements,
        action === 'confirm'
            ? 'Criando sua conta com Google...'
            : 'Cancelando o cadastro pendente...'
    );

    try {
        const payload = await requestJson(href);
        window.location.href = payload?.redirect || pageState.loginUrl;
    } catch (error) {
        setBusy(elements, false);
        setStatus(
            elements,
            getErrorMessage(
                error,
                action === 'confirm'
                    ? 'Nao foi possivel concluir o cadastro com Google.'
                    : 'Nao foi possivel cancelar o cadastro com Google.'
            ),
            true
        );

        const redirect = getRedirectFromError(error);
        if (redirect) {
            window.setTimeout(() => {
                window.location.href = redirect;
            }, 1500);
        }
    }
}

function bootstrapGoogleConfirmPage() {
    const root = document.querySelector('[data-google-confirm-root]');
    if (!root) {
        return;
    }

    const pageState = createPageState(root);
    const elements = findElements(root);
    elements.actionControls.forEach((control) => {
        control.addEventListener('click', (event) => {
            handleAction(pageState, elements, event);
        });
    });

    hydratePendingUser(pageState, elements);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapGoogleConfirmPage, { once: true });
} else {
    bootstrapGoogleConfirmPage();
}