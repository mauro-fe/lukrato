<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Application\Models\FaturaCartaoItem;
use PHPUnit\Framework\TestCase;

class FaturaCartaoItemTest extends TestCase
{
    public function testForeignKeysAreCastToIntegersWhenHydratedFromDatabaseStrings(): void
    {
        $item = new FaturaCartaoItem();
        $item->forceFill([
            'user_id' => '7',
            'cartao_credito_id' => '11',
            'fatura_id' => '13',
            'lancamento_id' => '17',
            'categoria_id' => '19',
            'subcategoria_id' => '21',
            'item_pai_id' => '23',
            'recorrencia_pai_id' => '29',
        ]);

        $this->assertSame(7, $item->user_id);
        $this->assertSame(11, $item->cartao_credito_id);
        $this->assertSame(13, $item->fatura_id);
        $this->assertSame(17, $item->lancamento_id);
        $this->assertSame(19, $item->categoria_id);
        $this->assertSame(21, $item->subcategoria_id);
        $this->assertSame(23, $item->item_pai_id);
        $this->assertSame(29, $item->recorrencia_pai_id);
    }
}
