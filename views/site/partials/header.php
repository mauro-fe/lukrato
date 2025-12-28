<?php
$pageTitle = $pageTitle ?? 'Lukrato';
$extraCss  = $extraCss  ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#e67e22',
                    secondary: '#2c3e50',
                    success: '#2ecc71',
                    warning: '#f39c12',
                    danger: '#e74c3c',
                }
            }
        }
    }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Style para Alpine.js x-cloak -->
    <style>
    [x-cloak] {
        display: none !important;
    }

    html,
    body {
        overflow-x: hidden;
        width: 100%;
        max-width: 100vw;
    }
    </style>

    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/site/landing-base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/site/modal-override.css">
    <link rel="icon" href="<?= BASE_URL ?>assets/img/icone.png" alt="icone lukrato">

    <?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/site/<?= htmlspecialchars($css) ?>.css">
    <?php endforeach; ?>


    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script>
    // Inicializar AOS quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    });
    </script>

</head>

<body class="antialiased" x-data="{ mobileMenuOpen: false }">
    <!-- Header Premium -->
    <header x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 20"
        :class="scrolled ? 'bg-white shadow-lg' : 'bg-white/95'"
        class="fixed top-0 left-0 right-0 z-50 backdrop-blur-lg transition-all duration-300">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo com animação -->
                <a href="<?= BASE_URL ?>" class="flex-shrink-0 group">
                    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato"
                        class="h-8 sm:h-16 transition-transform duration-300 group-hover:scale-110" loading="eager"
                        onerror="console.error('Logo não carregou:', this.src)">
                </a>

                <!-- Desktop Navigation Premium -->
                <nav class="hidden lg:flex items-center gap-8" aria-label="Navegação principal">
                    <a href="<?= BASE_URL ?>#funcionalidades"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group">
                        Funcionalidades
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#beneficios"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group">
                        Benefícios
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#planos"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group">
                        Planos
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#contato"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group">
                        Contato
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                </nav>

                <!-- Desktop Actions Premium -->
                <div class="hidden lg:flex items-center gap-4">
                    <a href="<?= BASE_URL ?>login"
                        class="inline-flex items-center gap-2 px-4 py-2 text-gray-700 hover:text-primary font-semibold transition-all duration-300 group">
                        <i class="fa-regular fa-user text-sm transition-transform group-hover:scale-110"></i>
                        <span>Login</span>
                    </a>
                    <a href="<?= BASE_URL ?>login"
                        class="inline-flex items-center gap-2 px-7 py-3 bg-gradient-to-r from-primary via-orange-500 to-orange-600 text-white font-bold rounded-full shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/50 hover:scale-105 transition-all duration-300 group">
                        <span>Começar grátis</span>
                        <i class="fa-solid fa-arrow-right text-sm transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>

                <!-- Mobile Menu Button Premium -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden relative p-2.5 text-gray-700 hover:text-primary hover:bg-orange-50 rounded-xl transition-all duration-300"
                    type="button" aria-label="Menu">
                    <i class="fa-solid fa-bars text-2xl" x-show="!mobileMenuOpen"></i>
                    <i class="fa-solid fa-xmark text-2xl" x-show="mobileMenuOpen" x-cloak></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Backdrop -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] lg:hidden" x-cloak>
    </div>

    <!-- Mobile Menu Panel -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 bottom-0 w-80 max-w-full bg-white shadow-2xl z-[70] lg:hidden overflow-y-auto"
        x-cloak>
        <div class="p-6">
            <!-- Header do Menu -->
            <div class="flex items-center justify-end mb-8">
                <button @click="mobileMenuOpen = false"
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    type="button">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>

            <!-- Navegação -->
            <nav class="flex flex-col gap-4 mb-6">
                <a href="<?= BASE_URL ?>#funcionalidades" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Funcionalidades
                </a>
                <a href="<?= BASE_URL ?>#beneficios" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Benefícios
                </a>
                <a href="<?= BASE_URL ?>#planos" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Planos
                </a>
                <a href="<?= BASE_URL ?>#contato" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Contato
                </a>
            </nav>

            <!-- Botões de Ação -->
            <div class="flex flex-col gap-3 pt-4 border-t border-gray-100">
                <a href="<?= BASE_URL ?>login"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 text-gray-700 hover:text-primary font-semibold border-2 border-gray-200 rounded-xl hover:border-primary transition-all duration-300">
                    <i class="fa-regular fa-user"></i>
                    <span>Login</span>
                </a>
                <a href="<?= BASE_URL ?>login"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary via-orange-500 to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <span>Começar grátis</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>