<!-- Seção de Contato -->
<section id="contato" class="relative py-14 md:py-14 bg-white" x-data="{ activeTab: 'whatsapp' }"
    aria-labelledby="contato-titulo" itemscope itemtype="https://schema.org/ContactPage">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <header class="max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
            <h2 id="contato-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Fale com o
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    Suporte Lukrato
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 mb-6">
                Dúvidas sobre <strong>controle financeiro pessoal</strong>? Quer sugestões ou precisa de ajuda? Escolha
                o canal abaixo.
            </p>

            <!-- Toggle Tabs com Alpine.js -->
            <div class="inline-flex bg-gray-100 rounded-xl p-1 shadow-inner">
                <button @click="activeTab = 'whatsapp'"
                    :class="activeTab === 'whatsapp' ? 'bg-white text-primary shadow-md' : 'text-gray-600 hover:text-gray-900'"
                    class="px-8 py-3 rounded-lg font-semibold transition-all duration-300">
                    <i data-lucide="message-circle" class="w-5 h-5 mr-2"></i>
                    WhatsApp
                </button>
                <button @click="activeTab = 'email'"
                    :class="activeTab === 'email' ? 'bg-white text-primary shadow-md' : 'text-gray-600 hover:text-gray-900'"
                    class="px-8 py-3 rounded-lg font-semibold transition-all duration-300">
                    <i data-lucide="mail" class="mr-2"></i>
                    E-mail
                </button>
            </div>
    </div>

    <!-- Panels -->
    <div class="max-w-3xl mx-auto">

        <!-- WhatsApp Panel -->
        <div x-show="activeTab === 'whatsapp'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-8 sm:p-12 shadow-xl" data-aos="fade-up">

            <div class="text-center mb-8">
                <div class="inline-flex w-16 h-16 bg-green-500 rounded-full items-center justify-center mb-4 shadow-lg">
                    <i data-lucide="message-circle" class="w-8 h-8 text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Atendimento rápido</h3>
                <p class="text-gray-600">Normalmente respondemos em poucos minutos em horário comercial.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-8">
                <a href="https://wa.me/5544999506302?text=Ol%C3%A1!%20Quero%20falar%20sobre%20o%20Lukrato."
                    target="_blank" rel="noopener"
                    class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                    WhatsApp (Comercial)
                </a>

                <a href="https://wa.me/5544997178938?text=Ol%C3%A1!%20Preciso%20de%20suporte%20no%20Lukrato."
                    target="_blank" rel="noopener"
                    class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-white text-green-600 border-2 border-green-500 font-semibold rounded-xl hover:bg-green-50 shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <i data-lucide="headphones" class="text-xl"></i>
                    WhatsApp (Suporte)
                </a>
            </div>

            <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-sm">
                    <i data-lucide="circle-check" class="text-green-500"></i>
                    Sem compromisso
                </span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-sm">
                    <i data-lucide="lock" class="text-primary"></i>
                    Seus dados ficam privados
                </span>
            </div>
        </div>

        <!-- E-mail Panel -->
        <div x-show="activeTab === 'email'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-2xl p-8 sm:p-12 shadow-xl"
            data-aos="fade-up">

            <div class="text-center mb-8">
                <div class="inline-flex w-16 h-16 bg-primary rounded-full items-center justify-center mb-4 shadow-lg">
                    <i data-lucide="mail" class="text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Envie uma mensagem</h3>
                <p class="text-gray-600">Prefere e-mail? Mande sua dúvida e respondemos em até 1 dia útil.</p>
            </div>

            <form id="contactForm" class="space-y-6">
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <label for="lk_nome" class="block text-sm font-semibold text-gray-700 mb-2">
                            Seu nome
                        </label>
                        <input id="lk_nome" name="nome" type="text" placeholder="Seu nome" required
                            class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-sm font-semibold text-gray-700 mb-2">
                            WhatsApp <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <input id="whatsapp" name="whatsapp" type="text" placeholder="(00) 00000-0000"
                            autocomplete="tel"
                            class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label for="lk_email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Seu e-mail
                    </label>
                    <input id="lk_email" name="email" type="email" placeholder="voce@email.com" required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                </div>

                <div>
                    <label for="lk_assunto" class="block text-sm font-semibold text-gray-700 mb-2">
                        Assunto
                    </label>
                    <input id="lk_assunto" name="assunto" type="text" placeholder="Ex: Dúvida sobre o plano Pro"
                        required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                </div>

                <div>
                    <label for="lk_mensagem" class="block text-sm font-semibold text-gray-700 mb-2">
                        Mensagem
                    </label>
                    <textarea id="lk_mensagem" name="mensagem" rows="6" placeholder="Escreva sua mensagem..." required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all resize-none"></textarea>
                </div>

                <button type="submit"
                    class="w-full px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <i data-lucide="send" class="mr-2"></i>
                    Enviar mensagem
                </button>
            </form>

        </div>

    </div>

    </div>
</section>
