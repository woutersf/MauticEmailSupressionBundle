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

        $prefix = defined('MAUTIC_TABLE_PREFIX') ? MAUTIC_TABLE_PREFIX : '';

        $sql = "SELECT EXISTS(
                    SELECT 1
                    FROM {$prefix}supr_list_campaign_email AS sce
                    INNER JOIN {$prefix}supr_list_date AS dt ON sce.supr_list_id = dt.supr_list_id
                    WHERE sce.campaign_id = :campaign_id
                    AND dt.date = :today
                    LIMIT 1
                ) AS is_suppressed";

        $result = $this->connection->executeQuery($sql, [
            'campaign_id' => $campaignId,
            'today'       => $today,
        ])->fetchOne();

        if ((bool) $result) {
            $event->doNotTrigger();
        }
    }
}
