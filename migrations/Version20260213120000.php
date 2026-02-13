<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260213120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add orders/cart entities, booking reservation link, and night photo for sides';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "order" (id SERIAL NOT NULL, user_id INT DEFAULT NULL, contact_name VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) NOT NULL, comment VARCHAR(1000) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, reserved_until TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expired_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F5299398A76ED395 ON "order" (user_id)');
        $this->addSql('COMMENT ON COLUMN "order".created_at IS \"(DC2Type:datetime_immutable)\"');
        $this->addSql('COMMENT ON COLUMN "order".reserved_until IS \"(DC2Type:datetime_immutable)\"');
        $this->addSql('COMMENT ON COLUMN "order".expired_at IS \"(DC2Type:datetime_immutable)\"');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE order_item (id SERIAL NOT NULL, order_ref_id INT NOT NULL, advertisement_id INT NOT NULL, side_code VARCHAR(10) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, price_snapshot NUMERIC(10, 2) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_52EA1F093A218B4B ON order_item (order_ref_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F09AA778AFD ON order_item (advertisement_id)');
        $this->addSql('COMMENT ON COLUMN order_item.start_date IS \"(DC2Type:date_immutable)\"');
        $this->addSql('COMMENT ON COLUMN order_item.end_date IS \"(DC2Type:date_immutable)\"');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F093A218B4B FOREIGN KEY (order_ref_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09AA778AFD FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE advertisement_booking ADD order_ref_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AEA0AE8D3A218B4B ON advertisement_booking (order_ref_id)');
        $this->addSql('ALTER TABLE advertisement_booking ADD CONSTRAINT FK_AEA0AE8D3A218B4B FOREIGN KEY (order_ref_id) REFERENCES "order" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE advertisement_side ADD night_image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement_booking DROP CONSTRAINT FK_AEA0AE8D3A218B4B');
        $this->addSql('DROP INDEX IDX_AEA0AE8D3A218B4B');
        $this->addSql('ALTER TABLE advertisement_booking DROP order_ref_id');
        $this->addSql('ALTER TABLE advertisement_side DROP night_image');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE "order"');
    }
}
