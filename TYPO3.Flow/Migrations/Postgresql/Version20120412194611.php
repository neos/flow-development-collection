<?php
namespace TYPO3\FLOW3\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Create tables for PostgreSQL
 */
class Version20120412194611 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("CREATE TABLE typo3_flow3_resource_resourcepointer (hash VARCHAR(255) NOT NULL, PRIMARY KEY(hash))");
		$this->addSql("CREATE TABLE typo3_flow3_mvc_routing_objectpathmapping (objecttype VARCHAR(255) NOT NULL, uripattern VARCHAR(255) NOT NULL, pathsegment VARCHAR(255) NOT NULL, identifier VARCHAR(255) NOT NULL, PRIMARY KEY(objecttype, uripattern, pathsegment))");
		$this->addSql("CREATE TABLE typo3_flow3_resource_resource (flow3_persistence_identifier VARCHAR(40) NOT NULL, resourcepointer VARCHAR(255) DEFAULT NULL, filename VARCHAR(255) NOT NULL, fileextension VARCHAR(255) NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
		$this->addSql("CREATE INDEX IDX_B4D45B323CB65D1 ON typo3_flow3_resource_resource (resourcepointer)");
		$this->addSql("CREATE TABLE typo3_flow3_security_account (flow3_persistence_identifier VARCHAR(40) NOT NULL, party VARCHAR(40) DEFAULT NULL, accountidentifier VARCHAR(255) NOT NULL, authenticationprovidername VARCHAR(255) NOT NULL, credentialssource VARCHAR(255) NOT NULL, creationdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expirationdate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, roles TEXT NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
		$this->addSql("CREATE INDEX IDX_65EFB31C89954EE0 ON typo3_flow3_security_account (party)");
		$this->addSql("COMMENT ON COLUMN typo3_flow3_security_account.roles IS '(DC2Type:array)'");
		$this->addSql("CREATE TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a (flow3_persistence_identifier VARCHAR(40) NOT NULL, allowedroles TEXT NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
		$this->addSql("COMMENT ON COLUMN typo3_flow3_security_authorization_resource_securitypubli_6180a.allowedroles IS '(DC2Type:array)'");
		$this->addSql("CREATE TABLE typo3_flow3_security_policy_role (identifier VARCHAR(255) NOT NULL, PRIMARY KEY(identifier))");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource ADD CONSTRAINT FK_B4D45B323CB65D1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow3_resource_resourcepointer (hash) NOT DEFERRABLE INITIALLY IMMEDIATE");
		$this->addSql("ALTER TABLE typo3_flow3_security_account ADD CONSTRAINT FK_65EFB31C89954EE0 FOREIGN KEY (party) REFERENCES typo3_party_domain_model_abstractparty (flow3_persistence_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("ALTER TABLE typo3_flow3_resource_resource DROP CONSTRAINT FK_B4D45B323CB65D1");
		$this->addSql("ALTER TABLE typo3_flow3_security_account DROP CONSTRAINT FK_65EFB31C89954EE0");
		$this->addSql("DROP TABLE typo3_flow3_resource_resourcepointer");
		$this->addSql("DROP TABLE typo3_flow3_mvc_routing_objectpathmapping");
		$this->addSql("DROP TABLE typo3_flow3_resource_resource");
		$this->addSql("DROP TABLE typo3_flow3_security_account");
		$this->addSql("DROP TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a");
		$this->addSql("DROP TABLE typo3_flow3_security_policy_role");
	}
}

?>