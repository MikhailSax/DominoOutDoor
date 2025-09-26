<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924080552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE advertisement ADD place_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ADD sides JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ALTER code DROP NOT NULL');
        $this->addSql('ALTER TABLE advertisement ALTER address DROP NOT NULL');
        $this->addSql('ALTER TABLE advertisement ALTER address TYPE VARCHAR(500)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE advertisement DROP place_number');
        $this->addSql('ALTER TABLE advertisement DROP sides');
        $this->addSql('ALTER TABLE advertisement ALTER code SET NOT NULL');
        $this->addSql('ALTER TABLE advertisement ALTER address SET NOT NULL');
        $this->addSql('ALTER TABLE advertisement ALTER address TYPE VARCHAR(255)');
    }
}
