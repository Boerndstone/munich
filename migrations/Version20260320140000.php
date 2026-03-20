<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove ClimbedRoutes entity: drop join tables and climbed_routes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE climbed_routes_user DROP FOREIGN KEY FK_FFFBF87346F14086');
        $this->addSql('ALTER TABLE climbed_routes_user DROP FOREIGN KEY FK_FFFBF873A76ED395');
        $this->addSql('ALTER TABLE climbed_routes_routes DROP FOREIGN KEY FK_CBAA636D46F14086');
        $this->addSql('ALTER TABLE climbed_routes_routes DROP FOREIGN KEY FK_CBAA636DAE2C16DC');
        $this->addSql('DROP TABLE climbed_routes_user');
        $this->addSql('DROP TABLE climbed_routes_routes');
        $this->addSql('DROP TABLE climbed_routes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE climbed_routes (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, route_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE climbed_routes_user (climbed_routes_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_FFFBF87346F14086 (climbed_routes_id), INDEX IDX_FFFBF873A76ED395 (user_id), PRIMARY KEY(climbed_routes_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE climbed_routes_routes (climbed_routes_id INT NOT NULL, routes_id INT NOT NULL, INDEX IDX_CBAA636D46F14086 (climbed_routes_id), INDEX IDX_CBAA636DAE2C16DC (routes_id), PRIMARY KEY(climbed_routes_id, routes_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE climbed_routes_user ADD CONSTRAINT FK_FFFBF87346F14086 FOREIGN KEY (climbed_routes_id) REFERENCES climbed_routes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE climbed_routes_user ADD CONSTRAINT FK_FFFBF873A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE climbed_routes_routes ADD CONSTRAINT FK_CBAA636D46F14086 FOREIGN KEY (climbed_routes_id) REFERENCES climbed_routes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE climbed_routes_routes ADD CONSTRAINT FK_CBAA636DAE2C16DC FOREIGN KEY (routes_id) REFERENCES routes (id) ON DELETE CASCADE');
    }
}
