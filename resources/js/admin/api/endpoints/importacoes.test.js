import {
    resolveImportacaoHistoricoEndpoint,
    resolveImportacaoJobStatusEndpoint,
    resolveImportacoesConfiguracoesEndpoint,
    resolveImportacoesConfiguracoesPageInitEndpoint,
    resolveImportacoesConfirmEndpoint,
    resolveImportacoesCsvTemplateEndpoint,
    resolveImportacoesHistoricoEndpoint,
    resolveImportacoesHistoricoPageInitEndpoint,
    resolveImportacoesJobsEndpoint,
    resolveImportacoesPageInitEndpoint,
    resolveImportacoesPreviewEndpoint,
} from './importacoes.js';

describe('admin/api/endpoints/importacoes', () => {
    it('resolve os endpoints v1 de configuracao, preview, confirmacao, historico e jobs', () => {
        expect(resolveImportacoesPageInitEndpoint()).toBe('api/v1/importacoes/page-init');
        expect(resolveImportacoesConfiguracoesPageInitEndpoint()).toBe('api/v1/importacoes/configuracoes/page-init');
        expect(resolveImportacoesConfiguracoesEndpoint()).toBe('api/v1/importacoes/configuracoes');
        expect(resolveImportacoesPreviewEndpoint()).toBe('api/v1/importacoes/preview');
        expect(resolveImportacoesConfirmEndpoint()).toBe('api/v1/importacoes/confirm');
        expect(resolveImportacoesHistoricoPageInitEndpoint()).toBe('api/v1/importacoes/historico/page-init');
        expect(resolveImportacoesHistoricoEndpoint()).toBe('api/v1/importacoes/historico');
        expect(resolveImportacaoHistoricoEndpoint(19)).toBe('api/v1/importacoes/historico/19');
        expect(resolveImportacoesJobsEndpoint()).toBe('api/v1/importacoes/jobs');
        expect(resolveImportacaoJobStatusEndpoint(8)).toBe('api/v1/importacoes/jobs/8');
    });

    it('resolve o download dos modelos CSV normalizando modo e alvo', () => {
        expect(resolveImportacoesCsvTemplateEndpoint()).toBe('api/v1/importacoes/modelos/csv?mode=auto&target=conta');
        expect(resolveImportacoesCsvTemplateEndpoint({ mode: 'manual', target: 'cartao' }))
            .toBe('api/v1/importacoes/modelos/csv?mode=manual&target=cartao');
        expect(resolveImportacoesCsvTemplateEndpoint({ mode: 'qualquer', target: 'outro' }))
            .toBe('api/v1/importacoes/modelos/csv?mode=auto&target=conta');
    });
});