<?php
$extraJs = $extraJs ?? [];
?>

</main>

<footer class="lk-footer">
    <div class="lk-footer-container">

        <!-- Marca -->
        <div class="lk-footer-brand">

            <class="lk-footer-logo">
                <a href="<?= BASE_URL ?>/" class="lk-site-logo">
                    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato" loading="lazy">
                </a>
                <p>
                    Controle financeiro simples, inteligente e sem complicação.
                </p>
        </div>

        <!-- Links -->
        <nav class="lk-footer-links">
            <strong>Produto</strong>
            <a href="<?= BASE_URL ?>/#funcionalidades">Funcionalidades</a>
            <a href="<?= BASE_URL ?>/#beneficios">Benefícios</a>
            <a href="<?= BASE_URL ?>/#planos">Planos</a>
            <a href="<?= BASE_URL ?>login">Entrar</a>
        </nav>

        <!-- Legal -->
        <nav class="lk-footer-links">
            <strong>Legal</strong>
            <a href="<?= BASE_URL ?>termos">Termos de Uso</a>
            <a href="<?= BASE_URL ?>privacidade">Política de Privacidade</a>
            <a href="<?= BASE_URL ?>lgpd">LGPD e proteção de dados</a>
        </nav>

        <!-- Contato -->
        <div class="lk-footer-contact">
            <strong>Contato</strong>
            <a href="https://wa.me/5544999506302">WhatsApp comercial</a>
            <a href="mailto:lukratosistema@gmail.com">lukratosistema@gmail.com</a>

        </div>

    </div>

    <div class="lk-footer-bottom">
        © 2025 Lukrato. Todos os direitos reservados.
    </div>
    <p class="lk-footer-note">
        Feito para quem quer cuidar melhor do próprio dinheiro.
    </p>

</footer>


<!-- JS global da landing (menu mobile, scroll suave, etc.) -->
<script src="<?= BASE_URL ?>/assets/js/csrf-manager.js"></script>
<script src="<?= BASE_URL ?>/assets/js/site/landing-base.js"></script>

<!-- JS específicos da página -->
<?php foreach ($extraJs as $js): ?>
    <script src="<?= BASE_URL ?>/assets/js/site/<?= htmlspecialchars($js) ?>.js"></script>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.APP_BASE_URL = "<?= rtrim(BASE_URL, '/') ?>";
</script>


</body>

</html>