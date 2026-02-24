<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove header_image column from rock table.
 * Rock header display now uses the main rock image only.
 */
final class Version20260223100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove header_image column from rock table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock DROP COLUMN header_image');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock ADD header_image VARCHAR(255) DEFAULT NULL');
    }
}
