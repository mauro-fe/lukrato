<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Exception;

class FaturaInstallmentCalculatorService
{
    private const STATUS_PENDENTE = 'pendente';
    private const STATUS_PARCIAL = 'parcial';
    private const STATUS_PAGA = 'paga';

    /**
     * Calcular mês/ano de competência (mês da compra).
     * A competência é sempre o mês da compra.
     */
    public function calcularCompetenciaFatura(
        int $diaCompra,
        int $mesCompra,
        int $anoCompra,
        int $diaFechamento
    ): array {
        return [
            'mes' => $mesCompra,
            'ano' => $anoCompra,
        ];
    }

    /**
     * Calcular valores das parcelas com ajuste de arredondamento.
     */
    public function calcularValoresParcelas(float $valorTotal, int $numeroParcelas): array
    {
        $valorParcela = round($valorTotal / $numeroParcelas, 2);
        $valores = array_fill(0, $numeroParcelas, $valorParcela);

        $soma = $valorParcela * ($numeroParcelas - 1);
        $valores[$numeroParcelas - 1] = round($valorTotal - $soma, 2);

        $somaTotal = array_sum($valores);
        if (abs($somaTotal - $valorTotal) > 0.01) {
            throw new Exception('Erro no cálculo das parcelas');
        }

        return $valores;
    }

    /**
     * Calcular data de vencimento da parcela.
     */
    public function calcularDataVencimento(
        int $diaCompra,
        int $mesCompra,
        int $anoCompra,
        int $numeroParcela,
        int $diaVencimento,
        int $diaFechamento
    ): array {
        $mesFechamento = $mesCompra;
        $anoFechamento = $anoCompra;

        if ($diaCompra >= $diaFechamento) {
            $mesFechamento++;
            if ($mesFechamento > 12) {
                $mesFechamento = 1;
                $anoFechamento++;
            }
        }

        if ($diaVencimento > $diaFechamento) {
            $mesVencimento = $mesFechamento;
            $anoVencimento = $anoFechamento;
        } else {
            $mesVencimento = $mesFechamento + 1;
            $anoVencimento = $anoFechamento;
            if ($mesVencimento > 12) {
                $mesVencimento -= 12;
                $anoVencimento++;
            }
        }

        $mesVencimento += ($numeroParcela - 1);
        while ($mesVencimento > 12) {
            $mesVencimento -= 12;
            $anoVencimento++;
        }

        $ultimoDiaMes = (int) date('t', mktime(0, 0, 0, $mesVencimento, 1, $anoVencimento));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return [
            'data' => sprintf('%04d-%02d-%02d', $anoVencimento, $mesVencimento, $diaFinal),
            'mes' => $mesVencimento,
            'ano' => $anoVencimento,
        ];
    }

    public function determinarStatus(float $progresso): string
    {
        if ($progresso === 0.0) {
            return self::STATUS_PENDENTE;
        }

        if ($progresso >= 100.0) {
            return self::STATUS_PAGA;
        }

        return self::STATUS_PARCIAL;
    }
}
