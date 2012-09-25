<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Fix column names for direct associations
 */
class Version20110923125535 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_flow3_resource_resource DROP FOREIGN KEY typo3_flow3_resource_resource_ibfk_1");
		$this->addSql("DROP INDEX IDX_11FFD19FD0275681 ON typo3_flow3_resource_resource");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource CHANGE flow3_resource_resourcepointer resourcepointer VARCHAR(255) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource ADD  CONSTRAINT typo3_flow3_resource_resource_ibfk_1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow3_resource_resourcepointer(hash)");
		$this->addSql("CREATE INDEX IDX_B4D45B323CB65D1 ON typo3_flow3_resource_resource (resourcepointer)");

		$this->addSql("ALTER TABLE typo3_flow3_security_account DROP FOREIGN KEY typo3_flow3_security_account_ibfk_1");
		$this->addSql("DROP INDEX IDX_44D0753B38110E12 ON typo3_flow3_security_account");
		$this->addSql("ALTER TABLE typo3_flow3_security_account CHANGE party_abstractparty party VARCHAR(40) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_security_account ADD CONSTRAINT typo3_flow3_security_account_ibfk_1 FOREIGN KEY (party) REFERENCES typo3_party_domain_model_abstractparty(flow3_persistence_identifier)");
		$this->addSql("CREATE INDEX IDX_65EFB31C89954EE0 ON typo3_flow3_security_account (party)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_flow3_resource_resource DROP FOREIGN KEY typo3_flow3_resource_resource_ibfk_1");
		$this->addSql("DROP INDEX IDX_B4D45B323CB65D1 ON typo3_flow3_resource_resource");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource CHANGE resourcepointer flow3_resource_resourcepointer VARCHAR(255) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource ADD CONSTRAINT typo3_flow3_resource_resource_ibfk_1 FOREIGN KEY (flow3_resource_resourcepointer) REFERENCES typo3_flow3_resource_resourcepointer(hash)");
		$this->addSql("CREATE INDEX IDX_11FFD19FD0275681 ON typo3_flow3_resource_resource (flow3_resource_resourcepointer)");

		$this->addSql("ALTER TABLE typo3_flow3_security_account DROP FOREIGN KEY typo3_flow3_security_account_ibfk_1");
		$this->addSql("DROP INDEX IDX_65EFB31C89954EE0 ON typo3_flow3_security_account");
		$this->addSql("ALTER TABLE typo3_flow3_security_account CHANGE party party_abstractparty VARCHAR(40) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_security_account ADD CONSTRAINT typo3_flow3_security_account_ibfk_1 FOREIGN KEY (party_abstractparty) REFERENCES typo3_party_domain_model_abstractparty(flow3_persistence_identifier)");
		$this->addSql("CREATE INDEX IDX_44D0753B38110E12 ON typo3_flow3_security_account (party_abstractparty)");
	}
}

?>