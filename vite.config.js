import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    root: resolve(__dirname, 'resources/js'),
    base: './',
    build: {
        outDir: resolve(__dirname, 'public/build'),
        emptyOutDir: false,
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                'global': resolve(__dirname, 'resources/js/admin/global/index.js'),
                'lancamento-global': resolve(__dirname, 'resources/js/admin/lancamento-global/index.js'),
                'lancamentos': resolve(__dirname, 'resources/js/admin/lancamentos/index.js'),
                'lancamentos-create': resolve(__dirname, 'resources/js/admin/lancamentos/create.js'),
                'contas': resolve(__dirname, 'resources/js/admin/contas/index.js'),
                'faturas': resolve(__dirname, 'resources/js/admin/faturas/index.js'),
                'cartoes': resolve(__dirname, 'resources/js/admin/cartoes/index.js'),
                'financas': resolve(__dirname, 'resources/js/admin/financas/index.js'),
                'orcamento': resolve(__dirname, 'resources/js/admin/orcamento/index.js'),
                'metas': resolve(__dirname, 'resources/js/admin/metas/index.js'),
                'relatorios': resolve(__dirname, 'resources/js/admin/relatorios/index.js'),
                'dashboard': resolve(__dirname, 'resources/js/admin/dashboard/index.js'),
                'categorias': resolve(__dirname, 'resources/js/admin/categorias/index.js'),
                'cartoes-arquivadas': resolve(__dirname, 'resources/js/admin/cartoes-arquivadas/index.js'),
                'gamification': resolve(__dirname, 'resources/js/admin/gamification/index.js'),
                'gamification-dashboard': resolve(__dirname, 'resources/js/admin/gamification-dashboard/index.js'),
                'billing': resolve(__dirname, 'resources/js/admin/billing/index.js'),
                'perfil': resolve(__dirname, 'resources/js/admin/perfil/index.js'),
                'configuracoes': resolve(__dirname, 'resources/js/admin/configuracoes/index.js'),
                'frontend-pilot': resolve(__dirname, 'resources/js/admin/frontend-pilot/index.js'),
                'importacoes': resolve(__dirname, 'resources/js/admin/importacoes/index.js'),
                'importacoes-configuracoes': resolve(__dirname, 'resources/js/admin/importacoes/configuracoes.js'),
                'importacoes-historico': resolve(__dirname, 'resources/js/admin/importacoes/historico.js'),
                'card-modals': resolve(__dirname, 'resources/js/admin/card-modals/index.js'),
                'sysadmin': resolve(__dirname, 'resources/js/admin/sysadmin/index.js'),
                'contas-arquivadas': resolve(__dirname, 'resources/js/admin/contas-arquivadas/index.js'),
                'sysadmin-communications': resolve(__dirname, 'resources/js/admin/sysadmin/communications.js'),
                'sysadmin-cupons': resolve(__dirname, 'resources/js/admin/sysadmin/cupons.js'),
                'sysadmin-blog': resolve(__dirname, 'resources/js/admin/sysadmin/blog.js'),
                'sysadmin-ai': resolve(__dirname, 'resources/js/admin/sysadmin/ai-chat.js'),
                'sysadmin-ai-logs': resolve(__dirname, 'resources/js/admin/sysadmin/ai-logs.js'),
                // Auth
                'auth-login': resolve(__dirname, 'resources/js/admin/auth/login/index.js'),
                'auth-forgot-password': resolve(__dirname, 'resources/js/admin/auth/forgot-password/index.js'),
                'auth-reset-password': resolve(__dirname, 'resources/js/admin/auth/reset-password/index.js'),
                'auth-verify-email': resolve(__dirname, 'resources/js/admin/auth/verify-email/index.js'),
                'admin-base': resolve(__dirname, 'resources/css/admin/base.css'),
                'auth-login-style': resolve(__dirname, 'resources/css/admin/auth/admin-auth-login.css'),
                'auth-shared-style': resolve(__dirname, 'resources/css/admin/auth/auth-shared.css'),
                'auth-verify-email-style': resolve(__dirname, 'resources/css/admin/auth/auth-verify-email.css'),
                // Site (público)
                'landing-base': resolve(__dirname, 'resources/js/site/landing-base.js'),
                'site-card': resolve(__dirname, 'resources/js/site/card/index.js'),
                'site-google-confirm-page': resolve(__dirname, 'resources/js/site/auth/google-confirm/index.js'),
                // CSS compilado do Tailwind (site público)
                'site-app': resolve(__dirname, 'resources/css/site/app.css'),
                'site-base': resolve(__dirname, 'resources/css/site/base.css'),
                'site-landing': resolve(__dirname, 'resources/css/site/landing-base.css'),
                'site-legal': resolve(__dirname, 'resources/css/site/legal.css'),
                'site-aprenda': resolve(__dirname, 'resources/css/site/aprenda.css'),
                'site-card-style': resolve(__dirname, 'resources/css/site/card.css'),
                'site-google-confirm': resolve(__dirname, 'resources/css/site/auth/google-confirm.css'),
                'error-page': resolve(__dirname, 'resources/css/errors/page.css'),
            }
        }
    },
    server: {
        // Proxy para XAMPP durante desenvolvimento
        proxy: {
            '/lukrato/public/api': {
                target: 'http://localhost',
                changeOrigin: true,
            }
        }
    }
});
