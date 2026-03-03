import { defineConfig } from 'vite';
import { resolve } from 'path';

// ============================================
// LUKRATO - VITE CONFIG PRODUÇÃO
// ============================================
// Usar para build de produção:
//   npx vite build --config vite.config.producao.js
// ============================================

export default defineConfig({
    root: resolve(__dirname, 'resources/js'),
    base: '/build/',
    build: {
        outDir: resolve(__dirname, 'public/build'),
        emptyOutDir: true,
        manifest: true,
        sourcemap: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        rollupOptions: {
            input: {
                'global': resolve(__dirname, 'resources/js/admin/global/index.js'),
                'lancamento-global': resolve(__dirname, 'resources/js/admin/lancamento-global/index.js'),
                'lancamentos': resolve(__dirname, 'resources/js/admin/lancamentos/index.js'),
                'contas': resolve(__dirname, 'resources/js/admin/contas/index.js'),
                'faturas': resolve(__dirname, 'resources/js/admin/faturas/index.js'),
                'cartoes': resolve(__dirname, 'resources/js/admin/cartoes/index.js'),
                'financas': resolve(__dirname, 'resources/js/admin/financas/index.js'),
                'relatorios': resolve(__dirname, 'resources/js/admin/relatorios/index.js'),
                'dashboard': resolve(__dirname, 'resources/js/admin/dashboard/index.js'),
                'categorias': resolve(__dirname, 'resources/js/admin/categorias/index.js'),
                'cartoes-arquivadas': resolve(__dirname, 'resources/js/admin/cartoes-arquivadas/index.js'),
                'gamification': resolve(__dirname, 'resources/js/admin/gamification/index.js'),
                'gamification-dashboard': resolve(__dirname, 'resources/js/admin/gamification-dashboard/index.js'),
                'billing': resolve(__dirname, 'resources/js/admin/billing/index.js'),
                'perfil': resolve(__dirname, 'resources/js/admin/perfil/index.js'),
                'card-modals': resolve(__dirname, 'resources/js/admin/card-modals/index.js'),
                'sysadmin': resolve(__dirname, 'resources/js/admin/sysadmin/index.js'),
                'contas-arquivadas': resolve(__dirname, 'resources/js/admin/contas-arquivadas/index.js'),
                'onboarding': resolve(__dirname, 'resources/js/admin/onboarding/index.js'),
                'onboarding-lancamento': resolve(__dirname, 'resources/js/admin/onboarding/lancamento.js'),
                'sysadmin-communications': resolve(__dirname, 'resources/js/admin/sysadmin/communications.js'),
                'sysadmin-cupons': resolve(__dirname, 'resources/js/admin/sysadmin/cupons.js'),
                // Auth
                'auth-login': resolve(__dirname, 'resources/js/admin/auth/login/index.js'),
                'auth-forgot-password': resolve(__dirname, 'resources/js/admin/auth/forgot-password/index.js'),
                'auth-reset-password': resolve(__dirname, 'resources/js/admin/auth/reset-password/index.js'),
                'auth-verify-email': resolve(__dirname, 'resources/js/admin/auth/verify-email/index.js'),
                // Site (público)
                'landing-base': resolve(__dirname, 'resources/js/site/landing-base.js'),
                'site-card': resolve(__dirname, 'resources/js/site/card/index.js'),
            }
        }
    },
});
