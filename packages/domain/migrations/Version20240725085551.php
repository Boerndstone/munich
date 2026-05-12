<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240725085551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rock ADD preview_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP subscribe_to_newsletter');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rock DROP preview_image');
        $this->addSql('ALTER TABLE `user` ADD subscribe_to_newsletter TINYINT(1) DEFAULT NULL');
    }
}
