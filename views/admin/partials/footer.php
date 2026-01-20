</main>
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>

<!-- Botão Scroll to Top -->
<button id="scrollToTopBtn" class="scroll-to-top" aria-label="Voltar ao topo" title="Voltar ao topo">
    <i class="fas fa-arrow-up"></i>
</button>

<?php loadPageCss('admin-partials-footer'); ?>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-12 mx-auto text-center">
                <div class="footer-content">
                    <div class="footer-copyright">&copy; 2025 Lukrato</div>
                    <div class="footer-dev-label">Desenvolvido por</div>
                    <div class="footer-developers">
                        <a href="https://www.linkedin.com/in/mauro-felix-846a08268/" target="_blank"
                            rel="noopener">Mauro Felix</a>
                        <span class="footer-separator">&</span>
                        <a href="https://www.linkedin.com/in/jose-victor-75b4322a5/" target="_blank" rel="noopener">José
                            Victor</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.9/dist/inputmask.min.js">
</script>
<script src="https://cdn.jsdelivr.net/npm/just-validate@4.3.0/dist/just-validate.production.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/lancamento-global.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>assets/js/core/popper.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/core/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script async defer src="https://buttons.github.io/buttons.js"></script>

<script src="<?= BASE_URL ?>assets/js/soft-ui-dashboard.js?v=1.1.2"></script>

<?php loadPageJs(); ?>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    // Ajusta o AOS no mobile para reduzir o atraso sem desabilitar as animações.
    const isMobile = window.matchMedia('(max-width: 767px)').matches;
    const aosOptions = {
        offset: isMobile ? 50 : 120,
        delay: 0,
        duration: isMobile ? 500 : 1000,
        easing: 'ease',
        once: true,
        mirror: false,
        anchorPlacement: 'top-bottom',
        startEvent: 'DOMContentLoaded',
        disable: false,
        debounceDelay: 50,
        throttleDelay: 99,
        useClassNames: false,
        disableMutationObserver: false,
        animatedClassName: 'aos-animate',
        initClassName: 'aos-init'
    };

    AOS.init(aosOptions);

    // Garante recálculo das posições após o carregamento completo no mobile.
    window.addEventListener('load', () => AOS.refresh());

    // Scroll to Top Button
    (() => {
        const scrollBtn = document.getElementById('scrollToTopBtn');
        if (!scrollBtn) {
            console.warn('Botão scrollToTopBtn não encontrado');
            return;
        }

        const toggleScrollButton = () => {
            if (window.scrollY > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        };

        scrollBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        window.addEventListener('scroll', toggleScrollButton);
        toggleScrollButton();
    })();
</script>

<style>
    /* Botão Scroll to Top - Estilo Final Override */
    button#scrollToTopBtn {
        position: fixed !important;
        bottom: 100px !important;
        right: 20px !important;
        left: auto !important;
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        min-height: 48px !important;
        border-radius: 100% !important;
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%) !important;
        border: none !important;
        color: #fff !important;
        cursor: pointer !important;
        z-index: 9998 !important;
        box-shadow: 0 4px 16px rgba(52, 152, 219, 0.3) !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 18px !important;
        opacity: 0 !important;
        visibility: hidden !important;
        transform: translateY(20px) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        overflow: visible !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    button#scrollToTopBtn::before {
        content: '' !important;
        position: absolute !important;
        inset: -4px !important;
        border-radius: 100% !important;
        background: linear-gradient(135deg, #3498db, #2980b9) !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
        z-index: -1 !important;
        filter: blur(12px) !important;
    }

    button#scrollToTopBtn.show {
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
        display: flex !important;
    }

    button#scrollToTopBtn.show::before {
        opacity: 0.5 !important;
    }

    button#scrollToTopBtn:hover {
        transform: translateY(-4px) scale(1.05) !important;
        box-shadow: 0 5px 20px rgba(52, 152, 219, 0.45) !important;
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%) !important;
    }

    button#scrollToTopBtn:hover::before {
        opacity: 0.6 !important;
        animation: glow-pulse 2s ease-in-out infinite !important;
    }

    button#scrollToTopBtn:active {
        transform: translateY(-2px) scale(1.02) !important;
    }

    button#scrollToTopBtn i {
        font-size: 18px !important;
    }

    @keyframes glow-pulse {

        0%,
        100% {
            opacity: 0.4;
        }

        50% {
            opacity: 0.8;
        }
    }

    /* Ocultar no desktop */
    @media (min-width: 769px) {
        button#scrollToTopBtn {
            display: none !important;
        }
    }

    /* Mostrar no mobile */
    @media (max-width: 768px) {
        button#scrollToTopBtn {
            display: flex !important;
        }
    }
</style>

</body>

</html>