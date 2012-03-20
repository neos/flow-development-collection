<?php
namespace TYPO3\FLOW3\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adjust default values to NOT NULL unless allowed in model.
 */
class Version20120329220340 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_flow3_mvc_routing_objectpathmapping CHANGE identifier identifier VARCHAR(255) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource CHANGE filename filename VARCHAR(255) NOT NULL, CHANGE fileextension fileextension VARCHAR(255) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_security_authorization_resource_securitypublis_6180a CHANGE allowedroles allowedroles LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");
		$this->addSql("ALTER TABLE typo3_flow3_security_account CHANGE accountidentifier accountidentifier VARCHAR(255) NOT NULL, CHANGE authenticationprovidername authenticationprovidername VARCHAR(255) NOT NULL, CHANGE credentialssource credentialssource VARCHAR(255) NOT NULL, CHANGE creationdate creationdate DATETIME NOT NULL, CHANGE roles roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");

	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_flow3_mvc_routing_objectpathmapping CHANGE identifier identifier VARCHAR(255) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource CHANGE filename filename VARCHAR(255) DEFAULT NULL, CHANGE fileextension fileextension VARCHAR(255) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_security_account CHANGE accountidentifier accountidentifier VARCHAR(255) DEFAULT NULL, CHANGE authenticationprovidername authenticationprovidername VARCHAR(255) DEFAULT NULL, CHANGE credentialssource credentialssource VARCHAR(255) DEFAULT NULL, CHANGE creationdate creationdate DATETIME DEFAULT NULL, CHANGE roles roles LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'");
		$this->addSql("ALTER TABLE typo3_flow3_security_authorization_resource_securitypublis_6180a CHANGE allowedroles allowedroles LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'");
	}
}

?>