<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adjust DB schema to a clean state (remove cruft that built up in the past)
 */
class Version20150309181635 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("DROP INDEX flow3_identity_typo3_flow3_security_account ON typo3_flow_security_account");
		$this->addSql("CREATE UNIQUE INDEX flow_identity_typo3_flow_security_account ON typo3_flow_security_account (accountidentifier, authenticationprovidername)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("DROP INDEX flow_identity_typo3_flow_security_account ON typo3_flow_security_account");
		$this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_flow3_security_account ON typo3_flow_security_account (accountidentifier, authenticationprovidername)");
	}
}