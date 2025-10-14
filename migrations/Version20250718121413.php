<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250718121413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter rocks_id column to NOT NULL with foreign key handling';
    }

    public function up(Schema $schema): void
    {
        // 1. Drop the foreign key constraint
        $this->addSql('ALTER TABLE topo DROP FOREIGN KEY FK_74A061F576A15D70');

        // 2. Alter the column
        $this->addSql('ALTER TABLE topo CHANGE rocks_id rocks_id INT NOT NULL');

        // 3. Re-add the foreign key constraint
        $this->addSql('ALTER TABLE topo ADD CONSTRAINT FK_74A061F576A15D70 FOREIGN KEY (rocks_id) REFERENCES rocks (id)');
    }

    public function down(Schema $schema): void
    {
        // Reverse the changes
        $this->addSql('ALTER TABLE topo DROP FOREIGN KEY FK_74A061F576A15D70');
        $this->addSql('ALTER TABLE topo CHANGE rocks_id rocks_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE topo ADD CONSTRAINT FK_74A061F576A15D70 FOREIGN KEY (rocks_id) REFERENCES rocks (id)');
    }
}
