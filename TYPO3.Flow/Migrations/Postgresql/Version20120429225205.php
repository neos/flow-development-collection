<?php
namespace TYPO3\FLOW3\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Create unique indexes for identity properties
 */
class Version20120429225205 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_flow3_security_account ON typo3_flow3_security_account (accountidentifier, authenticationprovidername)");
		$this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_flow3_security_policy_role ON typo3_flow3_security_policy_role (identifier)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("DROP INDEX flow3_identity_typo3_flow3_security_account");
		$this->addSql("DROP INDEX flow3_identity_typo3_flow3_security_policy_role");
	}
}

?>