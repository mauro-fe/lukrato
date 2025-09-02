</main>

<style>
    :root {
        --bg-1: #0b141c;
        --text: #e8edf3;
        --muted: #a9b7c5
    }

    footer {
        background: var(--bg-1);
    }

    span {
        color: var(--muted);
    }

    a {
        color: var(--text);
    }
</style>
<footer class="footer py-5">
    <div class="container">
        <div class="row">
            <div class="col-8 mx-auto text-center mt-1">
                <span>© 2025 Lukrato</span>
                <span>Desenvolvido por <a href="https://github.com/mauro-fe" target="_blank" rel="noopener">Mauro Felix
                    </a></span>
                <span> & <a href="https://github.com/Joseph-0505" target="_blank" rel="noopener"> José Victor</a></span>
            </div>
        </div>
    </div>
</footer>
<script src="<?= BASE_URL ?>assets/js/core/popper.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/core/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

</script>
<script async defer src="https://buttons.github.io/buttons.js"></script>

<script src="<?= BASE_URL ?>assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>

<?php loadPageJs(); ?>

</body>

</html>