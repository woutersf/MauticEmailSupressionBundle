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

        // Create supr_list_campaign_email table
        $suprListCampaignEmailTable = $schema->createTable('supr_list_campaign_email');

        $suprListCampaignEmailTable->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
        ]);

        $suprListCampaignEmailTable->addColumn('supr_list_id', 'integer', [
            'notnull' => true,
        ]);

        $suprListCampaignEmailTable->addColumn('campaign_id', 'integer', [
            'notnull' => false,
        ]);

        $suprListCampaignEmailTable->addColumn('email_id', 'integer', [
            'notnull' => false,
        ]);

        $suprListCampaignEmailTable->setPrimaryKey(['id']);
        $suprListCampaignEmailTable->addIndex(['supr_list_id'], 'idx_supr_list');
        $suprListCampaignEmailTable->addIndex(['campaign_id'], 'idx_campaign');
        $suprListCampaignEmailTable->addIndex(['email_id'], 'idx_email');
        $suprListCampaignEmailTable->addForeignKeyConstraint(
            'supr_list',
            ['supr_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('supr_list_campaign_email');
        $schema->dropTable('supr_list_date');
        $schema->dropTable('supr_list');
    }
}
