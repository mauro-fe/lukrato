<?php
$extraJs = $extraJs ?? [];
?>

</main>

<!-- Footer Moderno -->
<footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">

            <!-- Marca -->
            <div class="space-y-4">
                <a href="<?= BASE_URL ?>/" class="inline-block hover:opacity-80 transition-opacity">
                    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato" class="h-8" loading="lazy">
                </a>
                <p class="text-gray-400 leading-relaxed">
                    Controle financeiro simples, inteligente e sem complicação.
                </p>
                <div class="flex gap-3 pt-2">
                    <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition-colors">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition-colors">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition-colors">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                </div>
            </div>

            <!-- Produto -->
            <div>
                <h3 class="font-bold text-lg mb-4">Produto</h3>
                <nav class="flex flex-col gap-3">
                    <a href="<?= BASE_URL ?>/#funcionalidades" class="text-gray-400 hover:text-primary transition-colors">
                        Funcionalidades
                    </a>
                    <a href="<?= BASE_URL ?>/#beneficios" class="text-gray-400 hover:text-white transition-colors">
                        Benefícios
                    </a>
                    <a href="<?= BASE_URL ?>/#planos" class="text-gray-400 hover:text-white transition-colors">
                        Planos
                    </a>
                    <a href="<?= BASE_URL ?>login" class="text-gray-400 hover:text-white transition-colors">
                        Entrar
                    </a>
                </nav>
            </div>

            <!-- Legal -->
            <div>
                <h3 class="font-bold text-lg mb-4">Legal</h3>
                <nav class="flex flex-col gap-3">
                    <a href="<?= BASE_URL ?>termos" class="text-gray-400 hover:text-white transition-colors">
                        Termos de Uso
                    </a>
                    <a href="<?= BASE_URL ?>privacidade" class="text-gray-400 hover:text-white transition-colors">
                        Política de Privacidade
                    </a>
                    <a href="<?= BASE_URL ?>lgpd" class="text-gray-400 hover:text-white transition-colors">
                        LGPD e proteção de dados
                    </a>
                </nav>
            </div>

            <!-- Contato -->
            <div>
                <h3 class="font-bold text-lg mb-4">Contato</h3>
                <div class="flex flex-col gap-3">
                    <a href="https://wa.me/5544999506302" 
                       class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors">
                        <i class="fa-brands fa-whatsapp"></i>
                        WhatsApp comercial
                    </a>
                    <a href="mailto:lukratosistema@gmail.com" 
                       class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors break-all">
                        <i class="fa-regular fa-envelope"></i>
                        lukratosistema@gmail.com
                    </a>
                </div>
            </div>

        </div>

        <!-- Bottom -->
        <div class="pt-8 border-t border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-gray-400 text-sm">
                © 2025 Lukrato. Todos os direitos reservados.
            </p>
            <p class="text-gray-500 text-sm italic">
                Feito para quem quer cuidar melhor do próprio dinheiro.
            </p>
        </div>
    </div>
</footer>


<!-- JS global da landing (menu mobile, scroll suave, etc.) -->
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