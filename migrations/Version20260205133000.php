<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add side A/B image fields to advertisements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement ADD side_a_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ADD side_b_image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE advertisement DROP side_a_image');
        $this->addSql('ALTER TABLE advertisement DROP side_b_image');
    }
}
