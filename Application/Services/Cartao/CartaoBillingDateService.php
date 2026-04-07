<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

class CartaoBillingDateService
{
    /**
     * @return array{mes:int, ano:int}
     */
    public function calcularCompetencia(string $dataCompra, ?int $diaFechamento = null): array
    {
        $dataObj = new \DateTimeImmutable($dataCompra);

        $mesCompetencia = (int) $dataObj->format('n');
        $anoCompetencia = (int) $dataObj->format('Y');

        if ($diaFechamento !== null && $diaFechamento > 0) {
            $diaCompra = (int) $dataObj->format('j');

            if ($diaCompra >= $diaFechamento) {
                $mesCompetencia++;

                if ($mesCompetencia > 12) {
                    $mesCompetencia = 1;
                    $anoCompetencia++;
                }
            }
        }

        return [
            'mes' => $mesCompetencia,
            'ano' => $anoCompetencia,
        ];
    }

    /**
     * @return array{data:string, mes:int, ano:int}
     */
    public function calcularDataVencimento(string $dataCompra, int $diaVencimento, ?int $diaFechamento = null): array
    {
        $dataObj = new \DateTimeImmutable($dataCompra);
        $mesAtual = (int) $dataObj->format('n');
        $anoAtual = (int) $dataObj->format('Y');
        $diaCompra = (int) $dataObj->format('j');

        if ($diaFechamento === null) {
            $diaFechamento = max(1, $diaVencimento - 5);
        }

        if ($diaCompra >= $diaFechamento) {
            $mesFechamento = $mesAtual + 1;
            $anoFechamento = $anoAtual;
            if ($mesFechamento > 12) {
                $mesFechamento -= 12;
                $anoFechamento++;
            }
        } else {
            $mesFechamento = $mesAtual;
            $anoFechamento = $anoAtual;
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

        $ultimoDiaMes = (int) date('t', mktime(0, 0, 0, $mesVencimento, 1, $anoVencimento));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return [
            'data' => sprintf('%04d-%02d-%02d', $anoVencimento, $mesVencimento, $diaFinal),
            'mes' => $mesVencimento,
            'ano' => $anoVencimento,
        ];
    }

    /**
     * @return array{data:string, mes:int, ano:int}
     */
    public function calcularDataParcelaMes(string $dataCompra, int $diaVencimento, ?int $diaFechamento, int $mesesAFrente): array
    {
        $vencimentoPrimeira = $this->calcularDataVencimento($dataCompra, $diaVencimento, $diaFechamento);

        $dataObj = new \DateTimeImmutable($vencimentoPrimeira['data']);
        $dataObj = $dataObj->modify("+{$mesesAFrente} months");

        $mesAlvo = (int) $dataObj->format('n');
        $anoAlvo = (int) $dataObj->format('Y');
        $ultimoDiaMes = (int) date('t', mktime(0, 0, 0, $mesAlvo, 1, $anoAlvo));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return [
            'data' => sprintf('%04d-%02d-%02d', $anoAlvo, $mesAlvo, $diaFinal),
            'mes' => $mesAlvo,
            'ano' => $anoAlvo,
        ];
    }
}
