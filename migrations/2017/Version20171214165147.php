<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171214165147 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE tc_vehicle ADD metadata_author_name VARCHAR(255) NOT NULL, ADD metadata_created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("UPDATE tc_vehicle SET metadata_author_name = 'Hospodaření', metadata_created_at = '2018-01-01'");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_vehicle DROP metadata_author_name, DROP metadata_created_at');
    }
}
