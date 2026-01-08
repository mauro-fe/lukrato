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
</script>
</body>

</html>