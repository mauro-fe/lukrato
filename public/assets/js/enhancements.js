/**
 * ============================================================================
 * LUKRATO - MELHORIAS INTERATIVAS
 * ============================================================================
 * Arquivo: enhancements.js
 * Descrição: Scripts para animações de contador, ripple effects e micro-interações
 * ============================================================================
 */

(function() {
    'use strict';

    // ============================================================================
    // 1. CONTADOR ANIMADO PARA VALORES
    // ============================================================================
    
    /**
     * Anima um valor numérico de 0 até o valor final
     * @param {HTMLElement} element - Elemento a ser animado
     * @param {number} start - Valor inicial
     * @param {number} end - Valor final
     * @param {number} duration - Duração em ms
     * @param {string} prefix - Prefixo (ex: 'R$ ')
     * @param {string} suffix - Sufixo
     */
    function animateCounter(element, start, end, duration = 1000, prefix = '', suffix = '') {
        if (!element) return;
        
        const startTime = performance.now();
        const isNegative = end < 0;
        const absEnd = Math.abs(end);
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (ease-out cubic)
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            
            const current = start + (absEnd - start) * easeProgress;
            const displayValue = isNegative ? -current : current;
            
            // Formata o valor
            const formatted = new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(displayValue);
            
            element.textContent = `${prefix}${formatted}${suffix}`;
            element.classList.add('updating');
            
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                element.classList.remove('updating');
            }
        }
        
        requestAnimationFrame(update);
    }

    /**
     * Inicializa contadores para elementos com classe .kpi-value
     */
    function initCounters() {
        const counters = document.querySelectorAll('.kpi-value:not(.loading)');
        
        counters.forEach((counter, index) => {
            const text = counter.textContent.trim();
            
            // Extrai o valor numérico
            const match = text.match(/R\$\s*([-\d.,]+)/);
            if (!match) return;
            
            const value = parseFloat(match[1].replace(/\./g, '').replace(',', '.'));
            
            // Delay escalonado para cada contador
            setTimeout(() => {
                animateCounter(counter, 0, value, 1200, 'R$ ', '');
            }, index * 100);
        });
    }

    // ============================================================================
    // 2. RIPPLE EFFECT
    // ============================================================================
    
    /**
     * Adiciona efeito ripple aos botões
     */
    function initRippleEffect() {
        const buttons = document.querySelectorAll('.btn, button:not(.theme-toggle)');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.className = 'ripple-effect';
                ripple.style.cssText = `
                    position: absolute;
                    left: ${x}px;
                    top: ${y}px;
                    width: 0;
                    height: 0;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.5);
                    transform: translate(-50%, -50%);
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                
                // Anima o ripple
                ripple.animate([
                    { width: '0px', height: '0px', opacity: 1 },
                    { width: '300px', height: '300px', opacity: 0 }
                ], {
                    duration: 600,
                    easing: 'ease-out'
                }).onfinish = () => ripple.remove();
            });
        });
    }

    // ============================================================================
    // 3. ANIMAÇÃO DE ENTRADA EM LINHAS DE TABELA
    // ============================================================================
    
    /**
     * Adiciona animação escalonada para linhas de tabela
     */
    function animateTableRows() {
        const rows = document.querySelectorAll('.table tbody tr');
        
        rows.forEach((row, index) => {
            row.style.setProperty('--row-index', index);
            row.style.opacity = '0';
            
            setTimeout(() => {
                row.style.opacity = '1';
            }, index * 50);
        });
    }

    // ============================================================================
    // 4. LAZY LOADING DE IMAGENS
    // ============================================================================
    
    /**
     * Carrega imagens de forma lazy
     */
    function initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }

    // ============================================================================
    // 5. SMOOTH SCROLL
    // ============================================================================
    
    /**
     * Adiciona scroll suave para âncoras
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // ============================================================================
    // 6. TOAST NOTIFICATIONS
    // ============================================================================
    
    /**
     * Cria notificação toast
     * @param {string} message - Mensagem
     * @param {string} type - Tipo: success, error, warning, info
     * @param {number} duration - Duração em ms
     */
    window.showToast = function(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            padding: 16px 20px;
            margin-bottom: 12px;
            border-radius: 12px;
            background: var(--color-surface);
            border-left: 4px solid var(--color-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'secondary'});
            box-shadow: var(--shadow-lg);
            color: var(--color-text);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        `;
        
        const icon = document.createElement('i');
        icon.className = `fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}`;
        icon.style.cssText = `color: var(--color-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'secondary'});`;
        
        const text = document.createElement('span');
        text.textContent = message;
        
        toast.appendChild(icon);
        toast.appendChild(text);
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.4s ease';
            setTimeout(() => toast.remove(), 400);
        }, duration);
    };
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }

    // ============================================================================
    // 7. LOADING BUTTON STATE
    // ============================================================================
    
    /**
     * Adiciona estado de loading a um botão
     * @param {HTMLElement} button - Botão
     * @param {boolean} loading - True para ativar loading
     */
    window.setButtonLoading = function(button, loading = true) {
        if (!button) return;
        
        if (loading) {
            button.dataset.originalText = button.innerHTML;
            button.classList.add('loading');
            button.disabled = true;
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
    };

    // ============================================================================
    // 8. PARALLAX EFFECT
    // ============================================================================
    
    /**
     * Adiciona efeito parallax suave em elementos
     */
    function initParallax() {
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        
        if (parallaxElements.length === 0) return;
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(el => {
                const speed = el.dataset.parallax || 0.5;
                const yPos = -(scrolled * speed);
                el.style.transform = `translateY(${yPos}px)`;
            });
        });
    }

    // ============================================================================
    // 9. CARD TILT EFFECT
    // ============================================================================
    
    /**
     * Adiciona efeito de inclinação 3D aos cards
     */
    function initCardTilt() {
        const cards = document.querySelectorAll('.kpi-card, .chart-card');
        
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }

    // ============================================================================
    // 10. OBSERVER PARA ANIMAÇÕES AO SCROLL
    // ============================================================================
    
    /**
     * Anima elementos quando entram na viewport
     */
    function initScrollAnimations() {
        const animatedElements = document.querySelectorAll('[data-animate]');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const animation = entry.target.dataset.animate || 'fadeInUp';
                    entry.target.style.animation = `${animation} 0.6s ease both`;
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        animatedElements.forEach(el => observer.observe(el));
    }

    // ============================================================================
    // 11. DEBOUNCE UTILITY
    // ============================================================================
    
    /**
     * Debounce function para otimizar performance
     */
    window.debounce = function(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // ============================================================================
    // 12. COPY TO CLIPBOARD
    // ============================================================================
    
    /**
     * Copia texto para a área de transferência
     */
    window.copyToClipboard = async function(text) {
        try {
            await navigator.clipboard.writeText(text);
            showToast('Copiado para a área de transferência!', 'success', 2000);
            return true;
        } catch (err) {
            showToast('Erro ao copiar', 'error', 2000);
            return false;
        }
    };

    // ============================================================================
    // INICIALIZAÇÃO
    // ============================================================================
    
    function init() {
        // Aguarda o DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        // Verifica preferência de movimento reduzido
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (!prefersReducedMotion) {
            // Inicializa apenas se o usuário não preferir movimento reduzido
            initCounters();
            initCardTilt();
            initParallax();
            initScrollAnimations();
        }
        
        // Inicializa recursos que não dependem de animação
        initRippleEffect();
        animateTableRows();
        initLazyLoading();
        initSmoothScroll();
        
        console.log('✨ Lukrato Enhancements initialized');
    }
    
    // Auto-inicialização
    init();
    
    // Re-inicializa contadores quando dados são atualizados
    document.addEventListener('dataUpdated', () => {
        setTimeout(initCounters, 100);
    });
    
})();
