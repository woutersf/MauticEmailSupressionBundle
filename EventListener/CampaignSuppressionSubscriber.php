<?php

namespace MauticPlugin\MauticEmailSupressionBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignTriggerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSuppressionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_TRIGGER => ['onCampaignTrigger', 0],
        ];
    }

    /**
     * Check if campaign should be suppressed based on suppression list dates
     */
    public function onCampaignTrigger(CampaignTriggerEvent $event): void
    {
        $campaign = $event->getCampaign();
        $campaignId = $campaign->getId();
        $today = (new \DateTime())->format('Y-m-d');

        // Query to check if today's date is suppressed for this campaign
        // Using EXISTS for optimal performance - stops as soon as first match is found
        $sql = "SELECT EXISTS(
                    SELECT 1
                    FROM supr_list_campaign_segment AS scs
                    INNER JOIN supr_list_date AS dt ON scs.supr_list_id = dt.supr_list_id
                    WHERE scs.campaign_id = :campaign_id
                    AND dt.date = :today
                    LIMIT 1
                ) AS is_suppressed";

        $result = $this->connection->executeQuery($sql, [
            'campaign_id' => $campaignId,
            'today' => $today,
        ])->fetchOne();

        $isSuppressed = (bool) $result;

        if ($isSuppressed) {
            $event->stopTrigger();
        }
    }
}
