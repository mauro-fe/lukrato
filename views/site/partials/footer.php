<?php
$extraJs = $extraJs ?? [];
?>

</main>

<footer class="lk-site-footer">
    <div class="lk-site-footer-inner">
        <p>&copy; <?= date('Y') ?> Lukrato. Todos os direitos reservados.</p>
        <span class="lk-site-footer-badge">Controle financeiro simples e inteligente.</span>
    </div>
</footer>

<!-- JS global da landing (menu mobile, scroll suave, etc.) -->
<script src="<?= BASE_URL ?>/assets/js/site/landing-base.js"></script>

<!-- JS específicos da página -->
<?php foreach ($extraJs as $js): ?>
    <script src="<?= BASE_URL ?>/assets/js/site/<?= htmlspecialchars($js) ?>.js"></script>
<?php endforeach; ?>

</body>

</html>