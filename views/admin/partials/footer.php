</main>
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>

<!-- Botão Scroll to Top -->
<button id="scrollToTopBtn" class="scroll-to-top" aria-label="Voltar ao topo" title="Voltar ao topo">
    <i data-lucide="arrow-up"></i>
</button>

<footer class="footer">
    <div class="footer-content">
        <p class="footer-copyright">&copy; <?= date('Y') ?> Lukrato - Suas finanças sob controle</p>
    </div>
</footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.9/dist/inputmask.min.js">
</script>
<script src="https://cdn.jsdelivr.net/npm/just-validate@4.3.0/dist/just-validate.production.min.js"></script>
<?= vite_scripts('admin/lancamento-global/index.js') ?>
<script src="<?= BASE_URL ?>assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/smooth-scrollbar.min.js"></script>

<?php loadPageJs(); ?>

</body>

</html>
