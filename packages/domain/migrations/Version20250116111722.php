<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250116111722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topo ADD path_collection LONGTEXT DEFAULT NULL, DROP topo_image, DROP path_coordinates, DROP canvas_background_image');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topo ADD topo_image LONGTEXT NOT NULL, ADD path_coordinates JSON DEFAULT NULL, ADD canvas_background_image VARCHAR(255) DEFAULT NULL, DROP path_collection');
    }
}
