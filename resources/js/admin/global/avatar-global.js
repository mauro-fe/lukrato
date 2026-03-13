/**
 * ============================================================================
 * LUKRATO — Avatar Global (Navbar + Sidebar)
 * ============================================================================
 * Aplica o avatar do usuário nos elementos globais (top-navbar e sidebar).
 * Expõe window.__LK_updateGlobalAvatars() para que a página de perfil
 * possa atualizar os avatares em tempo real após upload/remoção.
 * ============================================================================
 */

(() => {
    'use strict';

    const topNavAvatar = document.getElementById('topNavAvatar');
    const sidebarAvatar = document.getElementById('sidebarAvatar');

    /**
     * Atualiza avatar em um container (navbar ou sidebar).
     * Se avatarUrl existir, mostra <img>. Senão, mostra iniciais.
     */
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
                container.appendChild(img);
            }
            img.src = avatarUrl;
            img.style.display = 'block';
            if (initials) initials.style.display = 'none';
        } else {
            if (img) {
                img.style.display = 'none';
                img.src = '';
            }
            if (initials) initials.style.display = '';
        }
    }

    /**
     * Atualiza ambos os avatares globais.
     */
    function updateGlobalAvatars(avatarUrl) {
        applyAvatar(topNavAvatar, avatarUrl, 32);
        applyAvatar(sidebarAvatar, avatarUrl, 28);
    }

    // Expor para uso pelo perfil/index.js
    window.__LK_updateGlobalAvatars = updateGlobalAvatars;

    // Aplicar avatar inicial via __LK_CONFIG
    const cfg = window.__LK_CONFIG || {};
    if (cfg.userAvatar) {
        updateGlobalAvatars(cfg.userAvatar);
    }
})();
