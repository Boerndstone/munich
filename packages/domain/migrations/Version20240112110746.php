<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240112110746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes CHANGE topo_id topo_id_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3AE27D15 FOREIGN KEY (topo_id_id) REFERENCES topo (id)');
        $this->addSql('CREATE INDEX IDX_32D5C2B3AE27D15 ON routes (topo_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B3AE27D15');
        $this->addSql('DROP INDEX IDX_32D5C2B3AE27D15 ON routes');
        $this->addSql('ALTER TABLE routes CHANGE topo_id_id topo_id INT DEFAULT NULL');
    }
}
