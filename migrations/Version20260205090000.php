<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add advertisement booking table for reservation calendar';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE advertisement_booking (id SERIAL NOT NULL, advertisement_id INT NOT NULL, client_name VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, comment VARCHAR(1000) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7B43495FA1FBF71B ON advertisement_booking (advertisement_id)');
        $this->addSql('ALTER TABLE advertisement_booking ADD CONSTRAINT FK_7B43495FA1FBF71B FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE advertisement_booking DROP CONSTRAINT FK_7B43495FA1FBF71B');
        $this->addSql('DROP TABLE advertisement_booking');
    }
}
