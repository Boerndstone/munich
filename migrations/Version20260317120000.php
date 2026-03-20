<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove svg, path and viewBox columns from topo (topo uses image + pathCollection only).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE topo DROP COLUMN svg, DROP COLUMN path, DROP COLUMN view_box');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE topo ADD svg LONGTEXT DEFAULT NULL, ADD path JSON DEFAULT NULL, ADD view_box VARCHAR(64) DEFAULT NULL');
    }
}