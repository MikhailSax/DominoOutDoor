<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create product_request table for website inquiries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_request (id SERIAL NOT NULL, advertisement_id INT NOT NULL, side_code VARCHAR(10) NOT NULL, contact_name VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) NOT NULL, comment VARCHAR(1000) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_44509D59AA778AFD ON product_request (advertisement_id)');
        $this->addSql('ALTER TABLE product_request ADD CONSTRAINT FK_44509D59AA778AFD FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE product_request DROP CONSTRAINT FK_44509D59AA778AFD');
        $this->addSql('DROP TABLE product_request');
    }
}
