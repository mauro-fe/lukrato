<?php
$extraJs = $extraJs ?? [];
?>

</main>

<!-- Footer Moderno -->
<footer class="lk-footer" role="contentinfo" itemscope itemtype="https://schema.org/WPFooter">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">

            <!-- Marca -->
            <div class="space-y-4" itemscope itemtype="https://schema.org/Organization">
                <a href="<?= BASE_URL ?>/"
                    class="inline-block hover:opacity-90 transition-all duration-300 hover:scale-105"
                    title="Lukrato - Controle Financeiro Pessoal" aria-label="Ir para página inicial do Lukrato">
                    <img src="<?= BASE_URL ?>/assets/img/logo.png"
                        alt="Lukrato - Melhor App de Controle Financeiro Pessoal Grátis"
                        title="Lukrato - Sistema de Finanças Pessoais" class="h-14" loading="lazy" width="180"
                        height="56" itemprop="logo">
                </a>
                <meta itemprop="name" content="Lukrato">
                <meta itemprop="url" content="<?= BASE_URL ?>">
                <p class="text-white/70 leading-relaxed" itemprop="description">
                    Controle financeiro pessoal simples, inteligente e sem complicação. Organize suas finanças grátis.
                </p>
                <nav class="lk-footer-icons flex gap-3 pt-2" aria-label="Redes sociais">
                    <a href="https://facebook.com/lukrato" aria-label="Lukrato no Facebook" rel="noopener noreferrer"
                        target="_blank" class="w-11 h-11 bg-white rounded-xl flex items-center justify-center"
                        itemprop="sameAs">
                        <i class="fa-brands fa-facebook-f text-lg" aria-hidden="true"></i>
                    </a>
                    <a href="https://instagram.com/lukrato.oficial" aria-label="Lukrato no Instagram"
                        rel="noopener noreferrer" target="_blank"
                        class="w-11 h-11 bg-white rounded-xl flex items-center justify-center" itemprop="sameAs">
                        <i class="fa-brands fa-instagram text-lg" aria-hidden="true"></i>
                    </a>
                    <a href="https://linkedin.com/company/lukrato" aria-label="Lukrato no LinkedIn"
                        rel="noopener noreferrer" target="_blank"
                        class="w-11 h-11 bg-white rounded-xl flex items-center justify-center" itemprop="sameAs">
                        <i class="fa-brands fa-linkedin-in text-lg" aria-hidden="true"></i>
                    </a>
                </nav>
            </div>

            <!-- Produto -->
            <div class="lk-footer-links">
                <h3 class="font-bold text-lg mb-5 text-white">Produto</h3>
                <nav class="flex flex-col gap-3" aria-label="Links do produto">
                    <a href="<?= BASE_URL ?>/#funcionalidades" class="text-white/70"
                        title="Funcionalidades do app de controle financeiro">
                        <span>Funcionalidades</span>
                    </a>
                    <a href="<?= BASE_URL ?>/#beneficios" class="text-white/70"
                        title="Benefícios do controle financeiro pessoal">
                        <span>Benefícios</span>
                    </a>
                    <a href="<?= BASE_URL ?>/#planos" class="text-white/70" title="Planos e preços do Lukrato">
                        <span>Planos e Preços</span>
                    </a>
                    <a href="<?= BASE_URL ?>login" class="text-white/70" title="Entrar ou criar conta grátis">
                        <span>Entrar / Criar Conta</span>
                    </a>
                </nav>
            </div>

            <!-- Legal -->
            <div class="lk-footer-links">
                <h3 class="font-bold text-lg mb-5 text-white">Legal</h3>
                <nav class="flex flex-col gap-3" aria-label="Links legais">
                    <a href="<?= BASE_URL ?>termos" class="text-white/70" title="Termos de Uso do Lukrato">
                        <span>Termos de Uso</span>
                    </a>
                    <a href="<?= BASE_URL ?>privacidade" class="text-white/70" title="Política de Privacidade">
                        <span>Política de Privacidade</span>
                    </a>
                    <a href="<?= BASE_URL ?>lgpd" class="text-white/70" title="LGPD e proteção de dados pessoais">
                        <span>LGPD e Proteção de Dados</span>
                    </a>
                </nav>
            </div>

            <!-- Contato -->
            <div class="lk-footer-links" itemscope itemtype="https://schema.org/ContactPoint">
                <h3 class="font-bold text-lg mb-5 text-white">Contato</h3>
                <meta itemprop="contactType" content="Customer Service">
                <meta itemprop="availableLanguage" content="Portuguese">
                <div class="flex flex-col gap-4">
                    <a href="https://wa.me/5544999506302" class="inline-flex items-center gap-3 text-white/70 group"
                        rel="noopener noreferrer" target="_blank" title="Fale conosco pelo WhatsApp"
                        itemprop="telephone">
                        <span
                            class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center transition-all duration-300 group-hover:bg-green-500/20"
                            aria-hidden="true">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                        </span>
                        <span>WhatsApp Comercial</span>
                    </a>
                    <a href="mailto:lukratosistema@gmail.com" class="inline-flex items-center gap-3 text-white/70 group"
                        title="Envie um e-mail para o Lukrato" itemprop="email">
                        <span
                            class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center transition-all duration-300 group-hover:bg-orange-500/20"
                            aria-hidden="true">
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
                © <?= date('Y') ?> <strong>Lukrato</strong> - Controle Financeiro Pessoal. Todos os direitos reservados.
            </p>
            <p class="text-white/40 text-sm italic flex items-center gap-2">
                <span>Feito com</span>
                <i class="fas fa-heart text-red-400 animate-pulse" aria-hidden="true"></i>
                <span>para quem quer organizar suas finanças pessoais.</span>
            </p>
        </div>
    </div>
</footer>


<!-- JS global da landing (menu mobile, scroll suave, etc.) -->
<script src="<?= BASE_URL ?>/assets/js/csrf-manager.js"></script>
<script src="<?= BASE_URL ?>/assets/js/csrf-keep-alive.js"></script>
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