<?php

namespace MauticPlugin\MauticEmailSupressionBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailSuppressionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailSend', 0],
        ];
    }

    /**
     * Check if email should be suppressed based on suppression list dates
     */
    public function onEmailSend(EmailSendEvent $event): void
    {
        $email = $event->getEmail();

        // Only process if we have an email entity
        if (!$email) {
            return;
        }

        $emailId = $email->getId();
        $today = (new \DateTime())->format('Y-m-d');

        // Query to check if today's date is suppressed for this email
        // Using EXISTS for optimal performance - stops as soon as first match is found
        $sql = "SELECT EXISTS(
                    SELECT 1
                    FROM supr_list_campaign_email AS sce
                    INNER JOIN supr_list_date AS dt ON sce.supr_list_id = dt.supr_list_id
                    WHERE sce.email_id = :email_id
                    AND dt.date = :today
                    LIMIT 1
                ) AS is_suppressed";

        $result = $this->connection->executeQuery($sql, [
            'email_id' => $emailId,
            'today' => $today,
        ])->fetchOne();

        $isSuppressed = (bool) $result;

        if ($isSuppressed) {
            // Mark the email as failed to prevent sending
            $event->stopPropagation();
        }
    }
}
