<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add climbing_style JSON column to routes table.
 * Stores an array of climbing styles, e.g. ["sport", "slab", "crack"]
 */
final class Version20260223110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add climbing_style JSON column to routes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE routes ADD climbing_style JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE routes DROP climbing_style');
    }
}
