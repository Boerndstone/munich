<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rock: change sunny and rain from SMALLINT to BOOLEAN (true/false).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock CHANGE sunny sunny TINYINT(1) DEFAULT NULL, CHANGE rain rain TINYINT(1) DEFAULT NULL');
        // Migrate data: preserve NULL; sunny 3 -> 1 (true), other non-NULL -> 0 (false); rain 1 -> 1 (true), other non-NULL -> 0 (false)
        $this->addSql("UPDATE rock SET sunny = CASE WHEN sunny IS NULL THEN NULL WHEN sunny = 3 THEN 1 ELSE 0 END, rain = CASE WHEN rain IS NULL THEN NULL WHEN rain = 1 THEN 1 ELSE 0 END");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rock CHANGE sunny sunny SMALLINT DEFAULT NULL, CHANGE rain rain SMALLINT DEFAULT NULL');
        // Restore: preserve NULL; 1 -> 3 for sunny (was "sonnig"), 0 -> 1 for sunny (was "keine Sonne"); rain 1 -> 1, 0 -> 3
        $this->addSql('UPDATE rock SET sunny = CASE WHEN sunny IS NULL THEN NULL WHEN sunny = 1 THEN 3 ELSE 1 END, rain = CASE WHEN rain IS NULL THEN NULL WHEN rain = 1 THEN 1 ELSE 3 END');
    }
}
