<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517141926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes CHANGE climbed climbed TINYINT(1) DEFAULT NULL, CHANGE first_ascent first_ascent VARCHAR(100) DEFAULT NULL, CHANGE year_first_ascent year_first_ascent INT DEFAULT NULL, CHANGE scale scale VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes CHANGE climbed climbed TINYINT(1) NOT NULL, CHANGE first_ascent first_ascent VARCHAR(100) NOT NULL, CHANGE year_first_ascent year_first_ascent INT NOT NULL, CHANGE scale scale VARCHAR(100) NOT NULL');
    }
}
