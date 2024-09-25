<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240924135334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rock_translation (id INT AUTO_INCREMENT NOT NULL, rock_id INT DEFAULT NULL, locale VARCHAR(5) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_2A4CA5A0B48CC24E (rock_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rock_translation ADD CONSTRAINT FK_2A4CA5A0B48CC24E FOREIGN KEY (rock_id) REFERENCES rock (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rock_translation DROP FOREIGN KEY FK_2A4CA5A0B48CC24E');
        $this->addSql('DROP TABLE rock_translation');
    }
}
