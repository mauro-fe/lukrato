/**
 * =====================================================================
 * CELEBRATION ANIMATIONS SYSTEM
 * Dispara animações quando milestones são atingidos
 * - Level up
 * - Streak goals (7, 14, 30 dias)
 * - First transaction
 * - Goal completion
 * ===================================================================== */

class CelebrationSystem {
  constructor() {
    this.initialized = false;
    this.init();
  }

  init() {
    // Listen to various events
    this.setupEventListeners();
    this.initialized = true;
  }

  setupEventListeners() {
    // Transaction added
    document.addEventListener('lukrato:transaction-added', () => {
      this.playAddedAnimation();
    });

    // Level up
    document.addEventListener('lukrato:level-up', (e) => {
      this.playLevelUpAnimation(e.detail?.level);
    });

    // Streak milestones
    document.addEventListener('lukrato:streak-milestone', (e) => {
      this.playStreakAnimation(e.detail?.days);
    });

    // Goal completed
    document.addEventListener('lukrato:goal-completed', (e) => {
      this.playGoalAnimation(e.detail?.goalName);
    });

    // Achievement unlocked
    document.addEventListener('lukrato:achievement-unlocked', (e) => {
      this.playAchievementAnimation(e.detail?.name, e.detail?.icon);
    });
  }

  /**
   * Simple success animation + toast
   */
  playAddedAnimation() {
    // Pulse the FAB
    if (window.fab) {
      window.fab.celebrate();
    }

    // Toast
    if (window.LK?.toast) {
      window.LK.toast.success('✅ Lançamento adicionado!');
    }

    // Small confetti
    this.fireConfetti('small', 0.9, 0.9);
  }

  /**
   * Level up animation com particles
   */
  playLevelUpAnimation(level) {
    // Toast principal
    this.showCelebrationToast({
      title: `⭐ Nível ${level}!`,
      subtitle: `Parabéns! Você subiu de nível`,
      icon: 'star',
      duration: 3000
    });

    // Confetti grande
    this.fireConfetti('large', 0.5, 0.3);

    // Flash screen
    this.screenFlash('#f59e0b', 0.3, 2);

    // Viração FAB
    if (window.fab?.container) {
      window.fab.container.style.animation = 'spin 0.8s ease-out';
      setTimeout(() => {
        window.fab.container.style.animation = '';
      }, 800);
    }
  }

  /**
   * Streak milestone animation
   */
  playStreakAnimation(days) {
    const messages = {
      7: { title: '🔥 Semana Perfeita!', subtitle: '7 dias de série!' },
      14: { title: '🌟 Duas Semanas!', subtitle: '14 dias de série!' },
      30: { title: '👑 Mês Épico!', subtitle: '30 dias de série!' },
      100: { title: '🚀 Lendário!', subtitle: '100 dias de série!' }
    };

    const msg = messages[days] || { title: `🔥 ${days} dias!`, subtitle: 'Série em alta!' };

    // Modal celebration
    this.showCelebrationModal(msg.title, msg.subtitle);

    // Confetti customizado
    this.fireConfetti('extreme', 0.5, 0.2);
  }

  /**
   * Goal completion animation
   */
  playGoalAnimation(goalName) {
    this.showCelebrationToast({
      title: `🎯 Meta Atingida!`,
      subtitle: `Você completou: ${goalName}`,
      icon: 'target',
      duration: 3500
    });

    this.fireConfetti('large', 0.5, 0.4);
    this.screenFlash('#10b981', 0.4, 1.5);
  }

  /**
   * Achievement unlocked animation
   */
  playAchievementAnimation(name, icon) {
    const container = document.createElement('div');
    container.className = 'achievement-popup';
    container.innerHTML = `
      <div class="achievement-card">
        <div class="achievement-icon">${icon || '🏆'}</div>
        <div class="achievement-title">Conquista Desbloqueada!</div>
        <div class="achievement-name">${name}</div>
      </div>
    `;
    document.body.appendChild(container);

    // Trigger animation
    setTimeout(() => {
      container.classList.add('show');
    }, 10);

    // Remove after animation
    setTimeout(() => {
      container.classList.remove('show');
      setTimeout(() => container.remove(), 300);
    }, 3500);

    // Confetti
    this.fireConfetti('medium', 0.5, 0.6);
  }

  /**
   * Show toast celebration
   */
  showCelebrationToast(options) {
    const {
      title = '🎉 Parabéns!',
      subtitle = 'Você fez progresso!',
      icon = 'party-popper',
      duration = 3000
    } = options;

    if (window.LK?.toast) {
      window.LK.toast.success(
        `${title}\n${subtitle}`
      );
    }
  }

  /**
   * Show modal celebration
   */
  showCelebrationModal(title, subtitle) {
    if (typeof Swal === 'undefined') return;

    Swal.fire({
      title,
      text: subtitle,
      icon: 'success',
      confirmButtonText: '🎉 Incrível!',
      confirmButtonColor: 'var(--color-primary)',
      allowOutsideClick: false,
      didOpen: () => {
        // Confetti ao abrir
        this.fireConfetti('extreme', 0.5, 0.2);
      }
    });
  }

  /**
   * Screen flash effect
   */
  screenFlash(color = '#10b981', opacity = 0.3, duration = 1) {
    const flash = document.createElement('div');
    flash.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: ${color};
      opacity: 0;
      z-index: 99999;
      pointer-events: none;
      transition: none;
    `;

    document.body.appendChild(flash);

    // Flash in
    setTimeout(() => {
      flash.style.transition = `opacity ${duration / 2}ms ease-out`;
      flash.style.opacity = opacity;
    }, 10);

    // Flash out
    setTimeout(() => {
      flash.style.transition = `opacity ${duration / 2}ms ease-in`;
      flash.style.opacity = '0';
    }, duration / 2);

    // Remove
    setTimeout(() => flash.remove(), duration);
  }

  /**
   * Fire confetti
   */
  fireConfetti(intensity = 'medium', x = 0.5, y = 0.5) {
    if (typeof confetti !== 'function') return;

    const configs = {
      small: { particleCount: 30, spread: 40 },
      medium: { particleCount: 60, spread: 60 },
      large: { particleCount: 100, spread: 90 },
      extreme: { particleCount: 150, spread: 120 }
    };

    const config = configs[intensity] || configs.medium;

    confetti({
      ...config,
      origin: { x, y },
      gravity: 0.8,
      decay: 0.95,
      zIndex: 99999
    });
  }
}

// ─── Export & Initialize ──────────────────────────────────────────────
window.CelebrationSystem = CelebrationSystem;

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
  if (!window.celebrationSystem) {
    window.celebrationSystem = new CelebrationSystem();
  }
});
