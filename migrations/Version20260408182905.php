<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408182905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bills (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, amount NUMERIC(10, 2) NOT NULL, due_day INT NOT NULL, frequency VARCHAR(20) NOT NULL, category VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, status VARCHAR(20) DEFAULT \'UNPAID\' NOT NULL, created_at DATETIME DEFAULT NULL, budget_id INT DEFAULT NULL, INDEX IDX_22775DD036ABA6B8 (budget_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE budget (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, amount NUMERIC(15, 2) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, category VARCHAR(100) DEFAULT NULL, spent_amount NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, created_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_73F2F77BA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE complaint (id INT AUTO_INCREMENT NOT NULL, subject LONGTEXT NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, complaint_date DATE NOT NULL, response LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_5F2732B5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE contract_request (id INT AUTO_INCREMENT NOT NULL, calculated_premium NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, created_at DATETIME DEFAULT NULL, boldsign_document_id VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, asset_id INT NOT NULL, package_id INT NOT NULL, INDEX IDX_33C6F257A76ED395 (user_id), INDEX IDX_33C6F2575DA1941 (asset_id), INDEX IDX_33C6F257F44CABFF (package_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE expense (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(15, 2) NOT NULL, category VARCHAR(100) DEFAULT NULL, expense_date DATE NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, budget_id INT DEFAULT NULL, INDEX IDX_2D3A8DA636ABA6B8 (budget_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE insurance_package (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, asset_type VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, coverage_details LONGTEXT DEFAULT NULL, base_price NUMERIC(10, 2) NOT NULL, risk_multiplier NUMERIC(5, 2) DEFAULT \'1.00\' NOT NULL, duration_months INT NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE insured_asset (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(150) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, declared_value NUMERIC(12, 2) NOT NULL, approved_value NUMERIC(12, 2) DEFAULT NULL, manufacture_date DATE NOT NULL, brand VARCHAR(50) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_30236915A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE insured_contract (id INT AUTO_INCREMENT NOT NULL, asset_ref VARCHAR(150) NOT NULL, boldsign_document_id VARCHAR(255) NOT NULL, status VARCHAR(20) DEFAULT \'NOT_SIGNED\' NOT NULL, created_at DATETIME DEFAULT NULL, signed_at DATETIME DEFAULT NULL, local_file_path VARCHAR(500) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE loan (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(15, 2) NOT NULL, interest_rate NUMERIC(5, 2) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, created_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_C5D30D03A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE repayment (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(15, 2) NOT NULL, payment_date DATE NOT NULL, payment_type VARCHAR(50) DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, monthly_payment NUMERIC(15, 2) DEFAULT NULL, loan_id INT NOT NULL, INDEX IDX_50130A51CE73868F (loan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `role` (id INT AUTO_INCREMENT NOT NULL, role_name VARCHAR(50) NOT NULL, permissions LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(15, 2) NOT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, reference_type VARCHAR(30) DEFAULT NULL, reference_id INT DEFAULT NULL, currency VARCHAR(10) DEFAULT \'TND\' NOT NULL, user_id INT NOT NULL, INDEX IDX_723705D1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, password_hash VARCHAR(255) NOT NULL, role_id INT DEFAULT 2 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, is_verified TINYINT DEFAULT 0 NOT NULL, phone VARCHAR(30) DEFAULT NULL, verification_code VARCHAR(10) DEFAULT NULL, google_account TINYINT DEFAULT 0, last_login DATETIME DEFAULT NULL, face_registered TINYINT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE bills ADD CONSTRAINT FK_22775DD036ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT FK_33C6F257A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT FK_33C6F2575DA1941 FOREIGN KEY (asset_id) REFERENCES insured_asset (id)');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT FK_33C6F257F44CABFF FOREIGN KEY (package_id) REFERENCES insurance_package (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA636ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('ALTER TABLE insured_asset ADD CONSTRAINT FK_30236915A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE repayment ADD CONSTRAINT FK_50130A51CE73868F FOREIGN KEY (loan_id) REFERENCES loan (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bills DROP FOREIGN KEY FK_22775DD036ABA6B8');
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77BA76ED395');
        $this->addSql('ALTER TABLE complaint DROP FOREIGN KEY FK_5F2732B5A76ED395');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY FK_33C6F257A76ED395');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY FK_33C6F2575DA1941');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY FK_33C6F257F44CABFF');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA636ABA6B8');
        $this->addSql('ALTER TABLE insured_asset DROP FOREIGN KEY FK_30236915A76ED395');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D03A76ED395');
        $this->addSql('ALTER TABLE repayment DROP FOREIGN KEY FK_50130A51CE73868F');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395');
        $this->addSql('DROP TABLE bills');
        $this->addSql('DROP TABLE budget');
        $this->addSql('DROP TABLE complaint');
        $this->addSql('DROP TABLE contract_request');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE insurance_package');
        $this->addSql('DROP TABLE insured_asset');
        $this->addSql('DROP TABLE insured_contract');
        $this->addSql('DROP TABLE loan');
        $this->addSql('DROP TABLE repayment');
        $this->addSql('DROP TABLE `role`');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
