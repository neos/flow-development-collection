<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Rename FLOW3 tables to follow FQCN
 */
class Version20110824124835 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("RENAME TABLE flow3_policy_role TO typo3_flow3_security_policy_role");
		$this->addSql("RENAME TABLE flow3_resource_resource TO typo3_flow3_resource_resource");
		$this->addSql("RENAME TABLE flow3_resource_resourcepointer TO typo3_flow3_resource_resourcepointer");
		$this->addSql("RENAME TABLE flow3_resource_securitypublishingconfiguration TO typo3_flow3_security_authorization_resource_securitypublis_6180a");
		$this->addSql("RENAME TABLE flow3_security_account TO typo3_flow3_security_account");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("RENAME TABLE typo3_flow3_security_policy_role TO flow3_policy_role");
		$this->addSql("RENAME TABLE typo3_flow3_resource_resource TO flow3_resource_resource");
		$this->addSql("RENAME TABLE typo3_flow3_resource_resourcepointer TO flow3_resource_resourcepointer");
		$this->addSql("RENAME TABLE typo3_flow3_security_authorization_resource_securitypublis_6180a TO flow3_resource_securitypublishingconfiguration");
		$this->addSql("RENAME TABLE typo3_flow3_security_account TO flow3_security_account");
	}
}

?>