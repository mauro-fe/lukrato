import { getOnboardingChecklist, invalidateOnboardingChecklist } from './dashboard-data.js';

const BASE_URL = window.__LK_CONFIG?.baseUrl || window.BASE_URL || '/';
const ONBOARDING_STORAGE_PREFIX = `lk_user_${window.__LK_CONFIG?.userId ?? 'anon'}_`;

function storageKey(name) {
  return ONBOARDING_STORAGE_PREFIX + name;
}

let previousChecklistState = null;

export function initOnboardingChecklist() {
  if (window.__LK_ONBOARDING_CHECKLIST_INIT__) return;
  window.__LK_ONBOARDING_CHECKLIST_INIT__ = true;

  const firstVisit = !!window.__lkFirstVisit;
  const skipKey = storageKey('lk_checklist_skipped');
  const stateKey = storageKey('lk_checklist_state');
  const el = document.getElementById('onboardingChecklist');

  if (!el) return;
  if (localStorage.getItem(skipKey) === '1') return;

  previousChecklistState = localStorage.getItem(stateKey)
    ? JSON.parse(localStorage.getItem(stateKey))
    : {};

  const dismissBtn = document.getElementById('checklistDismiss');
  if (dismissBtn) {
    dismissBtn.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      localStorage.setItem(skipKey, '1');
      hideWidget(el);
    });
  }

  fetchAndRenderChecklist(el, firstVisit);

  setInterval(() => {
    fetchAndRenderChecklist(el, firstVisit, { force: true });
  }, 120000);

  document.addEventListener('lukrato:data-changed', () => {
    invalidateOnboardingChecklist();
    setTimeout(() => fetchAndRenderChecklist(el, firstVisit, { force: true }), 500);
  });
}

function fetchAndRenderChecklist(el, firstVisit, { force = false } = {}) {
  if (localStorage.getItem(storageKey('lk_checklist_skipped')) === '1') {
    hideWidget(el);
    return;
  }

  getOnboardingChecklist({ force })
    .then((res) => {
      if (!res.success) return;

      const data = res.data;
      if (data.all_complete) {
        localStorage.setItem(storageKey('lukrato_onboarding_completed'), 'true');
        if (!firstVisit) {
          hideWidget(el);
          return;
        }
      }

      renderChecklistCompact(data, el);
      el.style.display = 'block';

      detectChecklistChanges(data);

      if (firstVisit) {
        fireConfetti();
      }
    })
    .catch(() => {});
}

function renderChecklistCompact(data, el) {
  const badge = document.getElementById('checklistBadge');
  const fill = document.getElementById('checklistProgressFill');
  const box = document.getElementById('checklistItems');
  const primaryLink = document.getElementById('checklistPrimaryLink');

  if (badge) {
    badge.textContent = `${data.done_count}/${data.total}`;
  }

  if (fill) {
    const pct = data.total > 0 ? (data.done_count / data.total) * 100 : 0;
    fill.style.width = `${pct}%`;
  }

  const pending = data.items
    .filter((item) => !item.done)
    .sort((a, b) => a.priority - b.priority);

  const nextItems = pending.slice(0, 3);
  const nextItem = nextItems[0] || pending[0] || null;

  if (primaryLink && nextItem) {
    primaryLink.href = BASE_URL + nextItem.href;
  }

  if (!box) return;

  if (nextItems.length === 0) {
    box.innerHTML = `
      <div class="lk-onboarding-widget-empty">
        <span class="lk-onboarding-widget-item-label">Seu setup esta completo.</span>
        <span class="lk-onboarding-widget-item-desc">Agora e so manter sua rotina financeira.</span>
      </div>
    `;
    return;
  }

  box.innerHTML = nextItems.map((item) => {
    const content = getFriendlyItemCopy(item);
    return `
      <a href="${BASE_URL}${item.href}" class="lk-onboarding-widget-item" data-item-key="${item.key}">
        <span class="lk-onboarding-widget-item-label">${content.label}</span>
        <span class="lk-onboarding-widget-item-desc">${content.description}</span>
      </a>
    `;
  }).join('');
}

function getFriendlyItemCopy(item) {
  const map = {
    primeira_transacao: {
      label: 'Registre seu primeiro gasto',
      description: 'Comece a acompanhar o que entrou ou saiu.',
    },
    segunda_transacao: {
      label: 'Registre mais uma movimentacao',
      description: 'Dois registros ja deixam o mes mais claro.',
    },
    meta: {
      label: 'Defina uma meta',
      description: 'Escolha um objetivo para o dinheiro que sobra.',
    },
    conta_conectada: {
      label: 'Adicione outra conta ou cartao',
      description: 'Tenha uma visao mais completa do seu dinheiro.',
    },
    orcamento: {
      label: 'Defina um limite de gastos',
      description: 'Evite passar do ponto nas categorias principais.',
    },
  };

  return map[item.key] || {
    label: item.label,
    description: item.description,
  };
}

function detectChecklistChanges(data) {
  const currentState = {};
  data.items.forEach((item) => {
    currentState[item.key] = item.done;
  });

  if (previousChecklistState) {
    data.items.forEach((item) => {
      const wasNotDone = !previousChecklistState[item.key];
      const isNowDone = item.done;

      if (wasNotDone && isNowDone) {
        celebrateChecklistCompletion(item);
      }
    });
  }

  localStorage.setItem(storageKey('lk_checklist_state'), JSON.stringify(currentState));
  previousChecklistState = currentState;
}

function celebrateChecklistCompletion(item) {
  if (window.LK?.toast) {
    window.LK.toast.success(`Item concluido: ${item.label}`);
  }

  if (typeof confetti === 'function') {
    confetti({
      particleCount: 40,
      spread: 65,
      origin: { x: 0.5, y: 0.3 }
    });
  }

  document.dispatchEvent(new CustomEvent('lukrato:checklist-item-completed', {
    detail: {
      key: item.key,
      label: item.label,
      points: item.points,
      icon: item.icon
    }
  }));
}

function fireConfetti() {
  if (typeof confetti !== 'function') return;

  confetti({
    particleCount: 60,
    spread: 80,
    origin: { x: 0.5, y: 0.25 }
  });
}

function hideWidget(el) {
  el.style.opacity = '0';
  el.style.transform = 'translateY(-12px)';
  el.style.transition = 'all 0.25s ease';
  setTimeout(() => {
    el.style.display = 'none';
  }, 250);
}
