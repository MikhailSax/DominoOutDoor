<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create advertisement_side table and backfill A/B side data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE advertisement_side (id SERIAL NOT NULL, advertisement_id INT NOT NULL, code VARCHAR(10) NOT NULL, description VARCHAR(1000) DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B4C7965DAA778AFD ON advertisement_side (advertisement_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_advertisement_side_code ON advertisement_side (advertisement_id, code)');
        $this->addSql('ALTER TABLE advertisement_side ADD CONSTRAINT FK_B4C7965DAA778AFD FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("INSERT INTO advertisement_side (advertisement_id, code, description, price, image)\n            SELECT id, 'A', side_a_description, side_a_price, side_a_image\n            FROM advertisement\n            WHERE side_a_description IS NOT NULL OR side_a_price IS NOT NULL OR side_a_image IS NOT NULL");

        $this->addSql("INSERT INTO advertisement_side (advertisement_id, code, description, price, image)\n            SELECT id, 'B', side_b_description, side_b_price, side_b_image\n            FROM advertisement\n            WHERE side_b_description IS NOT NULL OR side_b_price IS NOT NULL OR side_b_image IS NOT NULL");

        $this->addSql("INSERT INTO advertisement_side (advertisement_id, code)\n            SELECT a.id, s.side_code\n            FROM advertisement a\n            CROSS JOIN LATERAL (SELECT jsonb_array_elements_text(COALESCE(a.sides::jsonb, '[]'::jsonb)) AS side_code) s\n            ON CONFLICT (advertisement_id, code) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE advertisement_side DROP CONSTRAINT FK_B4C7965DAA778AFD');
        $this->addSql('DROP TABLE advertisement_side');
    }
}
