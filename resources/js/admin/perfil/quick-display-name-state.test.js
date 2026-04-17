import { buildDisplayNameSnapshot, resolveDisplayNameValue } from './quick-display-name-state.js';

describe('perfil/quick-display-name-state', () => {
    it('prioriza o valor principal quando ele vem preenchido', () => {
        expect(resolveDisplayNameValue('  Maria Silva  ', 'Outro Nome')).toBe('Maria Silva');
    });

    it('usa o fallback quando o valor principal esta vazio', () => {
        expect(resolveDisplayNameValue('   ', 'Perfil User')).toBe('Perfil User');
    });

    it('gera first name e inicial a partir do nome salvo', () => {
        expect(buildDisplayNameSnapshot('Maria Silva')).toEqual({
            displayName: 'Maria Silva',
            firstName: 'Maria',
            initial: 'M',
        });
    });
});