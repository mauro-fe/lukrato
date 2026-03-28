<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Models\MessageCampaign;

class CampaignDeliveryStatusResolver
{
    public function resolve(
        bool $sendNotification,
        int $notificationsDelivered,
        bool $sendEmail,
        int $emailEligible,
        int $emailsSent,
        int $emailsFailed
    ): string {
        $deliveredViaAnyChannel = false;
        $hasChannelLevelFailure = false;

        if ($sendNotification) {
            if ($notificationsDelivered > 0) {
                $deliveredViaAnyChannel = true;
            } else {
                $hasChannelLevelFailure = true;
            }
        }

        if ($sendEmail) {
            if ($emailEligible === 0) {
                $hasChannelLevelFailure = true;
            } elseif ($emailsSent > 0) {
                $deliveredViaAnyChannel = true;

                if ($emailsFailed > 0) {
                    $hasChannelLevelFailure = true;
                }
            } else {
                $hasChannelLevelFailure = true;
            }
        }

        if (!$deliveredViaAnyChannel) {
            return MessageCampaign::STATUS_FAILED;
        }

        if ($hasChannelLevelFailure) {
            return MessageCampaign::STATUS_PARTIAL;
        }

        return MessageCampaign::STATUS_SENT;
    }
}
