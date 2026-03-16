<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250313120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bike column to rock for bicycle-reachable filter.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock ADD bike TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock DROP bike');
    }
}
