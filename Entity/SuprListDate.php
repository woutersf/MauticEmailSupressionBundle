<?php

namespace MauticPlugin\MauticEmailSupressionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class SuprListDate
{
    public const TABLE_NAME = 'supr_list_date';

    /**
     * @var int
     */
    private $id;

    /**
     * @var SuprList
     */
    private $suprList;

    /**
     * @var \DateTime
     */
    private $date;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable(self::TABLE_NAME);

        $builder->addId();

        $builder->createManyToOne('suprList', SuprList::class)
            ->addJoinColumn('supr_list_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('date', 'date')
            ->columnName('date')
            ->build();

        $builder->addIndex(['supr_list_id'], 'idx_supr_list');
        $builder->addIndex(['date'], 'idx_date');
        $builder->addIndex(['supr_list_id', 'date'], 'idx_supr_list_date');
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

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }
}
