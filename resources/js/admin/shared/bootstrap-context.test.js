import { readBootstrapContext, resolveBootstrapSource } from './bootstrap-context.js';

describe('admin/shared/bootstrap-context', () => {
    it('le o contexto de bootstrap a partir do dataset do root', () => {
        expect(readBootstrapContext({
            dataset: {
                bootstrapMenu: ' Perfil ',
                bootstrapViewId: ' Admin-Perfil-Index ',
                bootstrapViewPath: ' Admin/Perfil/Index ',
            },
        })).toEqual({
            menu: 'perfil',
            view_id: 'admin-perfil-index',
            view_path: 'admin/perfil/index',
        });
    });

    it('normaliza o payload bruto do bootstrap com ou sem envelope success/data', () => {
        expect(resolveBootstrapSource({
            success: true,
            data: {
                username: 'Maria',
            },
        })).toEqual({
            username: 'Maria',
        });

        expect(resolveBootstrapSource({
            username: 'João',
        })).toEqual({
            username: 'João',
        });
    });
});