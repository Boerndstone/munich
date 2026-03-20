<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove description, access and nature columns from rock table.
 * Content is available via RockTranslation for localized content.
 */
final class Version20250302120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove description, access and nature columns from rock table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock DROP description');
        $this->addSql('ALTER TABLE rock DROP access');
        $this->addSql('ALTER TABLE rock DROP nature');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE rock ADD access LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE rock ADD nature LONGTEXT DEFAULT NULL');
    }
}
