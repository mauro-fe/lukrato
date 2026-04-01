<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use Application\DTO\ProvisaoResultDTO;
use Application\Enums\LancamentoTipo;
use Application\Services\Dashboard\DashboardProvisaoService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class DashboardProvisaoServiceTest extends TestCase
{
    private DashboardProvisaoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardProvisaoService();
    }

    public function testSumPendentesSeparatesReceitasAndDespesas(): void
    {
        $pendentes = new Collection([
            (object) ['tipo' => LancamentoTipo::DESPESA->value, 'valor' => 120.50],
            (object) ['tipo' => LancamentoTipo::DESPESA->value, 'valor' => 79.50],
            (object) ['tipo' => LancamentoTipo::RECEITA->value, 'valor' => 350.00],
        ]);

        $result = $this->invokePrivate('sumPendentes', [$pendentes]);

        $this->assertSame([200.0, 350.0, 2, 1], $result);
    }

    public function testGroupByCartaoAggregatesTotalsAndKeepsLatestDueDate(): void
    {
        $itens = new Collection([
            (object) [
                'cartao_credito_id' => 10,
                'valor' => 40.00,
                'data_vencimento' => '2026-03-05',
            ],
            (object) [
                'cartao_credito_id' => 10,
                'valor' => 25.50,
                'data_vencimento' => '2026-03-12',
            ],
            (object) [
                'cartao_credito_id' => 22,
                'valor' => 80.00,
                'data_vencimento' => '2026-03-07',
            ],
        ]);

        $grouped = $this->invokePrivate('groupByCartao', [$itens]);

        $this->assertSame(65.5, $grouped[10]['total']);
        $this->assertSame(2, $grouped[10]['itens']);
        $this->assertSame('2026-03-12', $grouped[10]['data_vencimento']);
        $this->assertSame(80.0, $grouped[22]['total']);
        $this->assertSame(1, $grouped[22]['itens']);
    }

    public function testBuildResponseMergesAndSortsProjectionData(): void
    {
        $proximos = new Collection([
            ['id' => 1, 'titulo' => 'Internet', 'valor' => 120.0, 'data_pagamento' => '2026-03-20'],
            ['id' => 2, 'titulo' => 'Aluguel', 'valor' => 900.0, 'data_pagamento' => '2026-03-10'],
            ['id' => 3, 'titulo' => 'Academia', 'valor' => 90.0, 'data_pagamento' => '2026-03-28'],
            ['id' => 4, 'titulo' => 'Streaming', 'valor' => 35.0, 'data_pagamento' => '2026-03-18'],
        ]);
        $proximosFaturas = [
            ['id' => 'fatura_marco', 'titulo' => 'Fatura Visa', 'valor' => 450.0, 'data_pagamento' => '2026-03-05'],
            ['id' => 'fatura_extra', 'titulo' => 'Fatura Master', 'valor' => 110.0, 'data_pagamento' => '2026-03-26'],
        ];

        $vencidosData = [
            'items' => new Collection([
                ['id' => 10, 'titulo' => 'Conta de luz', 'valor' => 130.0, 'data_pagamento' => '2026-02-02'],
                ['id' => 11, 'titulo' => 'Condominio', 'valor' => 70.0, 'data_pagamento' => '2026-02-15'],
            ]),
            'despesas' => new Collection([
                (object) ['valor' => 130.0],
                (object) ['valor' => 70.0],
            ]),
            'receitas' => new Collection([
                (object) ['valor' => 50.0],
            ]),
        ];
        $vencidosFaturas = [
            ['id' => 'fatura_vencida_10', 'titulo' => 'Fatura vencida', 'valor' => 200.0, 'data_pagamento' => '2026-02-08'],
        ];

        $result = $this->invokePrivate('buildResponse', [
            '2026-03',
            1000.0,
            300.0,
            600.0,
            2,
            1,
            450.0,
            1,
            $proximos,
            $proximosFaturas,
            $vencidosData,
            $vencidosFaturas,
            200.0,
            1,
            3,
            180.0,
        ]);

        $this->assertInstanceOf(ProvisaoResultDTO::class, $result);

        $payload = $result->toArray();

        $this->assertSame('2026-03', $payload['month']);
        $this->assertSame(750.0, $payload['provisao']['a_pagar']);
        $this->assertSame(600.0, $payload['provisao']['a_receber']);
        $this->assertSame(850.0, $payload['provisao']['saldo_projetado']);
        $this->assertSame(1, $payload['provisao']['count_faturas']);
        $this->assertSame(450.0, $payload['provisao']['total_faturas']);

        $this->assertCount(5, $payload['proximos']);
        $this->assertSame(
            ['fatura_marco', 2, 4, 1, 'fatura_extra'],
            array_column($payload['proximos'], 'id')
        );

        $this->assertSame(3, $payload['vencidos']['count']);
        $this->assertSame(400.0, $payload['vencidos']['total']);
        $this->assertSame(1, $payload['vencidos']['count_faturas']);
        $this->assertSame(200.0, $payload['vencidos']['total_faturas']);
        $this->assertSame(2, $payload['vencidos']['despesas']['count']);
        $this->assertSame(200.0, $payload['vencidos']['despesas']['total']);
        $this->assertSame(1, $payload['vencidos']['receitas']['count']);
        $this->assertSame(50.0, $payload['vencidos']['receitas']['total']);
        $this->assertCount(3, $payload['vencidos']['items']);

        $this->assertSame(3, $payload['parcelas']['ativas']);
        $this->assertSame(180.0, $payload['parcelas']['total_mensal']);
    }

    private function invokePrivate(string $method, array $arguments = []): mixed
    {
        $caller = \Closure::bind(
            fn (...$args) => $this->{$method}(...$args),
            $this->service,
            $this->service
        );

        return $caller(...$arguments);
    }
}


