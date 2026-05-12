<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231224134310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes ADD relates_to_route_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3FB2AF44E FOREIGN KEY (relates_to_route_id) REFERENCES first_ascencionist (id)');
        $this->addSql('CREATE INDEX IDX_32D5C2B3FB2AF44E ON routes (relates_to_route_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B3FB2AF44E');
        $this->addSql('DROP INDEX IDX_32D5C2B3FB2AF44E ON routes');
        $this->addSql('ALTER TABLE routes DROP relates_to_route_id');
    }
}
