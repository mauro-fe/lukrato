// Tracking de cliques nos links
document.addEventListener('DOMContentLoaded', function() {
    // Rastrear cliques em links
    const trackableLinks = document.querySelectorAll('.link-card, .btn, .social-btn');
    
    trackableLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const linkType = this.classList.contains('btn') ? 'CTA Button' :
                            this.classList.contains('social-btn') ? 'Social Link' :
                            'Link Card';
            const linkText = this.querySelector('h3')?.textContent || 
                           this.getAttribute('title') || 
                           this.textContent.trim();
            
            // Log para debug (pode substituir por analytics real)
            console.log(`Click tracked: ${linkType} - ${linkText}`);
            
            // Se tiver Google Analytics ou similar, adicionar aqui:
            // gtag('event', 'click', {
            //     'event_category': linkType,
            //     'event_label': linkText
            // });
        });
    });

    // Animação de entrada suave nos elementos
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observar elementos para animação
    document.querySelectorAll('.link-card, .feature-item').forEach(el => {
        observer.observe(el);
    });

    // Adicionar efeito de partículas no hover do botão principal (opcional)
    const primaryBtn = document.querySelector('.btn-primary');
    if (primaryBtn) {
        primaryBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        primaryBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    }

    // Copiar link para clipboard (funcionalidade extra)
    const shareBtn = document.createElement('button');
    shareBtn.className = 'share-btn';
    shareBtn.innerHTML = '<i class="fas fa-share-alt"></i>';
    shareBtn.title = 'Compartilhar este link';
    shareBtn.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        color: white;
        font-size: 1.3rem;
        cursor: pointer;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        transition: all 0.3s ease;
    `;

    shareBtn.addEventListener('click', async function() {
        const url = window.location.href;
        
        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'Lukrato - Controle Financeiro',
                    text: 'Controle suas finanças de forma inteligente',
                    url: url
                });
            } catch (err) {
                console.log('Compartilhamento cancelado');
            }
        } else if (navigator.clipboard) {
            try {
                await navigator.clipboard.writeText(url);
                showNotification('Link copiado!');
            } catch (err) {
                console.error('Erro ao copiar:', err);
            }
        }
    });

    shareBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1) translateY(-4px)';
    });

    shareBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1) translateY(0)';
    });

    document.body.appendChild(shareBtn);

    // Função para mostrar notificação
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 24px;
            right: 24px;
            background: #10b981;
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            animation: slideInRight 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }

    // Adicionar estilos de animação para notificação
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
    `;
    document.head.appendChild(style);

    // Analytics de tempo na página
    let startTime = Date.now();
    
    window.addEventListener('beforeunload', function() {
        const timeSpent = Math.round((Date.now() - startTime) / 1000);
        console.log(`Tempo na página: ${timeSpent} segundos`);
        
        // Enviar para analytics se configurado
        // gtag('event', 'timing_complete', {
        //     'name': 'page_view',
        //     'value': timeSpent
        // });
    });
});
