<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926072457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
                INSERT INTO product (name, price, currency) VALUES ('Bitcoin', 0.5, 'USD'), ('Litecoin', 0.2, 'USD'), ('Etherium', 1.1, 'USD');
        ");

        $this->addSql("
        INSERT INTO \"order\" (product_id, created_at, updated_at, payop_invoice_id, payop_txid, status) 
        VALUES (1, '2019-09-04 06:10:43', null, '8ec777d6-685d-4e06-b356-d7673acb47ba', null, 'new'),
               (2, '2019-09-05 10:45:23', null, '63994d55-78ca-495d-8f50-0fba32004dbf', '4bef35e0-0dec-4665-be6d-46e653a3bd19', 'new'),
               (2, '2019-09-05 14:59:56', null, '639c41a5-1615-487d-8b3a-c3b579c4ab82', '464a6d85-cd7f-463a-bb11-f8e0bb6e3bf6', 'new'),
               (2, '2019-09-05 15:06:18', null, '387a7987-5b16-40c4-930f-944dacc3873e', '79549699-20c2-4b01-b872-4340d7367f14', 'new'),
               (2, '2019-09-05 15:10:47', null, null, null, 'new'),
               (3, '2019-09-05 15:10:49', null, '1e714e34-4822-448b-95ec-05aa81fe7327', '8807baec-596c-417c-a1b2-73c8f9a8533f', 'accepted'),
               (3, '2019-09-06 06:34:55', null, 'b8bf37ab-fc69-44df-bfeb-b9a879ce20b7', '1eeda2f2-d3e1-4edd-853e-3d897bc629b2', 'new');   
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('delete from "order"');
        $this->addSql('delete from product');
    }
}
