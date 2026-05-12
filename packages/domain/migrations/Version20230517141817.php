<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517141817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes CHANGE grade grade VARCHAR(20) DEFAULT NULL, CHANGE protection protection SMALLINT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE grade_no grade_no INT DEFAULT NULL, CHANGE rating rating SMALLINT DEFAULT NULL, CHANGE topo_id topo_id INT DEFAULT NULL, CHANGE nr nr INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes CHANGE grade grade VARCHAR(20) NOT NULL, CHANGE protection protection SMALLINT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE grade_no grade_no INT NOT NULL, CHANGE rating rating SMALLINT NOT NULL, CHANGE topo_id topo_id INT NOT NULL, CHANGE nr nr INT NOT NULL');
    }
}
