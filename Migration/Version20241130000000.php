<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailSupressionBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Mautic\Migrations\AbstractMauticMigration;

final class Version20241130000000 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        // Create supr_list table
        $suprListTable = $schema->createTable('supr_list');

        $suprListTable->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
        ]);

        $suprListTable->addColumn('name', 'string', [
            'length' => 500,
            'notnull' => true,
        ]);

        $suprListTable->addColumn('date_added', 'datetime', [
            'notnull' => true,
        ]);

        $suprListTable->addColumn('created_by', 'integer', [
            'notnull' => false,
        ]);

        $suprListTable->setPrimaryKey(['id']);
        $suprListTable->addIndex(['created_by'], 'idx_created_by');

        // Create supr_list_date table
        $suprListDateTable = $schema->createTable('supr_list_date');

        $suprListDateTable->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
        ]);

        $suprListDateTable->addColumn('supr_list_id', 'integer', [
            'notnull' => true,
        ]);

        $suprListDateTable->addColumn('date', 'date', [
            'notnull' => true,
        ]);

        $suprListDateTable->setPrimaryKey(['id']);
        $suprListDateTable->addIndex(['supr_list_id'], 'idx_supr_list');
        $suprListDateTable->addIndex(['date'], 'idx_date');
        $suprListDateTable->addIndex(['supr_list_id', 'date'], 'idx_supr_list_date');
        $suprListDateTable->addForeignKeyConstraint(
            'supr_list',
            ['supr_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );

        // Create supr_list_campaign_segment table
        $suprListCampaignSegmentTable = $schema->createTable('supr_list_campaign_segment');

        $suprListCampaignSegmentTable->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
        ]);

        $suprListCampaignSegmentTable->addColumn('supr_list_id', 'integer', [
            'notnull' => true,
        ]);

        $suprListCampaignSegmentTable->addColumn('campaign_id', 'integer', [
            'notnull' => false,
        ]);

        $suprListCampaignSegmentTable->addColumn('segment_id', 'integer', [
            'notnull' => false,
        ]);

        $suprListCampaignSegmentTable->setPrimaryKey(['id']);
        $suprListCampaignSegmentTable->addIndex(['supr_list_id'], 'idx_supr_list');
        $suprListCampaignSegmentTable->addIndex(['campaign_id'], 'idx_campaign');
        $suprListCampaignSegmentTable->addIndex(['segment_id'], 'idx_segment');
        $suprListCampaignSegmentTable->addForeignKeyConstraint(
            'supr_list',
            ['supr_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('supr_list_campaign_segment');
        $schema->dropTable('supr_list_date');
        $schema->dropTable('supr_list');
    }
}
