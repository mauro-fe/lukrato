<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Communication;

use Application\Models\MessageCampaign;
use Application\Services\Communication\CampaignApiWorkflowService;
use Application\Services\Communication\NotificationService;
use Carbon\Carbon;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CampaignApiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateCampaignRejectsInvalidLink(): void
    {
        $service = new CampaignApiWorkflowService(Mockery::mock(NotificationService::class));

        $result = $service->createCampaign(1, 'Admin', [
            'title' => 'Campanha',
            'message' => 'Mensagem valida',
            'link' => 'ftp://exemplo.com',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('O link deve usar protocolo http ou https', $result['message']);
    }

    public function testCreateCampaignReturnsScheduledPayload(): void
    {
        $campaign = new MessageCampaign();
        $campaign->id = 91;
        $campaign->title = 'Campanha PRO';
        $campaign->status = MessageCampaign::STATUS_SCHEDULED;
        $campaign->scheduled_at = Carbon::parse('2030-01-01 10:30:00');

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService
            ->shouldReceive('sendCampaign')
            ->once()
            ->with(
                7,
                'Campanha PRO',
                'Mensagem para base PRO',
                MessageCampaign::TYPE_INFO,
                [
                    'plan' => 'pro',
                    'status' => 'all',
                    'days_inactive' => null,
                    'email_verified' => null,
                ],
                true,
                false,
                null,
                null,
                Mockery::type('string'),
                null
            )
            ->andReturn($campaign);

        $service = new CampaignApiWorkflowService($notificationService);

        $result = $service->createCampaign(7, 'Admin Master', [
            'title' => 'Campanha PRO',
            'message' => 'Mensagem para base PRO',
            'filters' => [
                'plan' => 'pro',
            ],
            'scheduled_at' => '2030-01-01 10:30:00',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('Campanha agendada com sucesso', $result['message']);
        $this->assertSame([
            'campaign_id' => 91,
            'title' => 'Campanha PRO',
            'status' => MessageCampaign::STATUS_SCHEDULED,
            'scheduled_at' => '01/01/2030 10:30',
        ], $result['data']);
    }

    public function testCreateCampaignReturnsFailedMessageWhenAllChannelsFail(): void
    {
        $campaign = new MessageCampaign();
        $campaign->id = 12;
        $campaign->title = 'Falha total';
        $campaign->status = MessageCampaign::STATUS_FAILED;
        $campaign->total_recipients = 57;
        $campaign->emails_sent = 0;
        $campaign->emails_failed = 57;

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService
            ->shouldReceive('sendCampaign')
            ->once()
            ->andReturn($campaign);

        $service = new CampaignApiWorkflowService($notificationService);

        $result = $service->createCampaign(7, 'Admin Master', [
            'title' => 'Falha total',
            'message' => 'Mensagem para todos',
            'send_notification' => false,
            'send_email' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('Campanha processada, mas falhou em todos os canais selecionados.', $result['message']);
        $this->assertSame(MessageCampaign::STATUS_FAILED, $result['data']['status']);
    }

    public function testProcessDueCampaignsReturnsNotificationServiceStats(): void
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService
            ->shouldReceive('processScheduledCampaigns')
            ->once()
            ->andReturn([
                'processed' => 3,
                'sent' => 1,
                'partial' => 1,
                'failed' => 1,
                'stuck_recovered' => 2,
            ]);

        $service = new CampaignApiWorkflowService($notificationService);
        $result = $service->processDueCampaigns();

        $this->assertSame([
            'processed' => 3,
            'sent' => 1,
            'partial' => 1,
            'failed' => 1,
            'stuck_recovered' => 2,
        ], $result);
    }

    public function testCancelScheduledRejectsNonScheduledCampaign(): void
    {
        $campaign = new MessageCampaign();
        $campaign->status = MessageCampaign::STATUS_SENT;

        $service = new class (Mockery::mock(NotificationService::class), $campaign) extends CampaignApiWorkflowService {
            public function __construct(
                NotificationService $notificationService,
                private readonly MessageCampaign $campaign
            ) {
                parent::__construct($notificationService);
            }

            protected function findCampaign(int $id): ?MessageCampaign
            {
                return $id === 33 ? $this->campaign : null;
            }
        };

        $result = $service->cancelScheduled(33);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertStringContainsString('agendada', $result['message']);
    }

    public function testGetBirthdaysClampsDaysRangeToThirty(): void
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService
            ->shouldReceive('getBirthdayUsers')
            ->once()
            ->andReturn([
                ['id' => 1, 'nome' => 'Ana'],
            ]);
        $notificationService
            ->shouldReceive('getUpcomingBirthdays')
            ->once()
            ->with(30)
            ->andReturn([
                ['id' => 2, 'nome' => 'Beto'],
                ['id' => 3, 'nome' => 'Caio'],
            ]);

        $service = new CampaignApiWorkflowService($notificationService);
        $result = $service->getBirthdays(99);

        $this->assertSame(1, $result['today_count']);
        $this->assertSame(2, $result['upcoming_count']);
        $this->assertSame(30, $result['days_range']);
    }
}
