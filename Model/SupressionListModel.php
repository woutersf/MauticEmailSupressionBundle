<?php

namespace MauticPlugin\MauticEmailSupressionBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\MauticEmailSupressionBundle\Entity\SuprList;
use MauticPlugin\MauticEmailSupressionBundle\Entity\SuprListDate;
use MauticPlugin\MauticEmailSupressionBundle\Form\Type\SupressionListType;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SupressionListModel extends FormModel
{
    public function __construct(
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository()
    {
        return $this->em->getRepository(SuprList::class);
    }

    public function getDateRepository()
    {
        return $this->em->getRepository(SuprListDate::class);
    }

    public function getDatesBySuprListId($suprListId)
    {
        return $this->em->getRepository(SuprListDate::class)
            ->createQueryBuilder('d')
            ->where('d.suprList = :suprListId')
            ->setParameter('suprListId', $suprListId)
            ->orderBy('d.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDateBySuprListAndDate($suprListId, \DateTime $date)
    {
        return $this->em->getRepository(SuprListDate::class)
            ->createQueryBuilder('d')
            ->where('d.suprList = :suprListId')
            ->andWhere('d.date = :date')
            ->setParameter('suprListId', $suprListId)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteDateBySuprListAndDate($suprListId, \DateTime $date)
    {
        return $this->em->createQueryBuilder()
                    ->delete(SuprListDate::class, 'd')
                     ->where('d.suprList = :suprList')
                     ->andWhere('d.date = :date')
                     ->setParameter('suprList', $suprListId)
                     ->setParameter('date', $date->format('Y-m-d'))
                     ->getQuery()
                     ->execute();
    }

    public function getPermissionBase(): string
    {
        return 'supressionlist:supressionlists';
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?\Symfony\Contracts\EventDispatcher\Event $event = null): ?\Symfony\Contracts\EventDispatcher\Event
    {
        // No events dispatched for now
        return null;
    }

    public function saveEntity($entity, $unlock = true): void
    {
        if (!$entity instanceof SuprList) {
            throw new MethodNotAllowedHttpException(['SuprList']);
        }

        parent::saveEntity($entity, $unlock);
    }

    public function deleteEntity($entity): void
    {
        if (!$entity instanceof SuprList) {
            throw new MethodNotAllowedHttpException(['SuprList']);
        }

        parent::deleteEntity($entity);
    }

    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof SuprList) {
            throw new MethodNotAllowedHttpException(['SuprList']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        // Get all segments ordered by name
        if (!isset($options['segment_choices'])) {
            $segments = $this->em->getRepository(\Mautic\LeadBundle\Entity\LeadList::class)
                ->createQueryBuilder('s')
                ->orderBy('s.name', 'ASC')
                ->getQuery()
                ->getResult();

            $segmentChoices = [];
            foreach ($segments as $segment) {
                $segmentChoices[$segment->getName()] = $segment->getId();
            }
            $options['segment_choices'] = $segmentChoices;
        }

        // Get all campaigns ordered by name
        if (!isset($options['campaign_choices'])) {
            $campaigns = $this->em->getRepository(\Mautic\CampaignBundle\Entity\Campaign::class)
                ->createQueryBuilder('c')
                ->orderBy('c.name', 'ASC')
                ->getQuery()
                ->getResult();

            $campaignChoices = [];
            foreach ($campaigns as $campaign) {
                $campaignChoices[$campaign->getName()] = $campaign->getId();
            }
            $options['campaign_choices'] = $campaignChoices;
        }

        return $formFactory->create(SupressionListType::class, $entity, $options);
    }

    public function getEntity($id = null): ?object
    {
        if (null === $id) {
            return new SuprList();
        }

        return parent::getEntity($id);
    }

    /**
     * Get linked segments and campaigns for a suppression list
     */
    public function getLinkedSegmentsAndCampaigns($suprListId): array
    {
        $links = $this->em->getRepository(\MauticPlugin\MauticEmailSupressionBundle\Entity\SuprListCampaignSegment::class)
            ->createQueryBuilder('l')
            ->where('l.suprList = :suprListId')
            ->setParameter('suprListId', $suprListId)
            ->getQuery()
            ->getResult();

        $segments = [];
        $campaigns = [];

        foreach ($links as $link) {
            $segmentId = $link->getSegmentId();
            $campaignId = $link->getCampaignId();

            // Fetch segment details if segment ID exists
            if ($segmentId) {
                $segment = $this->em->getRepository(\Mautic\LeadBundle\Entity\LeadList::class)->find($segmentId);
                if ($segment) {
                    $segments[$segmentId] = $segment;
                }
            }

            // Fetch campaign details if campaign ID exists
            if ($campaignId) {
                $campaign = $this->em->getRepository(\Mautic\CampaignBundle\Entity\Campaign::class)->find($campaignId);
                if ($campaign) {
                    $campaigns[$campaignId] = $campaign;
                }
            }
        }

        return [
            'segments'  => array_values($segments),
            'campaigns' => array_values($campaigns),
        ];
    }

    /**
     * Save linked segments and campaigns for a suppression list
     */
    public function saveLinkedSegmentsAndCampaigns($suprListId, array $segmentIds, array $campaignIds): void
    {
        $suprList = $this->getEntity($suprListId);
        if (!$suprList) {
            return;
        }

        // Delete all existing links for this suppression list
        $this->em->createQueryBuilder()
            ->delete(\MauticPlugin\MauticEmailSupressionBundle\Entity\SuprListCampaignSegment::class, 'l')
            ->where('l.suprList = :suprListId')
            ->setParameter('suprListId', $suprListId)
            ->getQuery()
            ->execute();

        // Add new segment links
        foreach ($segmentIds as $segmentId) {
            $link = new \MauticPlugin\MauticEmailSupressionBundle\Entity\SuprListCampaignSegment();
            $link->setSuprList($suprList);
            $link->setSegmentId($segmentId);
            $link->setCampaignId(null);
            $this->em->persist($link);
        }

        // Add new campaign links
        foreach ($campaignIds as $campaignId) {
            $link = new \MauticPlugin\MauticEmailSupressionBundle\Entity\SuprListCampaignSegment();
            $link->setSuprList($suprList);
            $link->setSegmentId(null);
            $link->setCampaignId($campaignId);
            $this->em->persist($link);
        }

        $this->em->flush();
    }
}
