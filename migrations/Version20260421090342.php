<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421090342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bills CHANGE category category VARCHAR(50) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'UNPAID\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE bills ADD CONSTRAINT FK_22775DD036ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('CREATE INDEX IDX_22775DD036ABA6B8 ON bills (budget_id)');
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY `budget_ibfk_1`');
        $this->addSql('ALTER TABLE budget CHANGE category category VARCHAR(100) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE budget RENAME INDEX user_id TO IDX_73F2F77BA76ED395');
        $this->addSql('ALTER TABLE complaint DROP FOREIGN KEY `complaint_ibfk_1`');
        $this->addSql('ALTER TABLE complaint CHANGE subject subject LONGTEXT NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL, CHANGE response response LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE complaint RENAME INDEX user_id TO IDX_5F2732B5A76ED395');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY `contract_request_ibfk_1`');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY `contract_request_ibfk_2`');
        $this->addSql('ALTER TABLE contract_request CHANGE calculated_premium calculated_premium NUMERIC(10, 2) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE boldsign_document_id boldsign_document_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT FK_33C6F257A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT FK_33C6F2575DA1941 FOREIGN KEY (asset_id) REFERENCES insured_asset (id)');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT FK_33C6F257F44CABFF FOREIGN KEY (package_id) REFERENCES insurance_package (id)');
        $this->addSql('CREATE INDEX IDX_33C6F257A76ED395 ON contract_request (user_id)');
        $this->addSql('ALTER TABLE contract_request RENAME INDEX asset_id TO IDX_33C6F2575DA1941');
        $this->addSql('ALTER TABLE contract_request RENAME INDEX package_id TO IDX_33C6F257F44CABFF');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY `expense_ibfk_1`');
        $this->addSql('ALTER TABLE expense CHANGE category category VARCHAR(100) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA636ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('ALTER TABLE expense RENAME INDEX budget_id TO IDX_2D3A8DA636ABA6B8');
        $this->addSql('ALTER TABLE insurance_package CHANGE description description LONGTEXT DEFAULT NULL, CHANGE coverage_details coverage_details LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE insured_asset DROP FOREIGN KEY `insured_asset_ibfk_1`');
        $this->addSql('DROP INDEX idx_reference ON insured_asset');
        $this->addSql('ALTER TABLE insured_asset CHANGE reference reference VARCHAR(150) DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE location location VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE approved_value approved_value NUMERIC(12, 2) DEFAULT NULL, CHANGE brand brand VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE insured_asset ADD CONSTRAINT FK_30236915A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE insured_asset RENAME INDEX user_id TO IDX_30236915A76ED395');
        $this->addSql('ALTER TABLE insured_contract DROP FOREIGN KEY `fk_asset`');
        $this->addSql('DROP INDEX uk_document_id ON insured_contract');
        $this->addSql('DROP INDEX idx_asset_ref ON insured_contract');
        $this->addSql('ALTER TABLE insured_contract CHANGE status status VARCHAR(20) DEFAULT \'NOT_SIGNED\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE signed_at signed_at DATETIME DEFAULT NULL, CHANGE local_file_path local_file_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY `loan_ibfk_1`');
        $this->addSql('ALTER TABLE loan CHANGE status status VARCHAR(20) DEFAULT \'active\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE loan RENAME INDEX user_id TO IDX_C5D30D03A76ED395');
        $this->addSql('ALTER TABLE repayment DROP FOREIGN KEY `repayment_ibfk_1`');
        $this->addSql('ALTER TABLE repayment CHANGE payment_type payment_type VARCHAR(50) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL, CHANGE monthly_payment monthly_payment NUMERIC(15, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE repayment ADD CONSTRAINT FK_50130A51CE73868F FOREIGN KEY (loan_id) REFERENCES loan (id)');
        $this->addSql('ALTER TABLE repayment RENAME INDEX loan_id TO IDX_50130A51CE73868F');
        $this->addSql('ALTER TABLE role CHANGE permissions permissions LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY `transaction_ibfk_1`');
        $this->addSql('ALTER TABLE transaction CHANGE status status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE reference_type reference_type VARCHAR(30) DEFAULT NULL, CHANGE currency currency VARCHAR(10) DEFAULT \'TND\' NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transaction RENAME INDEX user_id TO IDX_723705D1A76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY `user_ibfk_1`');
        $this->addSql('DROP INDEX role_id ON user');
        $this->addSql('ALTER TABLE user ADD is_active TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE phone phone VARCHAR(30) DEFAULT NULL, CHANGE verification_code verification_code VARCHAR(10) DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX email TO UNIQ_8D93D649E7927C74');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bills DROP FOREIGN KEY FK_22775DD036ABA6B8');
        $this->addSql('DROP INDEX IDX_22775DD036ABA6B8 ON bills');
        $this->addSql('ALTER TABLE bills CHANGE category category VARCHAR(50) DEFAULT \'NULL\', CHANGE description description VARCHAR(255) DEFAULT \'NULL\', CHANGE status status VARCHAR(20) DEFAULT \'\'\'UNPAID\'\'\', CHANGE created_at created_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77BA76ED395');
        $this->addSql('ALTER TABLE budget CHANGE category category VARCHAR(100) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT `budget_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE budget RENAME INDEX idx_73f2f77ba76ed395 TO user_id');
        $this->addSql('ALTER TABLE complaint DROP FOREIGN KEY FK_5F2732B5A76ED395');
        $this->addSql('ALTER TABLE complaint CHANGE subject subject TEXT NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'\'\'pending\'\'\' NOT NULL, CHANGE response response TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\'');
        $this->addSql('ALTER TABLE complaint ADD CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE complaint RENAME INDEX idx_5f2732b5a76ed395 TO user_id');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY FK_33C6F257A76ED395');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY FK_33C6F2575DA1941');
        $this->addSql('ALTER TABLE contract_request DROP FOREIGN KEY FK_33C6F257F44CABFF');
        $this->addSql('DROP INDEX IDX_33C6F257A76ED395 ON contract_request');
        $this->addSql('ALTER TABLE contract_request CHANGE calculated_premium calculated_premium NUMERIC(10, 2) DEFAULT \'NULL\', CHANGE status status VARCHAR(20) DEFAULT \'\'\'PENDING\'\'\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\', CHANGE boldsign_document_id boldsign_document_id VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT `contract_request_ibfk_1` FOREIGN KEY (asset_id) REFERENCES insured_asset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract_request ADD CONSTRAINT `contract_request_ibfk_2` FOREIGN KEY (package_id) REFERENCES insurance_package (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract_request RENAME INDEX idx_33c6f257f44cabff TO package_id');
        $this->addSql('ALTER TABLE contract_request RENAME INDEX idx_33c6f2575da1941 TO asset_id');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA636ABA6B8');
        $this->addSql('ALTER TABLE expense CHANGE category category VARCHAR(100) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\'');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT `expense_ibfk_1` FOREIGN KEY (budget_id) REFERENCES budget (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE expense RENAME INDEX idx_2d3a8da636aba6b8 TO budget_id');
        $this->addSql('ALTER TABLE insurance_package CHANGE description description TEXT DEFAULT NULL, CHANGE coverage_details coverage_details TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\'');
        $this->addSql('ALTER TABLE insured_asset DROP FOREIGN KEY FK_30236915A76ED395');
        $this->addSql('ALTER TABLE insured_asset CHANGE reference reference VARCHAR(150) DEFAULT \'NULL\', CHANGE type type VARCHAR(50) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\', CHANGE location location VARCHAR(255) DEFAULT \'NULL\', CHANGE approved_value approved_value NUMERIC(12, 2) DEFAULT \'NULL\', CHANGE brand brand VARCHAR(50) DEFAULT \'NULL\', CHANGE user_id user_id INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE insured_asset ADD CONSTRAINT `insured_asset_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX idx_reference ON insured_asset (reference)');
        $this->addSql('ALTER TABLE insured_asset RENAME INDEX idx_30236915a76ed395 TO user_id');
        $this->addSql('ALTER TABLE insured_contract CHANGE status status ENUM(\'NOT_SIGNED\', \'SIGNED\', \'REJECTED\') DEFAULT \'\'\'NOT_SIGNED\'\'\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE signed_at signed_at DATETIME DEFAULT \'NULL\', CHANGE local_file_path local_file_path VARCHAR(500) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE insured_contract ADD CONSTRAINT `fk_asset` FOREIGN KEY (asset_ref) REFERENCES insured_asset (reference) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX uk_document_id ON insured_contract (boldsign_document_id)');
        $this->addSql('CREATE INDEX idx_asset_ref ON insured_contract (asset_ref)');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D03A76ED395');
        $this->addSql('ALTER TABLE loan CHANGE status status VARCHAR(20) DEFAULT \'\'\'active\'\'\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\'');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE loan RENAME INDEX idx_c5d30d03a76ed395 TO user_id');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE repayment DROP FOREIGN KEY FK_50130A51CE73868F');
        $this->addSql('ALTER TABLE repayment CHANGE payment_type payment_type VARCHAR(50) DEFAULT \'NULL\', CHANGE status status VARCHAR(20) DEFAULT \'\'\'pending\'\'\' NOT NULL, CHANGE monthly_payment monthly_payment NUMERIC(15, 2) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE repayment ADD CONSTRAINT `repayment_ibfk_1` FOREIGN KEY (loan_id) REFERENCES loan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE repayment RENAME INDEX idx_50130a51ce73868f TO loan_id');
        $this->addSql('ALTER TABLE `role` CHANGE permissions permissions TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395');
        $this->addSql('ALTER TABLE transaction CHANGE status status VARCHAR(20) DEFAULT \'\'\'PENDING\'\'\' NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\', CHANGE reference_type reference_type VARCHAR(30) DEFAULT \'NULL\', CHANGE currency currency VARCHAR(10) DEFAULT \'\'\'TND\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transaction RENAME INDEX idx_723705d1a76ed395 TO user_id');
        $this->addSql('ALTER TABLE `user` DROP is_active, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\', CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\', CHANGE phone phone VARCHAR(30) DEFAULT \'NULL\', CHANGE verification_code verification_code VARCHAR(10) DEFAULT \'NULL\', CHANGE last_login last_login DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('CREATE INDEX role_id ON `user` (role_id)');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649e7927c74 TO email');
    }
}
