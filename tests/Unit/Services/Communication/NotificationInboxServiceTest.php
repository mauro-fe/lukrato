<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Communication;

use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Communication\NotificationInboxService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class NotificationInboxServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetUnreadCountCombinesLegacyCampaignAndVisibleDynamicAlerts(): void
    {
        $legacyQuery = Mockery::mock();
        $legacyQuery->shouldReceive('where')->once()->with('lida', false)->andReturnSelf();
        $legacyQuery->shouldReceive('count')->once()->andReturn(2);

        $legacyModel = Mockery::mock('alias:Application\Models\Notificacao');
        $legacyModel->shouldReceive('where')->once()->with('user_id', 501)->andReturn($legacyQuery);

        $campaignQuery = Mockery::mock();
        $campaignQuery->shouldReceive('where')->once()->with('is_read', false)->andReturnSelf();
        $campaignQuery->shouldReceive('count')->once()->andReturn(1);

        $campaignModel = Mockery::mock('alias:Application\Models\Notification');
        $campaignModel->shouldReceive('where')->once()->with('user_id', 501)->andReturn($campaignQuery);

        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $cartaoService->shouldReceive('verificarLimitesBaixos')->once()->with(501)->andReturn([
            [
                'cartao_id' => 22,
                'nome_cartao' => 'Visa',
                'percentual_disponivel' => 8,
                'limite_disponivel' => 100.00,
            ],
        ]);

        $faturaService = Mockery::mock(CartaoFaturaService::class);
        $faturaService->shouldReceive('verificarVencimentosProximos')->once()->with(501)->andReturn([
            [
                'cartao_id' => 11,
                'nome_cartao' => 'Master',
                'dias_faltando' => 2,
                'valor_fatura' => 350.40,
            ],
        ]);

        $service = new NotificationInboxService($cartaoService, $faturaService);

        $result = $service->getUnreadCount(501, [
            'cartao_lim_22' => time(),
        ]);

        $this->assertSame(4, $result['unread']);
        $this->assertArrayHasKey('cartao_lim_22', $result['ignored_alerts']);
    }

    public function testGetInboxRemovesIgnoredAlertsThatAreNoLongerCurrent(): void
    {
        $legacyGetQuery = Mockery::mock();
        $legacyGetQuery->shouldReceive('get')->once()->andReturn(new EloquentCollection());

        $legacyOrderQuery = Mockery::mock();
        $legacyOrderQuery->shouldReceive('orderBy')->once()->with('created_at', 'desc')->andReturn($legacyGetQuery);

        $legacyModel = Mockery::mock('alias:Application\Models\Notificacao');
        $legacyModel->shouldReceive('where')->once()->with('user_id', 777)->andReturn($legacyOrderQuery);

        $campaignGetQuery = Mockery::mock();
        $campaignGetQuery->shouldReceive('get')->once()->andReturn(new EloquentCollection());

        $campaignLimitQuery = Mockery::mock();
        $campaignLimitQuery->shouldReceive('limit')->once()->with(50)->andReturn($campaignGetQuery);

        $campaignOrderQuery = Mockery::mock();
        $campaignOrderQuery->shouldReceive('orderBy')->once()->with('created_at', 'desc')->andReturn($campaignLimitQuery);

        $campaignModel = Mockery::mock('alias:Application\Models\Notification');
        $campaignModel->shouldReceive('where')->once()->with('user_id', 777)->andReturn($campaignOrderQuery);

        $userModel = Mockery::mock('alias:Application\Models\Usuario');
        $userModel->shouldReceive('find')->once()->with(777)->andReturn(null);

        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $cartaoService->shouldReceive('verificarLimitesBaixos')->once()->with(777)->andReturn([]);

        $faturaService = Mockery::mock(CartaoFaturaService::class);
        $faturaService->shouldReceive('verificarVencimentosProximos')->once()->with(777)->andReturn([]);

        $service = new NotificationInboxService($cartaoService, $faturaService);

        $result = $service->getInbox(777, [
            'cartao_venc_99' => time(),
        ]);

        $this->assertSame([], $result['itens']);
        $this->assertSame(0, $result['unread']);
        $this->assertSame([], $result['ignored_alerts']);
    }

    public function testMarkAsReadUpdatesAllNotificationTargetsAndTracksDynamicIds(): void
    {
        $legacyWhereInQuery = Mockery::mock();
        $legacyWhereInQuery->shouldReceive('update')->once()->with(['lida' => true])->andReturn(1);

        $legacyQuery = Mockery::mock();
        $legacyQuery->shouldReceive('whereIn')->once()->with('id', [5])->andReturn($legacyWhereInQuery);

        $legacyModel = Mockery::mock('alias:Application\Models\Notificacao');
        $legacyModel->shouldReceive('where')->once()->with('user_id', 900)->andReturn($legacyQuery);

        $campaignWhereInQuery = Mockery::mock();
        $campaignWhereInQuery->shouldReceive('update')->once()->with(Mockery::on(
            static fn(array $payload): bool => $payload['is_read'] === true && isset($payload['read_at'])
        ))->andReturn(1);

        $campaignQuery = Mockery::mock();
        $campaignQuery->shouldReceive('whereIn')->once()->with('id', [12])->andReturn($campaignWhereInQuery);

        $campaignModel = Mockery::mock('alias:Application\Models\Notification');
        $campaignModel->shouldReceive('where')->once()->with('user_id', 900)->andReturn($campaignQuery);

        $service = new NotificationInboxService(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class)
        );

        $result = $service->markAsRead(900, [5, 'campaign_12', 'subscription_grace_9'], [
            'cartao_venc_1' => 123,
        ]);

        $this->assertSame(123, $result['ignored_alerts']['cartao_venc_1']);
        $this->assertArrayHasKey('subscription_grace_9', $result['ignored_alerts']);
        $this->assertIsInt($result['ignored_alerts']['subscription_grace_9']);
    }

    public function testMarkAsReadRejectsRequestsWithoutValidIds(): void
    {
        $service = new NotificationInboxService(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nenhum ID de notificação válido fornecido.');

        $service->markAsRead(1, ['', 0, null], []);
    }
}
