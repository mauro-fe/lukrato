<?php

namespace Application\Validators;

use Application\DTO\CheckoutRequestDTO;
use Application\Models\Plano;

/**
 * Validador de checkout
 */
class CheckoutValidator
{
    public function validate(CheckoutRequestDTO $dto, Plano $plano): void
    {
        $this->validateMonths($dto->months);
        $this->validateDiscount($dto->months, $dto->discount);
        $this->validatePrice($plano);
    }

    private function validateMonths(int $months): void
    {
        if (!in_array($months, [1, 6, 12], true)) {
            throw new \InvalidArgumentException('Período inválido. Selecione mensal, semestral ou anual.');
        }
    }

    private function validateDiscount(int $months, int $discount): void
    {
        $expectedDiscount = $this->getExpectedDiscount($months);

        if ($discount !== $expectedDiscount) {
            throw new \InvalidArgumentException('Desconto inválido para o período selecionado.');
        }
    }

    private function validatePrice(Plano $plano): void
    {
        $valorMensal = $plano->preco_centavos / 100;

        if ($valorMensal <= 0) {
            throw new \InvalidArgumentException('Preço do plano PRO inválido.');
        }
    }

    public function getExpectedDiscount(int $months): int
    {
        return match ($months) {
            1 => 0,
            6 => 10,
            12 => 15,
            default => 0
        };
    }

    public function calculateTotal(float $valorMensal, int $months, int $discount): float
    {
        return round($valorMensal * $months * (1 - ($discount / 100)), 2);
    }
}
