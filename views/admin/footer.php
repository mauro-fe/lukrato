<footer class="footer py-5">
    <div class="container">
        <div class="row">
            <div class="col-8 mx-auto text-center mt-1">
                <p class="mb-0">
                    Todos os direitos reservados © <span id="currentYear"></span><br> Desenvolvido por <b>Mauro
                        Felix</b>
                </p>
            </div>
        </div>
    </div>
</footer>
<script src="<?= BASE_URL ?>assets/js/core/popper.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/core/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
document.getElementById('currentYear').textContent = new Date().getFullYear();
// --- Fim da substituição ---

var win = navigator.platform.indexOf('Win') > -1;
if (win && document.querySelector('#sidenav-scrollbar')) {
    var options = {
        damping: '0.5'
    }
    Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
}
</script>
<script async defer src="https://buttons.github.io/buttons.js"></script>

<script src="<?= BASE_URL ?>assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>

<?php loadPageJs(); ?>

</body>

</html>