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

        $target = strtolower(trim($this->request->queryString('target', 'conta')));
        if (!in_array($target, ['conta', 'cartao'], true)) {
            return Response::validationErrorResponse([
                'target' => 'Alvo inválido para modelo CSV. Use conta ou cartao.',
            ]);
        }

        [$filename, $content] = $this->buildTemplate($mode, $target);

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
    private function buildTemplate(string $mode, string $target): array
    {
        if ($target === 'cartao') {
            return $this->buildCardTemplate($mode);
        }

        if ($mode === 'manual') {
            return [
                'modelo_importacao_manual.csv',
                $this->buildCsvContent([
                    'tipo;data;descricao;valor;categoria;subcategoria;observacao;id_externo',
                    'despesa;01/03/2026;Supermercado;149,90;Alimentacao;Mercado;Compra mensal;MAN-0001',
                    'receita;05/03/2026;Salario;3200,00;Renda;Salario;Pagamento mensal;MAN-0002',
                ]),
            ];
        }

        return [
            'modelo_importacao_automatico.csv',
            $this->buildCsvContent([
                'tipo;data;descricao;valor',
                'despesa;01/03/2026;Supermercado;149,90',
                'receita;05/03/2026;Salario;3200,00',
            ]),
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function buildCardTemplate(string $mode): array
    {
        if ($mode === 'manual') {
            return [
                'modelo_importacao_cartao_manual.csv',
                $this->buildCsvContent([
                    'data;descricao;valor;observacao;id_externo',
                    '05/03/2026;Restaurante;220,90;Compra presencial;FAT-0001',
                    '06/03/2026;Estorno parcial;-40,00;Ajuste da operadora;FAT-0002',
                ]),
            ];
        }

        return [
            'modelo_importacao_cartao_automatico.csv',
            $this->buildCsvContent([
                'data;descricao;valor',
                '05/03/2026;Restaurante;220,90',
                '06/03/2026;Estorno parcial;-40,00',
            ]),
        ];
    }

    /**
     * @param array<int, string> $lines
     */
    private function buildCsvContent(array $lines): string
    {
        return implode("\n", array_merge(
            ['sep=;'],
            $lines
        ));
    }
}
