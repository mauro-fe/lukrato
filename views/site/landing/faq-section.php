<!-- Seção de Perguntas Frequentes -->
<section id="faq" class="relative py-16 md:py-24 bg-white" aria-labelledby="faq-titulo">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <header class="lk-header-card max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 rounded-full mb-4">
                <i data-lucide="help-circle" class="w-5 h-5 text-primary"></i>
                <span class="text-sm font-semibold text-primary">Tire suas dúvidas</span>
            </div>
            <h2 id="faq-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Perguntas
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    Frequentes
                </span>
            </h2>
            <p class="text-lg text-gray-600 leading-relaxed">
                Tudo o que você precisa saber sobre o Lukrato antes de começar.
            </p>
        </header>

        <!-- FAQ Accordion -->
        <div class="max-w-3xl mx-auto space-y-3" x-data="{ openItem: null }" itemscope itemtype="https://schema.org/FAQPage">

            <!-- FAQ 1 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 1 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="0"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 1 ? null : 1"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 1">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">O Lukrato é realmente gratuito?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 1 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 1" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        Sim! O plano gratuito permite que você use as funcionalidades essenciais de controle financeiro sem nenhum custo.
                        Você pode registrar lançamentos, acompanhar seu saldo e organizar suas finanças.
                        O plano Pro oferece recursos extras como relatórios avançados, mais categorias e exportação de dados.
                    </div>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 2 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="50"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 2 ? null : 2"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 2">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">Preciso de cartão de crédito para começar?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 2 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 2" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        Não! Para criar sua conta e usar o plano gratuito, basta seu e-mail ou conta Google.
                        Nenhum dado de pagamento é solicitado. Se decidir assinar o plano Pro,
                        oferecemos opções de pagamento via Pix e boleto, além de cartão.
                    </div>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 3 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="100"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 3 ? null : 3"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 3">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">Meus dados financeiros ficam seguros?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 3 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 3" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        Sim, a segurança dos seus dados é prioridade. O Lukrato segue as diretrizes da LGPD
                        (Lei Geral de Proteção de Dados) e todas as informações são armazenadas de forma segura.
                        Seus dados financeiros são privados e nunca são compartilhados com terceiros.
                    </div>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 4 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="150"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 4 ? null : 4"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 4">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">Qual a diferença entre o plano Gratuito e o Pro?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 4 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 4" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        O plano Gratuito oferece o controle financeiro essencial: lançamentos, dashboard e categorias básicas.
                        O plano Pro desbloqueia relatórios avançados com gráficos e insights, agendamentos de contas,
                        exportação de dados em CSV, categorias e contas ilimitadas, e suporte prioritário.
                    </div>
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 5 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="200"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 5 ? null : 5"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 5">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">Posso cancelar minha assinatura a qualquer momento?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 5 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 5" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        Sim, sem fidelidade. Você pode cancelar sua assinatura Pro diretamente pelo painel do sistema,
                        sem precisar entrar em contato com o suporte. Após o cancelamento, você continua tendo acesso
                        ao plano Pro até o final do período já pago.
                    </div>
                </div>
            </div>

            <!-- FAQ 6 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 6 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="250"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 6 ? null : 6"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 6">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">Como funciona o sistema de gamificação?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 6 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 6" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        Cada ação que você realiza no sistema — como registrar lançamentos, manter seu streak de uso diário
                        ou completar metas — gera pontos. Esses pontos acumulam para subir de nível (são 15 níveis no total)
                        e desbloquear conquistas exclusivas. É uma forma divertida de manter a consistência na organização financeira.
                    </div>
                </div>
            </div>

            <!-- FAQ 7 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 7 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="300"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 7 ? null : 7"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 7">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">Posso acessar de qualquer dispositivo?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 7 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 7" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        Sim! O Lukrato é um sistema web responsivo que funciona em qualquer navegador.
                        Você pode acessar do computador, tablet ou celular, sem precisar instalar nada.
                        Seus dados ficam sincronizados automaticamente.
                    </div>
                </div>
            </div>

            <!-- FAQ 8 -->
            <div class="lk-faq-item border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200"
                :class="openItem === 8 ? 'shadow-md border-orange-200' : 'hover:border-gray-300'"
                data-aos="fade-up" data-aos-delay="350"
                itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button @click="openItem = openItem === 8 ? null : 8"
                    class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50/50 transition-colors"
                    :aria-expanded="openItem === 8">
                    <span class="font-semibold text-gray-900 pr-4" itemprop="name">Como funciona o programa de indicação?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                        :class="openItem === 8 && 'rotate-180 text-primary'"></i>
                </button>
                <div x-show="openItem === 8" x-collapse itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed" itemprop="text">
                        Ao criar sua conta, você recebe um código de indicação exclusivo no seu perfil.
                        Quando um amigo se cadastra usando seu código, você ganha 15 dias de acesso Pro gratuito
                        e seu amigo ganha 7 dias. Não há limite de indicações — quanto mais amigos, mais dias grátis!
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>