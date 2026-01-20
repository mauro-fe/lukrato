/* ============================================================================
   LUKRATO - MOBILE ENHANCEMENTS
   ============================================================================
   Arquivo: mobile.js
   Descrição: Melhorias de UX e funcionalidades específicas para mobile
   ============================================================================ */

(function () {
  'use strict';

  // ============================================================================
  // SCROLL TO TOP BUTTON
  // ============================================================================
  const initScrollToTop = () => {
    const scrollBtn = document.getElementById('scrollToTopBtn') ||
      document.querySelector('.scroll-to-top, [class*="scroll-top"], [class*="back-to-top"]');

    if (!scrollBtn) {
      return;
    }


    // Mostrar/ocultar baseado no scroll
    const toggleButton = () => {
      if (window.pageYOffset > 300) {
        scrollBtn.classList.add('show');
      } else {
        scrollBtn.classList.remove('show');
      }
    };

    // Scroll to top ao clicar
    scrollBtn.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // Listener de scroll
    let scrollTimeout;
    window.addEventListener('scroll', () => {
      if (scrollTimeout) {
        clearTimeout(scrollTimeout);
      }
      scrollTimeout = setTimeout(toggleButton, 100);
    }, { passive: true });

    // Check inicial
    toggleButton();
  };

  // ============================================================================
  // DETECÇÃO DE DISPOSITIVO
  // ============================================================================
  const isMobile = () => {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
      window.innerWidth <= 768;
  };

  const isTablet = () => {
    return /iPad|Android/i.test(navigator.userAgent) && window.innerWidth >= 768 && window.innerWidth <= 1024;
  };

  const isTouchDevice = () => {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  };

  // Adiciona classes ao body baseado no dispositivo
  if (isMobile()) {
    document.body.classList.add('is-mobile');
  }

  if (isTablet()) {
    document.body.classList.add('is-tablet');
  }

  if (isTouchDevice()) {
    document.body.classList.add('is-touch');
  }

  // ============================================================================
  // SIDEBAR MOBILE
  // ============================================================================
  const initSidebarMobile = () => {
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const menuBtn = document.querySelector('.header-menu-btn');
    const edgeBtn = document.querySelector('.edge-menu-btn');

    if (!sidebar) return;

    // Criar backdrop se não existir
    let sidebarBackdrop = backdrop;
    if (!sidebarBackdrop) {
      sidebarBackdrop = document.createElement('div');
      sidebarBackdrop.className = 'sidebar-backdrop';
      document.body.appendChild(sidebarBackdrop);
    }

    // Função para abrir sidebar
    const openSidebar = () => {
      document.body.classList.add('sidebar-open-mobile');
      sidebar.setAttribute('aria-hidden', 'false');
      sidebarBackdrop.setAttribute('aria-hidden', 'false');

      // Focar no primeiro link do menu
      setTimeout(() => {
        const firstLink = sidebar.querySelector('.nav-item');
        if (firstLink) firstLink.focus();
      }, 300);
    };

    // Função para fechar sidebar
    const closeSidebar = () => {
      document.body.classList.remove('sidebar-open-mobile');
      sidebar.setAttribute('aria-hidden', 'true');
      sidebarBackdrop.setAttribute('aria-hidden', 'true');
    };

    // Toggle sidebar
    const toggleSidebar = () => {
      if (document.body.classList.contains('sidebar-open-mobile')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    };

    // Event listeners
    if (menuBtn) {
      menuBtn.addEventListener('click', toggleSidebar);
    }

    if (edgeBtn) {
      edgeBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarBackdrop) {
      sidebarBackdrop.addEventListener('click', closeSidebar);
    }

    // Fechar ao clicar em um link (mobile)
    if (isMobile()) {
      const navLinks = sidebar.querySelectorAll('.nav-item');
      navLinks.forEach(link => {
        link.addEventListener('click', () => {
          setTimeout(closeSidebar, 200);
        });
      });
    }

    // Fechar com ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && document.body.classList.contains('sidebar-open-mobile')) {
        closeSidebar();
      }
    });

    // Fechar ao redimensionar para desktop
    window.addEventListener('resize', () => {
      if (window.innerWidth > 992) {
        closeSidebar();
      }
    });
  };

  // ============================================================================
  // TABELAS RESPONSIVAS
  // ============================================================================
  const initTabelasResponsivas = () => {
    const tables = document.querySelectorAll('table:not(.table-responsive)');

    tables.forEach(table => {
      // Envolver tabela em container responsivo se não estiver
      if (!table.parentElement.classList.contains('table-responsive')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
      }

      // Adicionar indicador de scroll em mobile
      if (isMobile()) {
        const wrapper = table.closest('.table-responsive');
        if (wrapper && wrapper.scrollWidth > wrapper.clientWidth) {
          const indicator = document.createElement('div');
          indicator.className = 'scroll-indicator';
          indicator.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Deslize para ver mais';
          indicator.style.cssText = `
            text-align: center;
            padding: 8px;
            background: rgba(230, 126, 34, 0.1);
            color: var(--color-primary);
            font-size: 12px;
            border-radius: 8px;
            margin-top: 8px;
          `;

          wrapper.parentNode.insertBefore(indicator, wrapper.nextSibling);

          // Remover indicador após primeiro scroll
          wrapper.addEventListener('scroll', function removeIndicator() {
            indicator.style.display = 'none';
            wrapper.removeEventListener('scroll', removeIndicator);
          }, { once: true });
        }
      }
    });
  };

  // ============================================================================
  // SCROLL SUAVE EM CARDS HORIZONTAIS
  // ============================================================================
  const initScrollSuave = () => {
    const scrollContainers = document.querySelectorAll('.cartoes-scroll, .scroll-horizontal');

    scrollContainers.forEach(container => {
      let isDown = false;
      let startX;
      let scrollLeft;

      container.addEventListener('mousedown', (e) => {
        isDown = true;
        container.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
      });

      container.addEventListener('mouseleave', () => {
        isDown = false;
        container.style.cursor = 'grab';
      });

      container.addEventListener('mouseup', () => {
        isDown = false;
        container.style.cursor = 'grab';
      });

      container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 2;
        container.scrollLeft = scrollLeft - walk;
      });
    });
  };

  // ============================================================================
  // EVITAR ZOOM EM INPUTS (iOS)
  // ============================================================================
  const preventZoomOnInput = () => {
    if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
      const inputs = document.querySelectorAll('input, select, textarea');

      inputs.forEach(input => {
        // Garantir font-size mínimo de 16px para evitar zoom
        const fontSize = window.getComputedStyle(input).fontSize;
        if (parseFloat(fontSize) < 16) {
          input.style.fontSize = '16px';
        }
      });
    }
  };

  // ============================================================================
  // BOTÃO VOLTAR AO TOPO
  // ============================================================================
  const initBotaoVoltarTopo = () => {
    if (!isMobile()) return;

    let backToTopBtn = document.getElementById('backToTop');

    if (!backToTopBtn) {
      backToTopBtn = document.createElement('button');
      backToTopBtn.id = 'backToTop';
      backToTopBtn.className = 'btn-back-to-top';
      backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
      backToTopBtn.setAttribute('aria-label', 'Voltar ao topo');
      backToTopBtn.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--color-primary);
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 999;
        transition: all 0.3s ease;
      `;
      document.body.appendChild(backToTopBtn);
    }

    // Mostrar/ocultar baseado no scroll
    window.addEventListener('scroll', () => {
      if (window.pageYOffset > 300) {
        backToTopBtn.style.display = 'flex';
      } else {
        backToTopBtn.style.display = 'none';
      }
    });

    // Scroll suave ao clicar
    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  };

  // ============================================================================
  // OTIMIZAÇÃO DE MODAIS EM MOBILE
  // ============================================================================
  const initModaisMobile = () => {
    if (!isMobile()) return;

    // Observar abertura de modais
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.classList && (node.classList.contains('modal') || node.classList.contains('lkh-modal'))) {
            // Desabilitar scroll do body quando modal abrir
            if (node.classList.contains('show') || node.classList.contains('active')) {
              document.body.style.overflow = 'hidden';
            }
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Re-habilitar scroll quando modal fechar
    document.addEventListener('hidden.bs.modal', () => {
      document.body.style.overflow = '';
    });
  };

  // ============================================================================
  // TOQUES RÁPIDOS (DOUBLE TAP)
  // ============================================================================
  const initDoubleTapActions = () => {
    if (!isTouchDevice()) return;

    let lastTap = 0;
    const doubleTapElements = document.querySelectorAll('[data-double-tap]');

    doubleTapElements.forEach(element => {
      element.addEventListener('touchend', function (e) {
        const currentTime = new Date().getTime();
        const tapLength = currentTime - lastTap;

        if (tapLength < 300 && tapLength > 0) {
          // Double tap detectado
          e.preventDefault();
          const action = this.getAttribute('data-double-tap');

          if (action === 'edit') {
            this.click();
          }
        }

        lastTap = currentTime;
      });
    });
  };

  // ============================================================================
  // SWIPE GESTURES
  // ============================================================================
  const initSwipeGestures = () => {
    if (!isTouchDevice()) return;

    let touchStartX = 0;
    let touchEndX = 0;

    const swipeElements = document.querySelectorAll('[data-swipe]');

    swipeElements.forEach(element => {
      element.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
      });

      element.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe(element);
      });
    });

    function handleSwipe(element) {
      const swipeAction = element.getAttribute('data-swipe');
      const threshold = 50; // Mínimo de pixels para considerar swipe

      if (touchEndX < touchStartX - threshold) {
        // Swipe left
        if (swipeAction === 'delete') {
          element.classList.add('swiped-left');
        }
      }

      if (touchEndX > touchStartX + threshold) {
        // Swipe right
        if (swipeAction === 'delete') {
          element.classList.remove('swiped-left');
        }
      }
    }
  };

  // ============================================================================
  // PERFORMANCE: LAZY LOAD DE IMAGENS
  // ============================================================================
  const initLazyLoad = () => {
    if ('IntersectionObserver' in window) {
      const images = document.querySelectorAll('img[data-src]');

      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
          }
        });
      });

      images.forEach(img => imageObserver.observe(img));
    }
  };

  // ============================================================================
  // VIBRAÇÃO EM AÇÕES (se disponível)
  // ============================================================================
  const vibrate = (pattern = [10]) => {
    if ('vibrate' in navigator) {
      navigator.vibrate(pattern);
    }
  };

  // Adicionar vibração em botões importantes
  const initVibracao = () => {
    if (!isTouchDevice() || !('vibrate' in navigator)) return;

    const vibrationButtons = document.querySelectorAll('.btn-danger, .btn-success, [data-vibrate]');

    vibrationButtons.forEach(button => {
      button.addEventListener('click', () => {
        vibrate([10]);
      });
    });
  };

  // ============================================================================
  // ORIENTAÇÃO DA TELA
  // ============================================================================
  const handleOrientationChange = () => {
    const orientation = window.screen.orientation?.type ||
      (window.innerWidth > window.innerHeight ? 'landscape' : 'portrait');

    document.body.setAttribute('data-orientation', orientation);

    // Recarregar charts em mudança de orientação
    if (window.Chart && typeof window.reloadCharts === 'function') {
      setTimeout(() => {
        window.reloadCharts();
      }, 300);
    }
  };

  window.addEventListener('orientationchange', handleOrientationChange);
  window.addEventListener('resize', handleOrientationChange);

  // ============================================================================
  // PULL TO REFRESH (Experimental)
  // ============================================================================
  const initPullToRefresh = () => {
    if (!isMobile() || !isTouchDevice()) return;

    let startY = 0;
    let currentY = 0;
    let pulling = false;

    document.addEventListener('touchstart', (e) => {
      if (window.pageYOffset === 0) {
        startY = e.touches[0].pageY;
        pulling = true;
      }
    });

    document.addEventListener('touchmove', (e) => {
      if (!pulling) return;

      currentY = e.touches[0].pageY;
      const pullDistance = currentY - startY;

      if (pullDistance > 100) {
        // Threshold atingido - pode recarregar
      }
    });

    document.addEventListener('touchend', () => {
      const pullDistance = currentY - startY;

      if (pulling && pullDistance > 100) {
        // Recarregar página
        window.location.reload();
      }

      pulling = false;
      startY = 0;
      currentY = 0;
    });
  };

  // ============================================================================
  // INICIALIZAÇÃO
  // ============================================================================
  const init = () => {
    // Executar apenas em dispositivos móveis ou touch
    if (!isMobile() && !isTouchDevice()) {
      return;
    }

    // Inicializar todos os recursos
    initScrollToTop();
    initSidebarMobile();
    initTabelasResponsivas();
    initScrollSuave();
    preventZoomOnInput();
    initBotaoVoltarTopo();
    initModaisMobile();
    initDoubleTapActions();
    initSwipeGestures();
    initLazyLoad();
    initVibracao();
    handleOrientationChange();

    // Pull to refresh é experimental - descomentar se desejar
    // initPullToRefresh();
  };

  // Executar quando DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expor funções úteis globalmente
  window.LK = window.LK || {};
  window.LK.mobile = {
    isMobile,
    isTablet,
    isTouchDevice,
    vibrate
  };

})();
