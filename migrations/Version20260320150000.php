<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove routes.description (use Comment entity for route text instead).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE routes DROP description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE routes ADD description LONGTEXT DEFAULT NULL');
    }
}
