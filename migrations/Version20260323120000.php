<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Join table user_editable_rock for rock-scoped admin editors (ROLE_ROCK_EDITOR).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_editable_rock (user_id INT NOT NULL, rock_id INT NOT NULL, INDEX IDX_USER_EDITABLE_ROCK_USER (user_id), INDEX IDX_USER_EDITABLE_ROCK_ROCK (rock_id), PRIMARY KEY(user_id, rock_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_editable_rock ADD CONSTRAINT FK_USER_EDITABLE_ROCK_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_editable_rock ADD CONSTRAINT FK_USER_EDITABLE_ROCK_ROCK FOREIGN KEY (rock_id) REFERENCES rock (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_editable_rock');
    }
}
