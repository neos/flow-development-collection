<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\MigrationException;

/**
 * Adjusts schema to Flow 3.0 "Party package decoupling"
 */
class Version20150206114820 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 * @throws MigrationException
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		if ($this->isPartyPackageInstalled()) {
			$this->addSql("ALTER TABLE typo3_flow_security_account DROP FOREIGN KEY typo3_flow_security_account_ibfk_1");
			$this->addSql("DROP INDEX IDX_65EFB31C89954EE0 ON typo3_flow_security_account");
		}

		$this->addSql("ALTER TABLE typo3_flow_security_account DROP party");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_flow_security_account ADD party VARCHAR(40) DEFAULT NULL");

		if ($this->isPartyPackageInstalled()) {
			$this->addSql("CREATE INDEX IDX_65EFB31C89954EE0 ON typo3_flow_security_account (party)");
			$this->addSql("ALTER TABLE typo3_flow_security_account ADD CONSTRAINT typo3_flow_security_account_ibfk_1 FOREIGN KEY (party) REFERENCES typo3_party_domain_model_abstractparty (persistence_object_identifier)");
		}
	}

	/**
	 * @return boolean
	 */
	protected function isPartyPackageInstalled() {
		return $this->sm->tablesExist(array('typo3_party_domain_model_abstractparty'));
	}
}
