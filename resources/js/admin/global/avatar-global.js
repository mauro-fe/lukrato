/**
 * ============================================================================
 * LUKRATO - Avatar Global (Navbar + Sidebar)
 * ============================================================================
 * Aplica o avatar do usuario nos elementos globais e expoe
 * window.__LK_updateGlobalAvatars() para atualizacao em tempo real.
 * ============================================================================
 */

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

    function updateGlobalAvatars(avatarUrl) {
        applyAvatar(topNavAvatar, avatarUrl, 32);
        applyAvatar(sidebarAvatar, avatarUrl, 28);
    }

    window.__LK_updateGlobalAvatars = updateGlobalAvatars;

    const cfg = window.__LK_CONFIG || {};
    if (cfg.userAvatar) {
        updateGlobalAvatars(cfg.userAvatar);
    }
})();
