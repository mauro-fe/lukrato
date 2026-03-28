/**
 * Celebration animations for dashboard milestones.
 */

class CelebrationSystem {
  constructor() {
    this.initialized = false;
    this.init();
  }

  init() {
    this.setupEventListeners();
    this.initialized = true;
  }

  setupEventListeners() {
    document.addEventListener('lukrato:transaction-added', () => {
      this.playAddedAnimation();
    });

    document.addEventListener('lukrato:level-up', (event) => {
      this.playLevelUpAnimation(event.detail?.level);
    });

    document.addEventListener('lukrato:streak-milestone', (event) => {
      this.playStreakAnimation(event.detail?.days);
    });

    document.addEventListener('lukrato:goal-completed', (event) => {
      this.playGoalAnimation(event.detail?.goalName);
    });

    document.addEventListener('lukrato:achievement-unlocked', (event) => {
      this.playAchievementAnimation(event.detail?.name, event.detail?.icon);
    });
  }

  playAddedAnimation() {
    if (window.fab) {
      window.fab.celebrate();
    }

    if (window.LK?.toast) {
      window.LK.toast.success('Lancamento adicionado com sucesso.');
    }

    this.fireConfetti('small', 0.9, 0.9);
  }

  playLevelUpAnimation(level) {
    this.showCelebrationToast({
      title: `Nivel ${level}`,
      subtitle: 'você subiu de nivel.',
      icon: 'star',
      duration: 3000,
    });

    this.fireConfetti('large', 0.5, 0.3);
    this.screenFlash('#f59e0b', 0.3, 2);

    if (window.fab?.container) {
      window.fab.container.style.animation = 'spin 0.8s ease-out';
      setTimeout(() => {
        window.fab.container.style.animation = '';
      }, 800);
    }
  }

  playStreakAnimation(days) {
    const messages = {
      7: { title: 'Semana perfeita', subtitle: 'você chegou a 7 dias seguidos.' },
      14: { title: 'Duas semanas', subtitle: 'você chegou a 14 dias seguidos.' },
      30: { title: 'Mes epico', subtitle: 'você chegou a 30 dias seguidos.' },
      100: { title: 'Marco historico', subtitle: 'você chegou a 100 dias seguidos.' },
    };

    const message = messages[days] || {
      title: `${days} dias seguidos`,
      subtitle: 'Sua sequencia continua forte.',
    };

    this.showCelebrationModal(message.title, message.subtitle);
    this.fireConfetti('extreme', 0.5, 0.2);
  }

  playGoalAnimation(goalName) {
    this.showCelebrationToast({
      title: 'Meta atingida',
      subtitle: `você completou: ${goalName}`,
      icon: 'target',
      duration: 3500,
    });

    this.fireConfetti('large', 0.5, 0.4);
    this.screenFlash('#10b981', 0.4, 1.5);
  }

  playAchievementAnimation(name, icon) {
    const iconName = this.normalizeIconName(icon);
    const container = document.createElement('div');
    container.className = 'achievement-popup';
    container.innerHTML = `
      <div class="achievement-card">
        <div class="achievement-icon">
          <i data-lucide="${iconName}"></i>
        </div>
        <div class="achievement-title">Conquista desbloqueada</div>
        <div class="achievement-name">${name}</div>
      </div>
    `;

    document.body.appendChild(container);

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }

    setTimeout(() => {
      container.classList.add('show');
    }, 10);

    setTimeout(() => {
      container.classList.remove('show');
      setTimeout(() => container.remove(), 300);
    }, 3500);

    this.fireConfetti('medium', 0.5, 0.6);
  }

  showCelebrationToast(options) {
    const {
      title = 'Parabens',
      subtitle = 'você fez progresso.',
      icon = 'party-popper',
      duration = 3000,
    } = options;

    void icon;
    void duration;

    if (window.LK?.toast) {
      window.LK.toast.success(`${title}\n${subtitle}`);
    }
  }

  showCelebrationModal(title, subtitle) {
    if (typeof Swal === 'undefined') return;

    Swal.fire({
      title,
      text: subtitle,
      icon: 'success',
      confirmButtonText: 'Continuar',
      confirmButtonColor: 'var(--color-primary)',
      allowOutsideClick: false,
      didOpen: () => {
        this.fireConfetti('extreme', 0.5, 0.2);
      },
    });
  }

  normalizeIconName(icon) {
    const value = String(icon || '').trim();
    if (!value) return 'trophy';

    const iconMap = {
      'fa-trophy': 'trophy',
      'fa-award': 'award',
      'fa-medal': 'medal',
      'fa-star': 'star',
      'fa-target': 'target',
    };

    return iconMap[value] || value.replace(/^fa-/, '') || 'trophy';
  }

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

    setTimeout(() => {
      flash.style.transition = `opacity ${duration / 2}ms ease-out`;
      flash.style.opacity = opacity;
    }, 10);

    setTimeout(() => {
      flash.style.transition = `opacity ${duration / 2}ms ease-in`;
      flash.style.opacity = '0';
    }, duration / 2);

    setTimeout(() => flash.remove(), duration);
  }

  fireConfetti(intensity = 'medium', x = 0.5, y = 0.5) {
    if (typeof confetti !== 'function') return;

    const configs = {
      small: { particleCount: 30, spread: 40 },
      medium: { particleCount: 60, spread: 60 },
      large: { particleCount: 100, spread: 90 },
      extreme: { particleCount: 150, spread: 120 },
    };

    const config = configs[intensity] || configs.medium;

    confetti({
      ...config,
      origin: { x, y },
      gravity: 0.8,
      decay: 0.95,
      zIndex: 99999,
    });
  }
}

window.CelebrationSystem = CelebrationSystem;

document.addEventListener('DOMContentLoaded', () => {
  if (!window.celebrationSystem) {
    window.celebrationSystem = new CelebrationSystem();
  }
});
