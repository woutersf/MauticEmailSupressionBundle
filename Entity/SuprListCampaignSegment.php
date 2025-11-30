<?php

namespace MauticPlugin\MauticEmailSupressionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class SuprListCampaignSegment
{
    public const TABLE_NAME = 'supr_list_campaign_segment';

    /**
     * @var int
     */
    private $id;

    /**
     * @var SuprList
     */
    private $suprList;

    /**
     * @var int
     */
    private $campaignId;

    /**
     * @var int
     */
    private $segmentId;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable(self::TABLE_NAME);

        $builder->addId();

        $builder->createManyToOne('suprList', SuprList::class)
            ->addJoinColumn('supr_list_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('campaignId', 'integer')
            ->columnName('campaign_id')
            ->nullable()
            ->build();

        $builder->createField('segmentId', 'integer')
            ->columnName('segment_id')
            ->nullable()
            ->build();

        $builder->addIndex(['supr_list_id'], 'idx_supr_list');
        $builder->addIndex(['campaign_id'], 'idx_campaign');
        $builder->addIndex(['segment_id'], 'idx_segment');
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSuprList()
    {
        return $this->suprList;
    }

    public function setSuprList(SuprList $suprList)
    {
        $this->suprList = $suprList;
        return $this;
    }

    public function getCampaignId()
    {
        return $this->campaignId;
    }

    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    public function getSegmentId()
    {
        return $this->segmentId;
    }

    public function setSegmentId($segmentId)
    {
        $this->segmentId = $segmentId;
        return $this;
    }
}
