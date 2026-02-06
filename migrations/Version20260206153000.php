<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add side_code to advertisement_booking and backfill data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE advertisement_booking ADD side_code VARCHAR(10) DEFAULT 'A' NOT NULL");
        $this->addSql("UPDATE advertisement_booking b SET side_code = COALESCE(NULLIF(UPPER(TRIM((a.sides::jsonb->>0))), ''), 'A') FROM advertisement a WHERE b.advertisement_id = a.id");
        $this->addSql('ALTER TABLE advertisement_booking ALTER COLUMN side_code DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement_booking DROP side_code');
    }
}
