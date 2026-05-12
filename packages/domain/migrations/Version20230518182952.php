<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230518182952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topo CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE with_sector with_sector TINYINT(1) DEFAULT NULL, CHANGE svg svg LONGTEXT DEFAULT NULL, CHANGE number number SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topo CHANGE image image VARCHAR(255) NOT NULL, CHANGE with_sector with_sector TINYINT(1) NOT NULL, CHANGE svg svg LONGTEXT NOT NULL, CHANGE number number SMALLINT NOT NULL');
    }
}
