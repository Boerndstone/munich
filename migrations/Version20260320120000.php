<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove FirstAscencionist entity: drop routes.relates_to_route_id FK and first_ascencionist table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B3FB2AF44E');
        $this->addSql('ALTER TABLE routes DROP relates_to_route_id');
        $this->addSql('DROP TABLE first_ascencionist');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE first_ascencionist (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE routes ADD relates_to_route_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3FB2AF44E FOREIGN KEY (relates_to_route_id) REFERENCES first_ascencionist (id)');
        $this->addSql('CREATE INDEX IDX_32D5C2B3FB2AF44E ON routes (relates_to_route_id)');
    }
}
