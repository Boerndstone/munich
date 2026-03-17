<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add travel_time_minutes to area (driving time from Munich, filled via app:travel-time:import).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE area ADD travel_time_minutes SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE area DROP travel_time_minutes');
    }
}
