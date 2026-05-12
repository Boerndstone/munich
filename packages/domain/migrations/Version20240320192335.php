<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240320192335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B37F7E8D71 FOREIGN KEY (topo_id) REFERENCES topo (id)');
        $this->addSql('CREATE INDEX IDX_32D5C2B37F7E8D71 ON routes (topo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B37F7E8D71');
        $this->addSql('DROP INDEX IDX_32D5C2B37F7E8D71 ON routes');
    }
}
