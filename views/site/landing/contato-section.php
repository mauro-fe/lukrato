<!-- Seção de Contato -->
<section id="contato" class="relative py-16 md:py-24 bg-white" x-data="{ activeTab: 'whatsapp' }"
    aria-labelledby="contato-titulo" itemscope itemtype="https://schema.org/ContactPage">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <header class="lk-header-card max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
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
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-gray-800" aria-hidden="true" fill="currentColor"
                        role="img" focusable="false">
                        <path
                            d="M20.52 3.48A11.86 11.86 0 0 0 12.07 0C5.56 0 .26 5.3.26 11.81c0 2.08.54 4.1 1.57 5.89L0 24l6.48-1.7a11.79 11.79 0 0 0 5.59 1.43h.01c6.51 0 11.81-5.3 11.81-11.81 0-3.16-1.23-6.12-3.37-8.44zM12.08 21.7h-.01a9.8 9.8 0 0 1-4.99-1.37l-.36-.21-3.84 1.01 1.03-3.74-.24-.38A9.79 9.79 0 0 1 2.29 11.8c0-5.39 4.39-9.78 9.79-9.78 2.61 0 5.07 1.02 6.92 2.87a9.72 9.72 0 0 1 2.87 6.92c0 5.39-4.39 9.79-9.79 9.79zm5.37-7.35c-.29-.15-1.71-.84-1.98-.94-.26-.1-.45-.15-.64.15-.19.29-.74.94-.9 1.13-.17.19-.34.22-.63.08-.29-.15-1.23-.45-2.34-1.45-.86-.77-1.44-1.72-1.61-2.01-.17-.29-.02-.45.13-.59.13-.12.29-.34.43-.51.14-.17.19-.29.29-.49.1-.19.05-.36-.02-.51-.08-.15-.64-1.54-.87-2.11-.23-.55-.47-.48-.64-.48h-.55c-.19 0-.49.08-.75.36-.26.29-.98.96-.98 2.34 0 1.38 1.01 2.71 1.15 2.9.14.19 1.98 3.02 4.8 4.24.67.29 1.2.47 1.61.6.68.22 1.29.19 1.78.11.54-.08 1.71-.7 1.95-1.38.24-.68.24-1.27.17-1.38-.08-.12-.27-.19-.56-.34z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Atendimento rápido</h3>
                <p class="text-gray-600">Normalmente respondemos em poucos minutos em horário comercial.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-8">
                <a href="https://wa.me/5544999506302?text=Ol%C3%A1!%20Quero%20falar%20sobre%20o%20Lukrato."
                    target="_blank" rel="noopener"
                    class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 shadow-md hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                    WhatsApp (Comercial)
                </a>

                <a href="https://wa.me/5544997178938?text=Ol%C3%A1!%20Preciso%20de%20suporte%20no%20Lukrato."
                    target="_blank" rel="noopener"
                    class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-white text-green-600 border-2 border-green-500 font-semibold rounded-xl hover:bg-green-50 shadow-md hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
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
                    class="w-full px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-md hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
                    <i data-lucide="send" class="mr-2"></i>
                    Enviar mensagem
                </button>
            </form>

        </div>

    </div>

    </div>
</section>