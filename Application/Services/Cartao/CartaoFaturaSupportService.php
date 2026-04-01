<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Models\Fatura;
use Application\Services\Infrastructure\LogService;

class CartaoFaturaSupportService
{
    public function buscarOuCriarFatura(int $userId, int $cartaoId, int $mes, int $ano): Fatura
    {
        $descricao = "Fatura {$mes}/{$ano}";

        $fatura = Fatura::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->where('descricao', $descricao)
            ->first();

        if (!$fatura) {
            $fatura = Fatura::create([
                'user_id' => $userId,
                'cartao_credito_id' => $cartaoId,
                'descricao' => $descricao,
                'valor_total' => $this->moneyString(0),
                'numero_parcelas' => 0,
                'data_compra' => date('Y-m-d'),
            ]);

            LogService::info('[CARTAO] Nova fatura mensal criada', [
                'fatura_id' => $fatura->id,
                'cartao_id' => $cartaoId,
                'mes' => $mes,
                'ano' => $ano,
            ]);
        }

        return $fatura;
    }

    public function incrementarValorFatura(Fatura $fatura, float|int|string $valor): void
    {
        $novoTotal = (float) $fatura->valor_total + (float) $valor;
        $fatura->valor_total = $this->moneyString($novoTotal);
        $fatura->save();
    }

    public function moneyString(float|int|string|null $valor): string
    {
        return number_format((float) ($valor ?? 0), 2, '.', '');
    }
}
