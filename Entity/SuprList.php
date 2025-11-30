<?php

namespace MauticPlugin\MauticEmailSupressionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\UserBundle\Entity\User;

class SuprList extends FormEntity
{
    public const TABLE_NAME = 'supr_list';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable(self::TABLE_NAME);
        $builder->setCustomRepositoryClass(SuprListRepository::class);

        $builder->addId();

        $builder->createField('name', 'string')
            ->columnName('name')
            ->length(500)
            ->build();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
