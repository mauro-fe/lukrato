import { apiPost, getErrorMessage } from '../shared/api.js';
import { resolveDisplayNameEndpoint } from '../api/endpoints/preferences.js';
import { applyRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';
import { updateAvatarDisplay } from './profile-common.js';
import { buildDisplayNameSnapshot, resolveDisplayNameValue } from './quick-display-name-state.js';

function setStatus(statusNode, message, tone = 'neutral') {
    if (!statusNode) {
        return;
    }

    statusNode.hidden = message === '';
    statusNode.textContent = message;
    statusNode.dataset.tone = tone;
}

function setBusy(refs, busy) {
    refs.root.dataset.busy = busy ? 'true' : 'false';

    if (refs.input) {
        refs.input.disabled = busy;
    }

    if (refs.saveButton) {
        refs.saveButton.disabled = busy;
    }
}

function syncGreeting(snapshot) {
    document.querySelectorAll('.greeting-name strong').forEach((node) => {
        node.textContent = snapshot.firstName;
    });

    document.querySelectorAll('.avatar-initials-sm, .avatar-initials-xs').forEach((node) => {
        node.textContent = snapshot.initial;
    });
}

function syncDisplayNameAcrossPage(context, refs, displayName) {
    const snapshot = buildDisplayNameSnapshot(displayName);

    if (context.fields.nome) {
        context.fields.nome.value = snapshot.displayName;
    }

    if (refs.input && refs.input.value !== snapshot.displayName) {
        refs.input.value = snapshot.displayName;
    }

    applyRuntimeConfig({
        username: snapshot.displayName,
        needsDisplayNamePrompt: snapshot.displayName === '',
    }, {
        source: 'profile-display-name',
    });

    syncGreeting(snapshot);

    const currentAvatarUrl = context.avatar.image && context.avatar.image.style.display !== 'none'
        ? String(context.avatar.image.getAttribute('src') || '')
        : '';

    updateAvatarDisplay(context, currentAvatarUrl, snapshot.displayName);

    return snapshot;
}

function readSeedDisplayName(context, refs) {
    return resolveDisplayNameValue(
        context.fields.nome?.value,
        refs.input?.value || getRuntimeConfig().username || ''
    );
}

export function initProfileDisplayName(context, profileReadyPromise = null) {
    const root = document.querySelector('[data-profile-display-name-root]');
    if (!root) {
        return null;
    }

    const refs = {
        root,
        input: root.querySelector('[data-role="display-name-input"]'),
        saveButton: root.querySelector('[data-action="save-display-name"]'),
        status: root.querySelector('[data-slot="display-name-status"]'),
    };

    if (!refs.input || !refs.saveButton) {
        return null;
    }

    const endpoint = root.getAttribute('data-display-name-endpoint')
        || context.endpoints?.displayName
        || `${context.BASE}${resolveDisplayNameEndpoint()}`;

    const prime = () => {
        const seededValue = readSeedDisplayName(context, refs);
        syncDisplayNameAcrossPage(context, refs, seededValue);
        setStatus(refs.status, 'Nome sincronizado com o topo e o perfil.', 'neutral');
        setBusy(refs, false);
    };

    const save = async () => {
        const displayName = String(refs.input.value || '').trim();

        setBusy(refs, true);
        setStatus(refs.status, 'Salvando nome de exibição...', 'neutral');

        try {
            const response = await apiPost(endpoint, {
                display_name: displayName,
            });

            if (response?.success === false) {
                throw {
                    data: response,
                    status: response.status,
                };
            }

            const savedDisplayName = resolveDisplayNameValue(response?.data?.display_name, displayName);
            syncDisplayNameAcrossPage(context, refs, savedDisplayName);
            setStatus(refs.status, response?.data?.message || 'Nome de exibição salvo.', 'success');
        } catch (error) {
            setStatus(refs.status, getErrorMessage(error, 'Não foi possível salvar o nome de exibição.'), 'danger');
        } finally {
            setBusy(refs, false);
        }
    };

    refs.saveButton.addEventListener('click', () => {
        void save();
    });

    refs.input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        void save();
    });

    setBusy(refs, true);
    setStatus(refs.status, 'Carregando nome atual...', 'neutral');

    Promise.resolve(profileReadyPromise)
        .catch(() => null)
        .finally(prime);

    return {
        save,
    };
}