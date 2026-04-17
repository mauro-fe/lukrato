import {
    buildSiteAppUrl,
    buildSiteUrl,
    getSiteApiBaseUrl,
    getSiteBasePath,
    resolveContactSendEndpoint,
} from './engagement.js';

describe('site/api/endpoints/engagement', () => {
    const previousWindow = global.window;
    const previousDocument = global.document;

    afterEach(() => {
        global.window = previousWindow;
        global.document = previousDocument;
    });

    it('resolve o endpoint v1 de contato publico', () => {
        expect(resolveContactSendEndpoint()).toBe('api/v1/contato/enviar');
    });

    it('monta a URL publica preservando base customizada', () => {
        expect(buildSiteUrl(resolveContactSendEndpoint(), 'https://lukrato.test')).toBe('https://lukrato.test/api/v1/contato/enviar');
        expect(buildSiteUrl(resolveContactSendEndpoint(), 'https://lukrato.test/')).toBe('https://lukrato.test/api/v1/contato/enviar');
        expect(buildSiteUrl(resolveContactSendEndpoint(), '')).toBe('/api/v1/contato/enviar');
        expect(buildSiteAppUrl('privacidade', 'https://site.example.test/')).toBe('https://site.example.test/privacidade');
    });

    it('prioriza a api base explicita do site quando disponivel', () => {
        global.window = {
            API_BASE_URL: 'https://api.example.test/',
            APP_BASE_URL: 'https://site.example.test/',
        };
        global.document = {
            querySelector: () => null,
        };

        expect(getSiteApiBaseUrl()).toBe('https://api.example.test');
        expect(buildSiteUrl(resolveContactSendEndpoint())).toBe('https://api.example.test/api/v1/contato/enviar');
    });

    it('resolve o base path do site por APP_BASE_URL e por fallback local', () => {
        global.window = {
            APP_BASE_URL: 'https://site.example.test/lukrato/',
        };
        global.document = {
            querySelector: () => null,
        };

        expect(getSiteBasePath()).toBe('/lukrato/');
        expect(getSiteBasePath('', '/lukrato/public/planos')).toBe('/lukrato/public/');
    });
});