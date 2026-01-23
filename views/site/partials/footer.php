<?php
$extraJs = $extraJs ?? [];
?>

</main>

<!-- Footer Moderno -->
<footer class="lk-footer">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">

            <!-- Marca -->
            <div class="space-y-4">
                <a href="<?= BASE_URL ?>/"
                    class="inline-block hover:opacity-90 transition-all duration-300 hover:scale-105">
                    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato" class="h-14" loading="lazy">
                </a>
                <p class="text-white/70 leading-relaxed">
                    Controle financeiro simples, inteligente e sem complicação.
                </p>
                <div class="lk-footer-icons flex gap-3 pt-2">
                    <a href="#" aria-label="Facebook"
                        class="w-11 h-11 bg-white rounded-xl flex items-center justify-center">
                        <i class="fa-brands fa-facebook-f text-lg"></i>
                    </a>
                    <a href="#" aria-label="Instagram"
                        class="w-11 h-11 bg-white rounded-xl flex items-center justify-center">
                        <i class="fa-brands fa-instagram text-lg"></i>
                    </a>
                    <a href="#" aria-label="LinkedIn"
                        class="w-11 h-11 bg-white rounded-xl flex items-center justify-center">
                        <i class="fa-brands fa-linkedin-in text-lg"></i>
                    </a>
                </div>
            </div>

            <!-- Produto -->
            <div class="lk-footer-links">
                <h3 class="font-bold text-lg mb-5 text-white">Produto</h3>
                <nav class="flex flex-col gap-3">
                    <a href="<?= BASE_URL ?>/#funcionalidades" class="text-white/70">
                        <span>Funcionalidades</span>
                    </a>
                    <a href="<?= BASE_URL ?>/#beneficios" class="text-white/70">
                        <span>Benefícios</span>
                    </a>
                    <a href="<?= BASE_URL ?>/#planos" class="text-white/70">
                        <span>Planos</span>
                    </a>
                    <a href="<?= BASE_URL ?>login" class="text-white/70">
                        <span>Entrar</span>
                    </a>
                </nav>
            </div>

            <!-- Legal -->
            <div class="lk-footer-links">
                <h3 class="font-bold text-lg mb-5 text-white">Legal</h3>
                <nav class="flex flex-col gap-3">
                    <a href="<?= BASE_URL ?>termos" class="text-white/70">
                        <span>Termos de Uso</span>
                    </a>
                    <a href="<?= BASE_URL ?>privacidade" class="text-white/70">
                        <span>Política de Privacidade</span>
                    </a>
                    <a href="<?= BASE_URL ?>lgpd" class="text-white/70">
                        <span>LGPD e proteção de dados</span>
                    </a>
                </nav>
            </div>

            <!-- Contato -->
            <div class="lk-footer-links">
                <h3 class="font-bold text-lg mb-5 text-white">Contato</h3>
                <div class="flex flex-col gap-4">
                    <a href="https://wa.me/5544999506302" class="inline-flex items-center gap-3 text-white/70 group">
                        <span
                            class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center transition-all duration-300 group-hover:bg-green-500/20">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                        </span>
                        <span>WhatsApp comercial</span>
                    </a>
                    <a href="mailto:lukratosistema@gmail.com"
                        class="inline-flex items-center gap-3 text-white/70 group">
                        <span
                            class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center transition-all duration-300 group-hover:bg-orange-500/20">
                            <i class="fa-regular fa-envelope text-lg"></i>
                        </span>
                        <span class="break-all">lukratosistema@gmail.com</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- Bottom -->
        <div class="pt-8 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-white/50 text-sm">
                © <?= date('Y') ?> Lukrato. Todos os direitos reservados.
            </p>
            <p class="text-white/40 text-sm italic flex items-center gap-2">
                <span>Feito com</span>
                <i class="fas fa-heart text-red-400 animate-pulse"></i>
                <span>para quem quer cuidar melhor do próprio dinheiro.</span>
            </p>
        </div>
    </div>
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