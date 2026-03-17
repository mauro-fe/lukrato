<!-- MODAL / GALERIA -->
<div id="galleryModal" x-data="{ 
        open: false, 
        currentSlide: 0, 
        slides: [
            { src: '<?= BASE_URL ?>assets/img/mockups/dashboard.png', title: 'Dashboard', desc: 'Visão geral rápida: saldo, receitas e despesas do mês.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/contas.png', title: 'Contas', desc: 'Crie e gerencie contas: banco, carteira e reserva.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/categorias.png', title: 'Categorias', desc: 'Organize receitas e despesas por categoria com facilidade.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/relatorios.png', title: 'Relatórios', desc: 'Gráficos e insights para entender seus gastos e evolução.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/5.png', title: 'Tema claro', desc: 'Escolha o tema que preferir para usar no dia a dia.' }
        ],
        nextSlide() {
            this.currentSlide = this.currentSlide < this.slides.length - 1 ? this.currentSlide + 1 : 0;
        },
        prevSlide() {
            this.currentSlide = this.currentSlide > 0 ? this.currentSlide - 1 : this.slides.length - 1;
        }
     }" @open-gallery.window="open = true; currentSlide = 0" @keydown.escape.window="open = false" x-show="open"
    x-cloak style="display: none;" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="open = false"
        onclick="document.getElementById('galleryModal').style.display='none'"></div>

    <!-- Modal Content -->
    <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden" @click.stop>

        <!-- Close button -->
        <button @click="open = false" onclick="document.getElementById('galleryModal').style.display='none'"
            class="absolute top-4 right-4 z-20 w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-colors">
            <i data-lucide="x" class="text-xl text-gray-700"></i>
        </button>

        <div class="p-6 sm:p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Um pouco do Lukrato por dentro</h3>

            <!-- Gallery -->
            <div class="relative">
                <!-- Images -->
                <div class="relative aspect-video bg-gray-100 rounded-xl overflow-hidden mb-6">
                    <template x-for="(slide, index) in slides" :key="index">
                        <div x-show="currentSlide === index" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            class="absolute inset-0">
                            <img :src="slide.src" :alt="slide.title" class="w-full h-full object-contain"
                                loading="lazy" />
                        </div>
                    </template>
                </div>

                <!-- Navigation Arrows -->
                <button @click="prevSlide()"
                    class="absolute left-2 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-all hover:scale-110 z-10">
                    <i data-lucide="chevron-left" class="text-gray-700"></i>
                </button>

                <button @click="nextSlide()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-all hover:scale-110 z-10">
                    <i data-lucide="chevron-right" class="text-gray-700"></i>
                </button>
            </div>

            <!-- Meta Info -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <div class="flex-1">
                    <h4 class="text-lg font-semibold text-gray-900" x-text="slides[currentSlide].title"></h4>
                    <p class="text-sm text-gray-600" x-text="slides[currentSlide].desc"></p>
                </div>
                <div class="text-sm font-medium text-gray-500">
                    <span x-text="currentSlide + 1"></span>/<span x-text="slides.length"></span>
                </div>
            </div>

            <!-- Thumbnail dots -->
            <div class="flex justify-center gap-2 mt-4">
                <template x-for="(slide, index) in slides" :key="index">
                    <button @click="currentSlide = index" class="w-2 h-2 rounded-full transition-all"
                        :class="currentSlide === index ? 'bg-primary w-8' : 'bg-gray-300 hover:bg-gray-400'">
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
