import {
    resolveTelegramLinkEndpoint,
    resolveTelegramStatusEndpoint,
    resolveTelegramUnlinkEndpoint,
    resolveWhatsAppLinkEndpoint,
    resolveWhatsAppStatusEndpoint,
    resolveWhatsAppUnlinkEndpoint,
    resolveWhatsAppVerifyEndpoint,
} from './integrations.js';

describe('admin/api/endpoints/integrations', () => {
    it('resolve os endpoints v1 de integrações', () => {
        expect(resolveWhatsAppLinkEndpoint()).toBe('api/v1/whatsapp/link');
        expect(resolveWhatsAppVerifyEndpoint()).toBe('api/v1/whatsapp/verify');
        expect(resolveWhatsAppUnlinkEndpoint()).toBe('api/v1/whatsapp/unlink');
        expect(resolveWhatsAppStatusEndpoint()).toBe('api/v1/whatsapp/status');
        expect(resolveTelegramLinkEndpoint()).toBe('api/v1/telegram/link');
        expect(resolveTelegramUnlinkEndpoint()).toBe('api/v1/telegram/unlink');
        expect(resolveTelegramStatusEndpoint()).toBe('api/v1/telegram/status');
    });
});