<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241113135712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE commentBackup DROP FOREIGN KEY FK_9474526C34ECB4E6');
        //$this->addSql('ALTER TABLE commentBackup DROP FOREIGN KEY FK_9474526CA76ED395');
        //$this->addSql('ALTER TABLE rock_translation DROP FOREIGN KEY FK_2A4CA5A0B48CC24E');
        $this->addSql('DROP TABLE commentBackup');
        //$this->addSql('DROP TABLE rock_translation');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C34ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (id)');
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B37F7E8D71');
        $this->addSql('DROP INDEX IDX_32D5C2B37F7E8D71 ON routes');
        $this->addSql('ALTER TABLE routes ADD topo INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentBackup (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, route_id INT DEFAULT NULL, comment LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_9474526CA76ED395 (user_id), INDEX IDX_9474526C34ECB4E6 (route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        //$this->addSql('CREATE TABLE rock_translation (id INT AUTO_INCREMENT NOT NULL, rock_id INT DEFAULT NULL, locale VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_2A4CA5A0B48CC24E (rock_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        //$this->addSql('ALTER TABLE commentBackup ADD CONSTRAINT FK_9474526C34ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (id)');
        //$this->addSql('ALTER TABLE commentBackup ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        //$this->addSql('ALTER TABLE rock_translation ADD CONSTRAINT FK_2A4CA5A0B48CC24E FOREIGN KEY (rock_id) REFERENCES rock (id)');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C34ECB4E6');
        $this->addSql('ALTER TABLE routes DROP topo');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B37F7E8D71 FOREIGN KEY (topo_id) REFERENCES topo (id)');
        $this->addSql('CREATE INDEX IDX_32D5C2B37F7E8D71 ON routes (topo_id)');
    }
}
