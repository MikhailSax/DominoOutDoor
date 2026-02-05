<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make user.yandex_id BIGINT to avoid overflow for OAuth provider IDs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER yandex_id TYPE BIGINT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ALTER yandex_id TYPE INT');
    }
}
