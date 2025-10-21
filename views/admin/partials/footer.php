</main>
</div>
<?php loadPageCss('admin-partials-footer'); ?>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-8 mx-auto text-center mt-1">
                <span>&copy; 2025 Lukrato -</span>
                <span>Desenvolvido por <a href="https://github.com/mauro-fe" target="_blank" rel="noopener">Mauro
                        Felix</a></span>
                <span> & <a href="https://github.com/Joseph-0505" target="_blank" rel="noopener"> Joseph
                        Victor</a></span>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.9/dist/inputmask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/just-validate@4.3.0/dist/just-validate.production.min.js"></script>

<script src="<?= BASE_URL ?>assets/js/core/popper.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/core/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script async defer src="https://buttons.github.io/buttons.js"></script>

<script src="<?= BASE_URL ?>assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>

<?php loadPageJs(); ?>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    AOS.init({
        offset: 120,
        delay: 0,
        duration: 1000,
        easing: 'ease',
        once: false,
        mirror: true,
        anchorPlacement: 'top-bottom',
        startEvent: 'DOMContentLoaded',
        disable: false,
        debounceDelay: 50,
        throttleDelay: 99,
        useClassNames: false,
        disableMutationObserver: false,
        animatedClassName: 'aos-animate',
        initClassName: 'aos-init'
    });
</script>
</body>

</html>