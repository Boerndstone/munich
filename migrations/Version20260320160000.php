<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove ToDoListe entity and to_do_liste table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE to_do_liste');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE to_do_liste (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, beschreibung LONGTEXT DEFAULT NULL, status TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
}
