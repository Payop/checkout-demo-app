<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926072456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, product_id INTEGER NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, payop_invoice_id VARCHAR(255) DEFAULT NULL, payop_txid VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F52993984584665A ON "order" (product_id)');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, currency VARCHAR(255) NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE product');
    }
}
