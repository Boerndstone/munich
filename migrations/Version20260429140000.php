<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add topo_path_suggestion for public Mithelfen topo path submissions (pending review).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE topo_path_suggestion (id INT AUTO_INCREMENT NOT NULL, rock_id INT NOT NULL, topo_number SMALLINT DEFAULT NULL, path_collection LONGTEXT NOT NULL, reference_image_basename VARCHAR(255) DEFAULT NULL, uploader_name VARCHAR(255) NOT NULL, uploader_email VARCHAR(255) NOT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_topo_path_suggestion_status (status), INDEX IDX_topo_path_suggestion_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE topo_path_suggestion ADD CONSTRAINT FK_TOPO_PATH_SUGGESTION_ROCK FOREIGN KEY (rock_id) REFERENCES rock (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE topo_path_suggestion DROP FOREIGN KEY FK_TOPO_PATH_SUGGESTION_ROCK');
        $this->addSql('DROP TABLE topo_path_suggestion');
    }
}
