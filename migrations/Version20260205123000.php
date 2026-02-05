<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add side descriptions and prices for advertisements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement ADD side_a_description VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ADD side_b_description VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ADD side_a_price NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ADD side_b_price NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE advertisement DROP side_a_description');
        $this->addSql('ALTER TABLE advertisement DROP side_b_description');
        $this->addSql('ALTER TABLE advertisement DROP side_a_price');
        $this->addSql('ALTER TABLE advertisement DROP side_b_price');
    }
}
