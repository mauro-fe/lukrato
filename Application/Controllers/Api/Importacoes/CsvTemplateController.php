<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;

class CsvTemplateController extends ApiController
{
    public function __invoke(): Response
    {
        $this->requireApiUserIdAndReleaseSessionOrFail();

        $mode = strtolower(trim($this->request->queryString('mode', 'auto')));
        if (!in_array($mode, ['auto', 'manual'], true)) {
            return Response::validationErrorResponse([
                'mode' => 'Modo inválido para modelo CSV. Use auto ou manual.',
            ]);
        }

        [$filename, $content] = $this->buildTemplate($mode);

        return (new Response())
            ->setStatusCode(200)
            ->withHeaders([
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])
            ->setContent("\xEF\xBB\xBF" . $content);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function buildTemplate(string $mode): array
    {
        if ($mode === 'manual') {
            return [
                'modelo_importacao_manual.csv',
                implode("\n", [
                    'tipo;data;descricao;valor;categoria;subcategoria;observacao;id_externo',
                    'despesa;01/03/2026;Supermercado;149,90;Alimentacao;Mercado;Compra mensal;MAN-0001',
                    'receita;05/03/2026;Salario;3200,00;Renda;Salario;Pagamento mensal;MAN-0002',
                ]),
            ];
        }

        return [
            'modelo_importacao_automatico.csv',
            implode("\n", [
                'tipo;data;descricao;valor',
                'despesa;01/03/2026;Supermercado;149,90',
                'receita;05/03/2026;Salario;3200,00',
            ]),
        ];
    }
}
