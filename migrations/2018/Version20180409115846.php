<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180409115846 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_cashbook ADD chit_number_prefix VARCHAR(255) DEFAULT NULL, CHANGE id id INT NOT NULL COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('UPDATE ac_cashbook c JOIN ac_object o ON o.id = c.id SET c.chit_number_prefix = o.prefix');
        $this->addSql('ALTER TABLE ac_object DROP prefix');
        $this->addSql('DELETE FROM ac_chits WHERE deleted = 1');
        $this->addSql('ALTER TABLE ac_chits DROP deleted, DROP budgetCategoryIn,  DROP budgetCategoryOut');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_cashbook DROP chit_number_prefix, CHANGE id id INT NOT NULL');
        $this->addSql('UPDATE ac_cashbook SET chit_number_prefix = NULL WHERE chit_number_prefix = \'\'');
    }
}
