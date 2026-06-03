<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store original 1C XML data for advertisements, sides, and types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement ADD one_c_ref VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ADD one_c_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement_side ADD one_c_ref VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement_side ADD one_c_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement_type ADD one_c_ref VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement_type ADD one_c_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement_type DROP one_c_data');
        $this->addSql('ALTER TABLE advertisement_type DROP one_c_ref');
        $this->addSql('ALTER TABLE advertisement_side DROP one_c_data');
        $this->addSql('ALTER TABLE advertisement_side DROP one_c_ref');
        $this->addSql('ALTER TABLE advertisement DROP one_c_data');
        $this->addSql('ALTER TABLE advertisement DROP one_c_ref');
    }
}
