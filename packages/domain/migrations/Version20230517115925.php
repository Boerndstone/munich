<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517115925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE area (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, orientation VARCHAR(25) NOT NULL, sequence SMALLINT NOT NULL, online SMALLINT NOT NULL, image VARCHAR(25) DEFAULT NULL, header_image VARCHAR(25) DEFAULT NULL, lat NUMERIC(6, 2) DEFAULT NULL, lng NUMERIC(6, 2) DEFAULT NULL, zoom SMALLINT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photos (id INT AUTO_INCREMENT NOT NULL, belongs_to_area_id INT DEFAULT NULL, belongs_to_rock_id INT DEFAULT NULL, belongs_to_route_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, photgrapher VARCHAR(255) DEFAULT NULL, INDEX IDX_876E0D9D6D3667B (belongs_to_area_id), INDEX IDX_876E0D9DF50E4A9 (belongs_to_rock_id), INDEX IDX_876E0D9AE91F1B (belongs_to_route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rock (id INT AUTO_INCREMENT NOT NULL, area_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, nr INT NOT NULL, description LONGTEXT NOT NULL, access LONGTEXT NOT NULL, nature LONGTEXT NOT NULL, zone SMALLINT NOT NULL, banned SMALLINT NOT NULL, height INT NOT NULL, orientation VARCHAR(50) NOT NULL, season VARCHAR(50) NOT NULL, child_friendly SMALLINT NOT NULL, sunny SMALLINT NOT NULL, rain SMALLINT NOT NULL, image VARCHAR(255) NOT NULL, header_image VARCHAR(255) NOT NULL, topo INT NOT NULL, lat NUMERIC(10, 6) DEFAULT NULL, lng NUMERIC(10, 6) DEFAULT NULL, online TINYINT(1) NOT NULL, INDEX IDX_3749BBA2BD0F409C (area_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE routes (id INT AUTO_INCREMENT NOT NULL, area_id INT DEFAULT NULL, rock_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, grade VARCHAR(20) NOT NULL, climbed TINYINT(1) NOT NULL, first_ascent VARCHAR(100) NOT NULL, year_first_ascent INT NOT NULL, protection SMALLINT NOT NULL, description LONGTEXT NOT NULL, scale VARCHAR(100) NOT NULL, grade_no INT NOT NULL, rating SMALLINT NOT NULL, topo_id INT NOT NULL, nr INT NOT NULL, INDEX IDX_32D5C2B3BD0F409C (area_id), INDEX IDX_32D5C2B3B48CC24E (rock_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE topo (id INT AUTO_INCREMENT NOT NULL, rocks_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, with_sector TINYINT(1) NOT NULL, svg LONGTEXT NOT NULL, number SMALLINT NOT NULL, INDEX IDX_74A061F576A15D70 (rocks_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(64) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, avatar VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE videos (id INT AUTO_INCREMENT NOT NULL, video_area_id INT DEFAULT NULL, video_rocks_id INT DEFAULT NULL, video_routes_id INT DEFAULT NULL, video_link VARCHAR(255) DEFAULT NULL, INDEX IDX_29AA643258E2A931 (video_area_id), INDEX IDX_29AA6432DE236FCC (video_rocks_id), INDEX IDX_29AA64326C536B49 (video_routes_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT FK_876E0D9D6D3667B FOREIGN KEY (belongs_to_area_id) REFERENCES area (id)');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT FK_876E0D9DF50E4A9 FOREIGN KEY (belongs_to_rock_id) REFERENCES rock (id)');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT FK_876E0D9AE91F1B FOREIGN KEY (belongs_to_route_id) REFERENCES routes (id)');
        $this->addSql('ALTER TABLE rock ADD CONSTRAINT FK_3749BBA2BD0F409C FOREIGN KEY (area_id) REFERENCES area (id)');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3BD0F409C FOREIGN KEY (area_id) REFERENCES area (id)');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3B48CC24E FOREIGN KEY (rock_id) REFERENCES rock (id)');
        $this->addSql('ALTER TABLE topo ADD CONSTRAINT FK_74A061F576A15D70 FOREIGN KEY (rocks_id) REFERENCES rock (id)');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643258E2A931 FOREIGN KEY (video_area_id) REFERENCES area (id)');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA6432DE236FCC FOREIGN KEY (video_rocks_id) REFERENCES rock (id)');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA64326C536B49 FOREIGN KEY (video_routes_id) REFERENCES routes (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photos DROP FOREIGN KEY FK_876E0D9D6D3667B');
        $this->addSql('ALTER TABLE photos DROP FOREIGN KEY FK_876E0D9DF50E4A9');
        $this->addSql('ALTER TABLE photos DROP FOREIGN KEY FK_876E0D9AE91F1B');
        $this->addSql('ALTER TABLE rock DROP FOREIGN KEY FK_3749BBA2BD0F409C');
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B3BD0F409C');
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B3B48CC24E');
        $this->addSql('ALTER TABLE topo DROP FOREIGN KEY FK_74A061F576A15D70');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643258E2A931');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA6432DE236FCC');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA64326C536B49');
        $this->addSql('DROP TABLE area');
        $this->addSql('DROP TABLE photos');
        $this->addSql('DROP TABLE rock');
        $this->addSql('DROP TABLE routes');
        $this->addSql('DROP TABLE topo');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE videos');
    }
}
