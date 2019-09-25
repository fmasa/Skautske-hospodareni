<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170603124748 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE `pa_payment` SET email = NULL WHERE email = ''");
    }

    public function down(Schema $schema) : void
    {
    }
}
