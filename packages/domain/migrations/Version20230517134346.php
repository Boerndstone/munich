<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517134346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rock CHANGE description description LONGTEXT DEFAULT NULL, CHANGE access access LONGTEXT DEFAULT NULL, CHANGE nature nature LONGTEXT DEFAULT NULL, CHANGE zone zone SMALLINT DEFAULT NULL, CHANGE banned banned SMALLINT DEFAULT NULL, CHANGE height height INT DEFAULT NULL, CHANGE orientation orientation VARCHAR(50) DEFAULT NULL, CHANGE season season VARCHAR(50) DEFAULT NULL, CHANGE child_friendly child_friendly SMALLINT DEFAULT NULL, CHANGE sunny sunny SMALLINT DEFAULT NULL, CHANGE rain rain SMALLINT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE header_image header_image VARCHAR(255) DEFAULT NULL, CHANGE topo topo INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rock CHANGE description description LONGTEXT NOT NULL, CHANGE access access LONGTEXT NOT NULL, CHANGE nature nature LONGTEXT NOT NULL, CHANGE zone zone SMALLINT NOT NULL, CHANGE banned banned SMALLINT NOT NULL, CHANGE height height INT NOT NULL, CHANGE orientation orientation VARCHAR(50) NOT NULL, CHANGE season season VARCHAR(50) NOT NULL, CHANGE child_friendly child_friendly SMALLINT NOT NULL, CHANGE sunny sunny SMALLINT NOT NULL, CHANGE rain rain SMALLINT NOT NULL, CHANGE image image VARCHAR(255) NOT NULL, CHANGE header_image header_image VARCHAR(255) NOT NULL, CHANGE topo topo INT NOT NULL');
    }
}
