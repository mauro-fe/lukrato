<!-- CTA Final — Último empurrão antes do footer -->
<section class="py-16 lg:py-20 bg-gradient-to-r from-primary to-orange-600 relative overflow-hidden" aria-label="Chamada final para ação">
    <!-- Decoração sutil -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 left-1/4 w-64 h-64 bg-white/5 rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-48 h-48 bg-white/5 rounded-full filter blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-3xl mx-auto text-center space-y-6">
            <!-- Headline -->
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight" data-aos="fade-up">
                Seu dinheiro não vai se organizar sozinho
            </h2>

            <!-- Sub -->
            <p class="text-lg text-white/85 max-w-xl mx-auto" data-aos="fade-up" data-aos-delay="100">
                Crie sua conta agora e descubra para onde seu dinheiro está indo.
            </p>

            <!-- CTA -->
            <div class="flex flex-col items-center pt-2" data-aos="fade-up" data-aos-delay="200">
                <a href="<?= BASE_URL ?>login"
                    class="group inline-flex items-center justify-center px-10 py-4 text-lg font-bold text-primary bg-white rounded-xl shadow-xl hover:shadow-2xl hover:scale-[1.03] active:scale-[0.98] transition-all duration-300"
                    title="Criar conta gratuita no Lukrato" aria-label="Criar minha conta grátis">
                    Criar minha conta grátis
                    <i data-lucide="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" aria-hidden="true"></i>
                </a>
                <span class="text-sm text-white/70 mt-3 font-medium">Junte-se a +1.000 pessoas que já estão no controle</span>
            </div>

            <!-- Trust inline -->
            <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 pt-4" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-center gap-2 text-white/80 text-sm">
                    <i data-lucide="lock" class="w-4 h-4" aria-hidden="true"></i>
                    <span>Dados protegidos</span>
                </div>
                <div class="flex items-center gap-2 text-white/80 text-sm">
                    <i data-lucide="zap" class="w-4 h-4" aria-hidden="true"></i>
                    <span>1 minuto para criar</span>
                </div>
                <div class="flex items-center gap-2 text-white/80 text-sm">
                    <i data-lucide="credit-card" class="w-4 h-4" aria-hidden="true"></i>
                    <span>Sem cartão de crédito</span>
                </div>
            </div>
        </div>
    </div>
</section>