<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix user auth schema: nullable yandex_id and normalize email/phone column lengths';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER yandex_id DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER email TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE "user" ALTER phone TYPE VARCHAR(30)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ALTER yandex_id SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER email TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE "user" ALTER phone TYPE VARCHAR(255)');
    }
}
