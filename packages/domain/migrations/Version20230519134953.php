<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230519134953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE climbed_routes (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE climbed_routes_user (climbed_routes_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_FFFBF87346F14086 (climbed_routes_id), INDEX IDX_FFFBF873A76ED395 (user_id), PRIMARY KEY(climbed_routes_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE climbed_routes_routes (climbed_routes_id INT NOT NULL, routes_id INT NOT NULL, INDEX IDX_CBAA636D46F14086 (climbed_routes_id), INDEX IDX_CBAA636DAE2C16DC (routes_id), PRIMARY KEY(climbed_routes_id, routes_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE climbed_routes_user ADD CONSTRAINT FK_FFFBF87346F14086 FOREIGN KEY (climbed_routes_id) REFERENCES climbed_routes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE climbed_routes_user ADD CONSTRAINT FK_FFFBF873A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE climbed_routes_routes ADD CONSTRAINT FK_CBAA636D46F14086 FOREIGN KEY (climbed_routes_id) REFERENCES climbed_routes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE climbed_routes_routes ADD CONSTRAINT FK_CBAA636DAE2C16DC FOREIGN KEY (routes_id) REFERENCES routes (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE climbed_routes_user DROP FOREIGN KEY FK_FFFBF87346F14086');
        $this->addSql('ALTER TABLE climbed_routes_user DROP FOREIGN KEY FK_FFFBF873A76ED395');
        $this->addSql('ALTER TABLE climbed_routes_routes DROP FOREIGN KEY FK_CBAA636D46F14086');
        $this->addSql('ALTER TABLE climbed_routes_routes DROP FOREIGN KEY FK_CBAA636DAE2C16DC');
        $this->addSql('DROP TABLE climbed_routes');
        $this->addSql('DROP TABLE climbed_routes_user');
        $this->addSql('DROP TABLE climbed_routes_routes');
    }
}
