<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Services\CartaoCreditoLancamentoService;
use Application\Services\FaturaService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Testes para a lógica de cálculo de vencimento de fatura de cartão de crédito.
 * 
 * Regra de negócio:
 * - Se a compra foi feita ANTES do dia de fechamento: entra na fatura do mês atual
 * - Se a compra foi feita NO DIA ou DEPOIS do fechamento: entra na fatura do próximo mês
 * - O dia de vencimento determina se a fatura vence no mesmo mês ou no mês seguinte ao fechamento:
 *   - Se dia_vencimento > dia_fechamento: vencimento no MESMO mês do fechamento
 *   - Se dia_vencimento <= dia_fechamento: vencimento no mês SEGUINTE ao fechamento
 */
class CartaoCreditoVencimentoTest extends TestCase
{
    private CartaoCreditoLancamentoService $lancamentoService;
    private FaturaService $faturaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lancamentoService = new CartaoCreditoLancamentoService();
        $this->faturaService = new FaturaService();
    }

    /**
     * Helper para acessar método privado calcularDataVencimento do CartaoCreditoLancamentoService
     */
    private function calcularVencimentoLancamento(string $dataCompra, int $diaVencimento, ?int $diaFechamento = null): array
    {
        $method = new ReflectionMethod(CartaoCreditoLancamentoService::class, 'calcularDataVencimento');
        $method->setAccessible(true);
        return $method->invoke($this->lancamentoService, $dataCompra, $diaVencimento, $diaFechamento);
    }

    /**
     * Helper para acessar método privado calcularDataVencimento do FaturaService
     */
    private function calcularVencimentoFatura(int $diaCompra, int $mesCompra, int $anoCompra, int $numeroParcela, int $diaVencimento, int $diaFechamento): array
    {
        $method = new ReflectionMethod(FaturaService::class, 'calcularDataVencimento');
        $method->setAccessible(true);
        return $method->invoke($this->faturaService, $diaCompra, $mesCompra, $anoCompra, $numeroParcela, $diaVencimento, $diaFechamento);
    }

    // ========================================================================
    // CASO DO USUÁRIO: fecha=2, vence=10
    // ========================================================================

    /**
     * Cenário do usuário: fecha=2, vence=10
     * Compra em 02/02/2025 (no dia do fechamento) → vence 10/03/2025
     */
    public function testFecha2Vence10_CompraNoFechamento_VenceProximoMes(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-02-02', 10, 2);
        
        $this->assertEquals('2025-03-10', $result['data'], 'Compra no dia do fechamento deve ir para fatura do próximo mês');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * Cenário do usuário: fecha=2, vence=10
     * Compra em 03/02/2025 (após fechamento) → vence 10/03/2025
     */
    public function testFecha2Vence10_CompraAposFechamento_VenceProximoMes(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-02-03', 10, 2);
        
        $this->assertEquals('2025-03-10', $result['data'], 'Compra após fechamento deve ir para fatura do próximo mês');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * Cenário do usuário: fecha=2, vence=10
     * Compra em 15/02/2025 (bem após fechamento) → vence 10/03/2025
     */
    public function testFecha2Vence10_CompraMetadoMes_VenceProximoMes(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-02-15', 10, 2);
        
        $this->assertEquals('2025-03-10', $result['data'], 'Compra no meio do mês deve ir para fatura do próximo mês');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * Cenário do usuário: fecha=2, vence=10
     * Compra em 28/02/2025 (último dia, após fechamento) → vence 10/03/2025
     */
    public function testFecha2Vence10_CompraFimMes_VenceProximoMes(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-02-28', 10, 2);
        
        $this->assertEquals('2025-03-10', $result['data'], 'Compra no fim do mês deve ir para fatura do próximo mês');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * Cenário do usuário: fecha=2, vence=10
     * Compra em 01/02/2025 (ANTES do fechamento) → vence 10/02/2025
     */
    public function testFecha2Vence10_CompraAntesFechamento_VenceMesmoMes(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-02-01', 10, 2);
        
        $this->assertEquals('2025-02-10', $result['data'], 'Compra antes do fechamento deve ir para fatura do mês atual');
        $this->assertEquals(2, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    // ========================================================================
    // CASO: fecha=25, vence=5 (fechamento DEPOIS do vencimento)
    // ========================================================================

    /**
     * fecha=25, vence=5
     * Compra em 24/01/2025 (antes do fechamento) → fecha Jan, vence Feb 5
     */
    public function testFecha25Vence5_CompraAntesFechamento(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-01-24', 5, 25);
        
        $this->assertEquals('2025-02-05', $result['data'], 'fecha=25,vence=5: compra antes do fechamento deve vencer no mês seguinte ao fechamento');
        $this->assertEquals(2, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * fecha=25, vence=5
     * Compra em 25/01/2025 (no dia do fechamento) → fecha Fev, vence Mar 5
     */
    public function testFecha25Vence5_CompraNoFechamento(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-01-25', 5, 25);
        
        $this->assertEquals('2025-03-05', $result['data'], 'fecha=25,vence=5: compra no fechamento deve vencer 2 meses à frente');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * fecha=25, vence=5
     * Compra em 26/01/2025 (após fechamento) → fecha Fev, vence Mar 5
     */
    public function testFecha25Vence5_CompraAposFechamento(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-01-26', 5, 25);
        
        $this->assertEquals('2025-03-05', $result['data'], 'fecha=25,vence=5: compra após fechamento deve vencer 2 meses à frente');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    // ========================================================================
    // CASO: fecha=10, vence=15 (fechamento ANTES do vencimento, mesmo mês)
    // ========================================================================

    /**
     * fecha=10, vence=15
     * Compra em 09/03/2025 (antes do fechamento) → fecha Mar, vence Mar 15
     */
    public function testFecha10Vence15_CompraAntesFechamento(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-03-09', 15, 10);
        
        $this->assertEquals('2025-03-15', $result['data'], 'fecha=10,vence=15: compra antes do fechamento deve vencer no mesmo mês');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * fecha=10, vence=15
     * Compra em 10/03/2025 (no dia do fechamento) → fecha Abr, vence Abr 15
     */
    public function testFecha10Vence15_CompraNoFechamento(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-03-10', 15, 10);
        
        $this->assertEquals('2025-04-15', $result['data'], 'fecha=10,vence=15: compra no fechamento deve vencer no próximo mês');
        $this->assertEquals(4, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * fecha=10, vence=15
     * Compra em 11/03/2025 (após fechamento) → fecha Abr, vence Abr 15
     */
    public function testFecha10Vence15_CompraAposFechamento(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-03-11', 15, 10);
        
        $this->assertEquals('2025-04-15', $result['data'], 'fecha=10,vence=15: compra após fechamento deve vencer no próximo mês');
        $this->assertEquals(4, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    // ========================================================================
    // CASOS DE VIRADA DE ANO
    // ========================================================================

    /**
     * fecha=2, vence=10 - Compra em 02/12/2025 → vence 10/01/2026
     */
    public function testViradaAno_Fecha2Vence10_CompraNoFechamentoDezembro(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-12-02', 10, 2);
        
        $this->assertEquals('2026-01-10', $result['data'], 'Compra no fechamento em dezembro deve ir para janeiro do próximo ano');
        $this->assertEquals(1, $result['mes']);
        $this->assertEquals(2026, $result['ano']);
    }

    /**
     * fecha=2, vence=10 - Compra em 01/12/2025 → vence 10/12/2025
     */
    public function testViradaAno_Fecha2Vence10_CompraAntesFechamentoDezembro(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-12-01', 10, 2);
        
        $this->assertEquals('2025-12-10', $result['data'], 'Compra antes do fechamento em dezembro deve vencer em dezembro');
        $this->assertEquals(12, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * fecha=25, vence=5 - Compra em 25/12/2025 → fecha Jan/2026, vence Fev/2026
     */
    public function testViradaAno_Fecha25Vence5_CompraNoFechamentoDezembro(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-12-25', 5, 25);
        
        $this->assertEquals('2026-02-05', $result['data'], 'fecha=25,vence=5: compra no fechamento em dez deve vencer em fev do próximo ano');
        $this->assertEquals(2, $result['mes']);
        $this->assertEquals(2026, $result['ano']);
    }

    /**
     * fecha=25, vence=5 - Compra em 24/12/2025 → fecha Dez/2025, vence Jan/2026
     */
    public function testViradaAno_Fecha25Vence5_CompraAntesFechamentoDezembro(): void
    {
        $result = $this->calcularVencimentoLancamento('2025-12-24', 5, 25);
        
        $this->assertEquals('2026-01-05', $result['data'], 'fecha=25,vence=5: compra antes do fechamento em dez deve vencer em jan do próximo ano');
        $this->assertEquals(1, $result['mes']);
        $this->assertEquals(2026, $result['ano']);
    }

    // ========================================================================
    // CASOS DE FEVEREIRO (mês com 28/29 dias)
    // ========================================================================

    /**
     * Vencimento dia 30, mas fevereiro só tem 28 dias → deve ajustar para 28
     */
    public function testAjusteDiaFevereiro(): void
    {
        // fecha=2, vence=30 - Compra em 01/01/2025 → fecha Jan, vence Jan 30
        $result = $this->calcularVencimentoLancamento('2025-01-01', 30, 2);
        $this->assertEquals('2025-01-30', $result['data']);

        // fecha=2, vence=30 - Compra em 02/01/2025 → fecha Fev, vence Fev 28
        $result = $this->calcularVencimentoLancamento('2025-01-02', 30, 2);
        $this->assertEquals('2025-02-28', $result['data'], 'Dia 30 deve ser ajustado para 28 em fevereiro');
    }

    // ========================================================================
    // TESTES PARA FaturaService::calcularDataVencimento (PARCELAMENTO)
    // ========================================================================

    /**
     * FaturaService: fecha=2, vence=10
     * Compra em 02/02/2025 (no fechamento), parcela 1 → vence 10/03/2025
     */
    public function testFaturaService_Fecha2Vence10_Parcela1_CompraNoFechamento(): void
    {
        $result = $this->calcularVencimentoFatura(2, 2, 2025, 1, 10, 2);
        
        $this->assertEquals('2025-03-10', $result['data'], 'FaturaService: parcela 1, compra no fechamento deve vencer no próximo mês');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * FaturaService: fecha=2, vence=10
     * Compra em 02/02/2025 (no fechamento), parcela 2 → vence 10/04/2025
     */
    public function testFaturaService_Fecha2Vence10_Parcela2_CompraNoFechamento(): void
    {
        $result = $this->calcularVencimentoFatura(2, 2, 2025, 2, 10, 2);
        
        $this->assertEquals('2025-04-10', $result['data'], 'FaturaService: parcela 2, compra no fechamento deve vencer 2 meses à frente');
        $this->assertEquals(4, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * FaturaService: fecha=2, vence=10
     * Compra em 02/02/2025 (no fechamento), parcela 3 → vence 10/05/2025
     */
    public function testFaturaService_Fecha2Vence10_Parcela3_CompraNoFechamento(): void
    {
        $result = $this->calcularVencimentoFatura(2, 2, 2025, 3, 10, 2);
        
        $this->assertEquals('2025-05-10', $result['data'], 'FaturaService: parcela 3, compra no fechamento deve vencer 3 meses à frente');
        $this->assertEquals(5, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * FaturaService: fecha=2, vence=10
     * Compra em 01/02/2025 (antes do fechamento), parcela 1 → vence 10/02/2025
     */
    public function testFaturaService_Fecha2Vence10_Parcela1_CompraAntesFechamento(): void
    {
        $result = $this->calcularVencimentoFatura(1, 2, 2025, 1, 10, 2);
        
        $this->assertEquals('2025-02-10', $result['data'], 'FaturaService: parcela 1, compra antes do fechamento deve vencer no mês atual');
        $this->assertEquals(2, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * FaturaService: fecha=25, vence=5
     * Compra em 25/01/2025 (no fechamento), parcela 1 → vence 05/03/2025
     */
    public function testFaturaService_Fecha25Vence5_Parcela1_CompraNoFechamento(): void
    {
        $result = $this->calcularVencimentoFatura(25, 1, 2025, 1, 5, 25);
        
        $this->assertEquals('2025-03-05', $result['data'], 'FaturaService: fecha=25,vence=5, parcela 1, compra no fechamento');
        $this->assertEquals(3, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * FaturaService: fecha=25, vence=5
     * Compra em 24/01/2025 (antes do fechamento), parcela 1 → vence 05/02/2025
     */
    public function testFaturaService_Fecha25Vence5_Parcela1_CompraAntesFechamento(): void
    {
        $result = $this->calcularVencimentoFatura(24, 1, 2025, 1, 5, 25);
        
        $this->assertEquals('2025-02-05', $result['data'], 'FaturaService: fecha=25,vence=5, parcela 1, compra antes do fechamento');
        $this->assertEquals(2, $result['mes']);
        $this->assertEquals(2025, $result['ano']);
    }

    /**
     * FaturaService: virada de ano com parcelamento
     * fecha=2, vence=10, compra em 02/11/2025, 3 parcelas
     * Parcela 1 → 10/12/2025
     * Parcela 2 → 10/01/2026  
     * Parcela 3 → 10/02/2026
     */
    public function testFaturaService_ViradaAno_Parcelamento(): void
    {
        // Parcela 1
        $r1 = $this->calcularVencimentoFatura(2, 11, 2025, 1, 10, 2);
        $this->assertEquals('2025-12-10', $r1['data'], 'Parcela 1 deve vencer em dezembro');

        // Parcela 2
        $r2 = $this->calcularVencimentoFatura(2, 11, 2025, 2, 10, 2);
        $this->assertEquals('2026-01-10', $r2['data'], 'Parcela 2 deve vencer em janeiro do próximo ano');

        // Parcela 3
        $r3 = $this->calcularVencimentoFatura(2, 11, 2025, 3, 10, 2);
        $this->assertEquals('2026-02-10', $r3['data'], 'Parcela 3 deve vencer em fevereiro do próximo ano');
    }

    // ========================================================================
    // TESTES PARA calcularDataParcelaMes do CartaoCreditoLancamentoService
    // ========================================================================

    /**
     * Helper para acessar calcularDataParcelaMes
     */
    private function calcularParcelaMes(string $dataCompra, int $diaVencimento, ?int $diaFechamento, int $mesesAFrente): array
    {
        $method = new ReflectionMethod(CartaoCreditoLancamentoService::class, 'calcularDataParcelaMes');
        $method->setAccessible(true);
        return $method->invoke($this->lancamentoService, $dataCompra, $diaVencimento, $diaFechamento, $mesesAFrente);
    }

    /**
     * Parcelamento via CartaoCreditoLancamentoService
     * fecha=2, vence=10, compra em 02/02/2025
     * Parcela 1 (mesesAFrente=0) → 10/03/2025
     * Parcela 2 (mesesAFrente=1) → 10/04/2025
     * Parcela 3 (mesesAFrente=2) → 10/05/2025
     */
    public function testParcelaMes_Fecha2Vence10_CompraNoFechamento(): void
    {
        $r1 = $this->calcularParcelaMes('2025-02-02', 10, 2, 0);
        $this->assertEquals('2025-03-10', $r1['data'], 'Parcela 1 deve vencer em 10/03');

        $r2 = $this->calcularParcelaMes('2025-02-02', 10, 2, 1);
        $this->assertEquals('2025-04-10', $r2['data'], 'Parcela 2 deve vencer em 10/04');

        $r3 = $this->calcularParcelaMes('2025-02-02', 10, 2, 2);
        $this->assertEquals('2025-05-10', $r3['data'], 'Parcela 3 deve vencer em 10/05');
    }

    /**
     * Parcelamento via CartaoCreditoLancamentoService
     * fecha=2, vence=10, compra em 01/02/2025 (antes do fechamento)
     * Parcela 1 (mesesAFrente=0) → 10/02/2025
     * Parcela 2 (mesesAFrente=1) → 10/03/2025
     */
    public function testParcelaMes_Fecha2Vence10_CompraAntesFechamento(): void
    {
        $r1 = $this->calcularParcelaMes('2025-02-01', 10, 2, 0);
        $this->assertEquals('2025-02-10', $r1['data'], 'Parcela 1 deve vencer em 10/02');

        $r2 = $this->calcularParcelaMes('2025-02-01', 10, 2, 1);
        $this->assertEquals('2025-03-10', $r2['data'], 'Parcela 2 deve vencer em 10/03');
    }

    // ========================================================================
    // CASO ESPECIAL: fecha=vence (mesmo dia)
    // ========================================================================

    /**
     * fecha=10, vence=10 (mesmo dia)
     * Compra dia 9 → fecha neste mês, vence no próximo (pois vence <= fecha)
     * Compra dia 10 → fecha no próximo mês, vence 2 meses à frente
     */
    public function testFechaIgualVencimento(): void
    {
        // Compra em 09/03 → fecha Mar, vence Abr (pois 10 <= 10, vence no mês seguinte)
        $r1 = $this->calcularVencimentoLancamento('2025-03-09', 10, 10);
        $this->assertEquals('2025-04-10', $r1['data'], 'fecha=vence=10, compra antes: vence no mês seguinte ao fechamento');

        // Compra em 10/03 → fecha Abr, vence Mai (pois 10 <= 10, vence no mês seguinte)
        $r2 = $this->calcularVencimentoLancamento('2025-03-10', 10, 10);
        $this->assertEquals('2025-05-10', $r2['data'], 'fecha=vence=10, compra no fechamento: vence 2 meses à frente');
    }

    // ========================================================================
    // TESTE COMPLETO: Ciclo mensal fecha=2, vence=10
    // Simula compras ao longo de Jan e Fev, verificando em qual fatura caem
    // ========================================================================

    /**
     * Ciclo completo fecha=2, vence=10
     * Jan 01 a Jan 31 → tudo após dia 2 vai para fatura Fev 10
     * Fev 01 → entra na fatura Fev 10
     * Fev 02+ → entra na fatura Mar 10
     */
    public function testCicloCompletoFecha2Vence10(): void
    {
        // Jan 1 (antes do fechamento de jan) → fecha Jan, vence Jan 10
        $this->assertEquals('2025-01-10', $this->calcularVencimentoLancamento('2025-01-01', 10, 2)['data']);

        // Jan 2 (no fechamento) → fecha Fev, vence Fev 10
        $this->assertEquals('2025-02-10', $this->calcularVencimentoLancamento('2025-01-02', 10, 2)['data']);

        // Jan 15 (após fechamento) → fecha Fev, vence Fev 10
        $this->assertEquals('2025-02-10', $this->calcularVencimentoLancamento('2025-01-15', 10, 2)['data']);

        // Jan 31 (após fechamento) → fecha Fev, vence Fev 10
        $this->assertEquals('2025-02-10', $this->calcularVencimentoLancamento('2025-01-31', 10, 2)['data']);

        // Fev 1 (antes do fechamento de fev) → fecha Fev, vence Fev 10
        $this->assertEquals('2025-02-10', $this->calcularVencimentoLancamento('2025-02-01', 10, 2)['data']);

        // Fev 2 (no fechamento) → fecha Mar, vence Mar 10
        $this->assertEquals('2025-03-10', $this->calcularVencimentoLancamento('2025-02-02', 10, 2)['data']);

        // Fev 15 (após fechamento) → fecha Mar, vence Mar 10
        $this->assertEquals('2025-03-10', $this->calcularVencimentoLancamento('2025-02-15', 10, 2)['data']);

        // Fev 28 (após fechamento) → fecha Mar, vence Mar 10
        $this->assertEquals('2025-03-10', $this->calcularVencimentoLancamento('2025-02-28', 10, 2)['data']);

        // Mar 1 (antes do fechamento de mar) → fecha Mar, vence Mar 10
        $this->assertEquals('2025-03-10', $this->calcularVencimentoLancamento('2025-03-01', 10, 2)['data']);

        // Mar 2 (no fechamento) → fecha Abr, vence Abr 10
        $this->assertEquals('2025-04-10', $this->calcularVencimentoLancamento('2025-03-02', 10, 2)['data']);
    }
}
