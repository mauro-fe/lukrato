/**
 * CSRF Keep-Alive
 * Mantém o token CSRF atualizado automaticamente
 */

(function() {
    'use strict';

    const REFRESH_INTERVAL = 25 * 60 * 1000; // 25 minutos

    function refreshCsrfToken() {
        const base = document.querySelector('meta[name="base-url"]')?.content || '/';
        
        fetch(`${base}api/csrf/refresh`, {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            if (data.token) {
                // Atualizar meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.content = data.token;
                }
                
                // Atualizar namespace global
                if (window.LK) {
                    window.LK.csrfToken = data.token;
                }
                
                console.log('✓ CSRF token atualizado');
            }
        })
        .catch(err => {
            console.warn('Erro ao atualizar CSRF:', err);
        });
    }

    // Agendar atualização periódica
    setInterval(refreshCsrfToken, REFRESH_INTERVAL);
    
    console.log('✓ CSRF Keep-Alive iniciado');
})();
