<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128123427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photos ADD status VARCHAR(20) DEFAULT \'pending\' NOT NULL, ADD created_at DATETIME DEFAULT NULL, ADD uploader_name VARCHAR(255) DEFAULT NULL, ADD uploader_email VARCHAR(255) DEFAULT NULL');
        // Ensure existing photos remain visible by marking them as approved
        $this->addSql('UPDATE photos SET status = \'approved\'');
        $this->addSql('ALTER TABLE topo ADD CONSTRAINT FK_74A061F576A15D70 FOREIGN KEY (rocks_id) REFERENCES rock (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photos DROP status, DROP created_at, DROP uploader_name, DROP uploader_email');
        $this->addSql('ALTER TABLE topo DROP FOREIGN KEY FK_74A061F576A15D70');
    }
}
