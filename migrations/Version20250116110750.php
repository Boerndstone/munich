<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250116110750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE topo ADD path JSON DEFAULT NULL, CHANGE rocks_id rocks_id INT NOT NULL');
        $this->addSql('ALTER TABLE topo ADD CONSTRAINT FK_74A061F576A15D70 FOREIGN KEY (rocks_id) REFERENCES rock (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE topo DROP FOREIGN KEY FK_74A061F576A15D70');
        $this->addSql('ALTER TABLE topo DROP path, CHANGE rocks_id rocks_id INT DEFAULT NULL');
    }
}
