<?php

namespace MauticPlugin\MauticEmailSupressionBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class SuprListRepository extends CommonRepository
{
    public function getEntities(array $args = [])
    {
        $q = $this->createQueryBuilder('s');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    protected function addCatchAllWhereClause($qb, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($qb, $filter, ['s.name']);
    }

    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    protected function getDefaultOrder(): array
    {
        return [
            ['s.name', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 's';
    }
}
