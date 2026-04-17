/**
 * ============================================================================
 * LUKRATO - Avatar Global (Navbar + Sidebar)
 * ============================================================================
 * Aplica o avatar do usuário nos elementos globais e expõe
 * window.__LK_updateGlobalAvatars() para atualizacao em tempo real.
 * ============================================================================
 */

import {
    applyRuntimeConfig,
    ensureRuntimeConfig,
    getRuntimeConfig,
    onRuntimeConfigUpdate,
} from './runtime-config.js';

(() => {
    'use strict';

    const topNavAvatar = document.getElementById('topNavAvatar');
    const sidebarAvatar = document.getElementById('sidebarAvatar');

    function applyAvatar(container, avatarUrl, size) {
        if (!container) return;

        let img = container.querySelector('.avatar-global-img');
        const initials = container.querySelector('[class*="avatar-initials"]');

        if (avatarUrl) {
            if (!img) {
                img = document.createElement('img');
                img.className = 'avatar-global-img';
                img.alt = 'Foto de perfil';
                img.width = size;
                img.height = size;
                img.addEventListener('error', () => {
                    img.style.display = 'none';
                    if (initials) initials.style.display = '';
                });
                container.appendChild(img);
            }

            img.src = avatarUrl;
            img.style.display = 'block';

            if (initials) initials.style.display = 'none';
            return;
        }

        if (img) {
            img.style.display = 'none';
            img.removeAttribute('src');
        }

        if (initials) initials.style.display = '';
    }

    function updateGlobalAvatars(avatarUrl, { syncRuntime = true } = {}) {
        applyAvatar(topNavAvatar, avatarUrl, 32);
        applyAvatar(sidebarAvatar, avatarUrl, 28);

        if (syncRuntime) {
            applyRuntimeConfig({ userAvatar: avatarUrl || '' }, {
                dispatch: false,
                source: 'avatar-global',
            });
        }
    }

    window.__LK_updateGlobalAvatars = (avatarUrl) => {
        updateGlobalAvatars(avatarUrl);
    };

    onRuntimeConfigUpdate((config) => {
        updateGlobalAvatars(String(config.userAvatar || ''), { syncRuntime: false });
    });

    updateGlobalAvatars(String(getRuntimeConfig().userAvatar || ''), { syncRuntime: false });
    void ensureRuntimeConfig({}, { silent: true });
})();
