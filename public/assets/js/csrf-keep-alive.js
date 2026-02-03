/**
 * CSRF Keep-Alive
 * Mantém o token CSRF atualizado automaticamente
 * Token expira em 60 minutos (3600s), renovamos a cada 10 minutos para segurança
 */

(function () {
    'use strict';

    const REFRESH_INTERVAL = 10 * 60 * 1000; // 10 minutos (token expira em 20)

    /**
     * Atualiza todos os inputs hidden de CSRF na página
     */
    function updateAllCsrfInputs(token) {
        // Atualiza inputs com name csrf_token ou _token
        document.querySelectorAll('input[name="csrf_token"], input[name="_token"]').forEach(input => {
            input.value = token;
        });

        // Atualiza inputs com data-csrf-id
        document.querySelectorAll('[data-csrf-id]').forEach(el => {
            if (el.tagName === 'INPUT') {
                el.value = token;
            } else if (el.tagName === 'META') {
                el.setAttribute('content', token);
            }
        });
    }

    /**
     * Renova o token CSRF via API
     */
    function refreshCsrfToken() {
        const base = document.querySelector('meta[name="base-url"]')?.content || '/';
        const tokenId = document.querySelector('meta[name="csrf-token-id"]')?.content || 'default';

        fetch(`${base}api/csrf/refresh`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ token_id: tokenId })
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

                    // Atualizar CsrfManager se disponível
                    if (window.CsrfManager && typeof window.CsrfManager.applyToken === 'function') {
                        window.CsrfManager.applyToken(data.token, data.ttl);
                    }

                    // Atualizar todos os inputs hidden
                    updateAllCsrfInputs(data.token);

                    console.debug('[CSRF Keep-Alive] Token renovado com sucesso');
                }
            })
            .catch(err => {
                console.warn('[CSRF Keep-Alive] Erro ao atualizar CSRF:', err);
            });
    }

    // Agendar atualização periódica
    setInterval(refreshCsrfToken, REFRESH_INTERVAL);

    // Renovar também quando a página volta a ficar visível (usuário voltou à aba)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            // Aguardar um pouco para evitar múltiplas requisições
            setTimeout(refreshCsrfToken, 1000);
        }
    });

    // Expor função globalmente para uso manual
    window.refreshCsrfToken = refreshCsrfToken;

})();
