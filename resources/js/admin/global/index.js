/**
 * ============================================================================
 * LUKRATO — Global Infrastructure Bundle (Vite Entry Point)
 * ============================================================================
 * Carrega todos os scripts globais de infraestrutura na ordem correta.
 * Este entry point substitui as ~15 tags <script> individuais no header/footer
 * por um único bundle otimizado (minificado, hash, cache).
 *
 * Ordem de import é CRÍTICA — scripts anteriores definem globals usados pelos
 * posteriores (ex: CsrfManager → lukrato-fetch → lukrato-ui).
 * ============================================================================
 */

// ─── 0. Namespace + Infrastructure (extraído do header.php inline) ──────────
import './lk-namespace.js';        // window.LK namespace, error suppression, page transitions, DOM init
import './sidebar-state.js';       // Sidebar collapsed pre-render (localStorage)
import './scroll-to-top.js';       // AOS init + scroll-to-top button logic

// ─── 1. Core: CSRF + Fetch + Feedback + UI Facade ──────────────────────────
import './csrf-manager.js';        // window.CsrfManager, LK.getCSRF, LK.refreshCSRF
import './lukrato-fetch.js';       // window.lkFetch (LukratoFetch), fetch interceptor
import './lukrato-feedback.js';    // window.LKFeedback, window.showNotification
import './lukrato-ui.js';          // LK.toast, LK.api, LK.confirm, LK.loading

// ─── 2. Gestão e Funcionalidades ────────────────────────────────────────────
import './session-manager.js';     // LK.SessionManager (inatividade, heartbeat)
import './gamification-global.js'; // window.GAMIFICATION, notifyAchievementUnlocked, etc.
import './plan-limits.js';         // window.PlanLimits, fetch interceptor (403/limit)
import './enhancements.js';        // window.showToast, debounce, copyToClipboard

// ─── 3. UI Components ──────────────────────────────────────────────────────
import './lucide-init.js';         // LK.refreshIcons (MutationObserver p/ ícones dinâmicos)
import './accessibility.js';       // window.LKAccessibility (ARIA, focus trap)
import './first-visit-tooltips.js';// window.FirstVisitTooltips (tour primeira visita)
import './tooltips.js';            // Tooltips customizados (hover)
import './admin-home-header.js';   // LK.initHeader, LK.initModals (sidebar, notificações)
import './birthday-modal.js';      // window.BirthdayModal

// ─── 4. Banners & Avisos ────────────────────────────────────────────────────
import '../shared/aviso-lancamentos.js'; // Usage banner (plano free)

// ─── 5. Theme ───────────────────────────────────────────────────────────────
import './soft-ui-dashboard.js';   // Scrollbar, navbar, sidebar, dark mode theme
import './theme-toggle.js';        // Dark/light toggle (top-navbar)

// ─── 6. Avatar Global ────────────────────────────────────────────────────────
import './avatar-global.js';       // Navbar + sidebar avatar (window.__LK_updateGlobalAvatars)

// ─── 7. Partials extraídos (notificações, header, suporte) ─────────────────
import './notification-manager.js'; // NotificationManager + lkNotify (bell.php)
import './month-picker.js';         // LukratoHeader month/year nav (header-mes.php)
import './support-button.js';       // openSupportModal / sendSupportMessage (botao-suporte.php)

// ─── 8. User Feedback Collector ─────────────────────────────────────────────
import './feedback-collector.js';   // Micro feedback, NPS, sugestao, AI feedback (window.LKUserFeedback)
