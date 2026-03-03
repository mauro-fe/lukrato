/**
 * Cookie Consent — Banner with accept/decline, persists in localStorage
 */

export function init() {
    const COOKIE_KEY = 'lk_cookie_consent';

    // Already accepted/declined
    if (localStorage.getItem(COOKIE_KEY)) return;

    const baseUrl = (window.APP_BASE_URL || '') + '/';

    // Show after a small delay
    setTimeout(function () {
        const banner = document.createElement('div');
        banner.id = 'cookieConsent';
        banner.innerHTML = `
            <style>
                @keyframes cookieSlideIn {
                    from { opacity: 0; transform: translateY(20px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                #cookieBannerInner {
                    position: fixed;
                    bottom: 16px;
                    left: 16px;
                    right: 16px;
                    max-width: 380px;
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0,0,0,.15), 0 0 0 1px rgba(0,0,0,.05);
                    padding: 16px;
                    z-index: 9999;
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                    animation: cookieSlideIn .4s ease-out;
                }
                @media (min-width: 480px) {
                    #cookieBannerInner { right: auto; padding: 20px; }
                }
                #cookieBannerInner .cookie-content { display: flex; align-items: flex-start; gap: 12px; }
                #cookieBannerInner .cookie-icon {
                    width: 36px; height: 36px;
                    background: linear-gradient(135deg, #f97316, #ea580c);
                    border-radius: 10px;
                    display: flex; align-items: center; justify-content: center;
                    flex-shrink: 0; font-size: 18px;
                }
                @media (min-width: 480px) {
                    #cookieBannerInner .cookie-icon { width: 40px; height: 40px; font-size: 20px; }
                }
                #cookieBannerInner .cookie-text { flex: 1; min-width: 0; }
                #cookieBannerInner h4 { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #1e293b; }
                @media (min-width: 480px) { #cookieBannerInner h4 { font-size: 15px; margin-bottom: 6px; } }
                #cookieBannerInner p { margin: 0 0 12px; font-size: 12px; color: #64748b; line-height: 1.5; }
                @media (min-width: 480px) { #cookieBannerInner p { font-size: 13px; margin-bottom: 14px; } }
                #cookieBannerInner a { color: #f97316; text-decoration: underline; }
                #cookieBannerInner .cookie-buttons { display: flex; gap: 8px; }
                #cookieBannerInner .btn-accept {
                    flex: 1; padding: 10px 14px;
                    background: linear-gradient(135deg, #f97316, #ea580c);
                    color: white; border: none; border-radius: 8px;
                    font-size: 13px; font-weight: 600; cursor: pointer;
                    transition: all .2s ease;
                }
                #cookieBannerInner .btn-accept:hover { transform: scale(1.02); }
                #cookieBannerInner .btn-decline {
                    padding: 10px 14px; background: #f1f5f9; color: #64748b;
                    border: none; border-radius: 8px; font-size: 13px; font-weight: 500;
                    cursor: pointer; transition: all .2s ease;
                }
                #cookieBannerInner .btn-decline:hover { background: #e2e8f0; }
            </style>
            <div id="cookieBannerInner">
                <div class="cookie-content">
                    <div class="cookie-icon">
                        <i data-lucide="cookie" class="w-6 h-6"></i>
                    </div>
                    <div class="cookie-text">
                        <h4>Usamos cookies</h4>
                        <p>
                            Para melhorar sua experiência e analisar o uso do site.
                            <a href="${baseUrl}politica-de-privacidade">Saiba mais</a>
                        </p>
                        <div class="cookie-buttons">
                            <button id="cookieAccept" class="btn-accept">Aceitar</button>
                            <button id="cookieDecline" class="btn-decline">Recusar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(banner);

        // Render icon
        if (window.lucide) lucide.createIcons();

        document.getElementById('cookieAccept').addEventListener('click', function () {
            localStorage.setItem(COOKIE_KEY, 'accepted');
            closeBanner();
        });

        document.getElementById('cookieDecline').addEventListener('click', function () {
            localStorage.setItem(COOKIE_KEY, 'declined');
            closeBanner();
        });

        function closeBanner() {
            const el = document.getElementById('cookieBannerInner');
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all .3s ease';
                setTimeout(() => {
                    const wrapper = document.getElementById('cookieConsent');
                    if (wrapper) wrapper.remove();
                }, 300);
            }
        }
    }, 1500);
}
