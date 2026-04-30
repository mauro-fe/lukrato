<?php

declare(strict_types=1);

namespace Tests\Unit\Casts;

use Application\Casts\MoneyDecimalCast;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyDecimalCastTest extends TestCase
{
    public function testNormalizesCommonMoneyInputsWithoutFloatStringification(): void
    {
        $cast = new MoneyDecimalCast();
        $model = new class extends Model {};

        $this->assertNull($cast->set($model, 'valor', null, []));
        $this->assertNull($cast->set($model, 'valor', '', []));
        $this->assertSame('10.50', $cast->set($model, 'valor', '10.50', []));
        $this->assertSame('10.50', $cast->set($model, 'valor', '10,50', []));
        $this->assertSame('1234.56', $cast->set($model, 'valor', 'R$ 1.234,56', []));
        $this->assertSame('1234.56', $cast->set($model, 'valor', '1,234.56', []));
        $this->assertSame('10.00', $cast->set($model, 'valor', 10, []));
        $this->assertSame('10.51', $cast->set($model, 'valor', 10.505, []));
    }

    public function testRejectsInvalidMoneyInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor monetario invalido.');

        MoneyDecimalCast::normalize('abc');
    }
}
