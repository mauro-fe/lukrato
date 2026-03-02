<!-- Seção de Indicação -->
<section id="indicacao" class="relative py-14 md:py-24 bg-white overflow-hidden" aria-labelledby="indicacao-titulo">
    <!-- Background decorations -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div
            class="absolute top-20 left-10 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-15 animate-blob">
        </div>
        <div
            class="absolute bottom-20 right-10 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob animation-delay-2000">
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Header -->
        <header class="max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-orange-100 rounded-full mb-4">
                <span class="text-2xl"><i data-lucide="gift" class="w-6 h-6 text-primary"></i></span>
                <span class="text-sm font-semibold text-primary">Programa de Indicação</span>
            </div>

            <h2 id="indicacao-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Indique amigos e
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    ganhe dias grátis
                </span>
            </h2>

            <p class="text-lg sm:text-xl text-gray-600">
                Compartilhe o Lukrato com seus amigos e ganhe <strong>dias de PRO</strong> para você e para quem você
                indicar.
                Quanto mais indicações, mais benefícios!
            </p>
        </header>

        <!-- Cards de benefícios -->
        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto mb-16">
            <!-- Card Você Ganha -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1"
                data-aos="fade-right">
                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-primary to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i data-lucide="user" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Você ganha</h3>
                        <p class="text-gray-600">Por cada amigo indicado</p>
                    </div>
                </div>
                <div class="text-center py-6 bg-white rounded-2xl shadow-inner">
                    <p
                        class="text-5xl font-bold bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                        15 dias
                    </p>
                    <p class="text-gray-600 mt-2 font-medium">de acesso PRO grátis</p>
                </div>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-center gap-3 text-gray-700">
                        <i data-lucide="circle-check" class="text-green-500"></i>
                        <span>Acumule dias ilimitados</span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-700">
                        <i data-lucide="circle-check" class="text-green-500"></i>
                        <span>Ganhe quando seu amigo se cadastrar</span>
                    </li>
                </ul>
            </div>

            <!-- Card Amigo Ganha -->
            <div class="bg-gradient-to-br from-gray-50 to-gray-100/50 rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1"
                data-aos="fade-left">
                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-secondary to-gray-700 rounded-2xl flex items-center justify-center shadow-lg">
                        <i data-lucide="users" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Seu amigo ganha</h3>
                        <p class="text-gray-600">Ao usar seu código</p>
                    </div>
                </div>
                <div class="text-center py-6 bg-white rounded-2xl shadow-inner">
                    <p
                        class="text-5xl font-bold bg-gradient-to-r from-secondary to-gray-700 bg-clip-text text-transparent">
                        7 dias
                    </p>
                    <p class="text-gray-600 mt-2 font-medium">de acesso PRO grátis</p>
                </div>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-center gap-3 text-gray-700">
                        <i data-lucide="circle-check" class="text-green-500"></i>
                        <span>Começa já com benefícios PRO</span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-700">
                        <i data-lucide="circle-check" class="text-green-500"></i>
                        <span>Sem precisar pagar nada</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Como funciona -->
        <div class="max-w-4xl mx-auto" data-aos="fade-up">
            <h3 class="text-2xl font-bold text-center text-gray-900 mb-10">
                Como funciona?
            </h3>

            <div class="flex flex-col sm:flex-row items-start justify-center gap-4 sm:gap-0">
                <!-- Passo 1 -->
                <div class="text-center group flex-1 max-w-[200px] mx-auto sm:mx-0">
                    <div
                        class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <span class="text-2xl font-bold text-primary">1</span>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Crie sua conta</h4>
                    <p class="text-gray-600 text-sm">
                        Cadastre-se grátis e acesse seu código de indicação no perfil
                    </p>
                </div>

                <!-- Linha conectora 1-2 -->
                <div class="hidden sm:flex items-center justify-center flex-shrink-0 pt-8">
                    <div class="w-16 h-0.5 bg-gradient-to-r from-orange-200 via-primary to-orange-200"></div>
                </div>

                <!-- Passo 2 -->
                <div class="text-center group flex-1 max-w-[200px] mx-auto sm:mx-0">
                    <div
                        class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <span class="text-2xl font-bold text-primary">2</span>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Compartilhe</h4>
                    <p class="text-gray-600 text-sm">
                        Envie seu código ou link para amigos via WhatsApp, redes sociais
                    </p>
                </div>

                <!-- Linha conectora 2-3 -->
                <div class="hidden sm:flex items-center justify-center flex-shrink-0 pt-8">
                    <div class="w-16 h-0.5 bg-gradient-to-r from-orange-200 via-primary to-orange-200"></div>
                </div>

                <!-- Passo 3 -->
                <div class="text-center group flex-1 max-w-[200px] mx-auto sm:mx-0">
                    <div
                        class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <span class="text-2xl font-bold text-primary">3</span>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Ganhe dias PRO</h4>
                    <p class="text-gray-600 text-sm">
                        Quando seu amigo se cadastrar, vocês dois ganham dias de PRO!
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-16" data-aos="fade-up">
            <a href="<?= BASE_URL ?>login"
                class="inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300">
                Criar conta e começar a indicar
                <i data-lucide="arrow-right" class="ml-3"></i>
            </a>
            <p class="text-sm text-gray-500 mt-4">
                <i data-lucide="gift" class="mr-2 text-primary"></i>
                Sem limite de indicações — quanto mais amigos, mais dias você ganha!
            </p>
        </div>
    </div>
</section>
