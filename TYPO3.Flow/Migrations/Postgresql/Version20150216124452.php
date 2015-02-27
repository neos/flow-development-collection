<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adjust schema to Flow 3.0 "Party package decoupling"
 */
class Version20150216124452 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");
		$this->abortIf($this->isPartyPackageInstalled() && !$this->isAccordingPartyMigrationExecuted(), 'This migration requires a current version of the TYPO3.Party package and its migration 20150216124451 being applied.');

		$this->addSql("ALTER TABLE typo3_flow_security_account DROP CONSTRAINT fk_65efb31c89954ee0");
		$this->addSql("DROP INDEX idx_65efb31c89954ee0");
		$this->addSql("ALTER TABLE typo3_flow_security_account DROP party");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("ALTER TABLE typo3_flow_security_account ADD party VARCHAR(40) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_flow_security_account ADD CONSTRAINT fk_65efb31c89954ee0 FOREIGN KEY (party) REFERENCES typo3_party_domain_model_abstractparty (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
		$this->addSql("CREATE INDEX idx_65efb31c89954ee0 ON typo3_flow_security_account (party)");
	}

	/**
	 * @return boolean
	 */
	protected function isPartyPackageInstalled() {
		return $this->sm->tablesExist(array('typo3_party_domain_model_abstractparty'));
	}

	/**
	 * @return boolean
	 */
	protected function isAccordingPartyMigrationExecuted() {
		return $this->sm->tablesExist(array('typo3_party_domain_model_abstractparty_accounts_join'));
	}
}