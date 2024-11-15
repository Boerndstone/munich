<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241114114405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        ///$this->addSql('DROP TABLE commentBackup');
        //$this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        //$this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C34ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (id)');
        //$this->addSql('ALTER TABLE rock DROP topo');
        //$this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B37F7E8D71');
        //$this->addSql('DROP INDEX IDX_32D5C2B37F7E8D71 ON routes');
        $this->addSql('ALTER TABLE topo CHANGE rocks_id rocks_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        //$this->addSql('CREATE TABLE commentBackup (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, route_id INT DEFAULT NULL, comment LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_9474526CA76ED395 (user_id), INDEX IDX_9474526C34ECB4E6 (route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        //$this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        //$this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C34ECB4E6');
        //$this->addSql('ALTER TABLE rock ADD topo INT DEFAULT NULL');
        //$this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B37F7E8D71 FOREIGN KEY (topo_id) REFERENCES topo (id)');
        //$this->addSql('CREATE INDEX IDX_32D5C2B37F7E8D71 ON routes (topo_id)');
        $this->addSql('ALTER TABLE topo CHANGE rocks_id rocks_id INT DEFAULT NULL');
    }
}
