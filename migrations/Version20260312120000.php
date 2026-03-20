<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add view_box column to topo table for overlay SVG scaling (e.g. "0 0 1024 820").
 */
final class Version20260312120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add view_box column to topo table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE topo ADD view_box VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE topo DROP view_box');
    }
}
