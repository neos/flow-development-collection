<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Remove table and relations for Role entity, instead roleidentifiers are now stored as simple comma separated list in the account table
 */
class Version20140507145146 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("ALTER TABLE typo3_flow_security_account_roles_join DROP CONSTRAINT fk_adf11bbc23a1047c");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join DROP CONSTRAINT fk_d459c58e23a1047c");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join DROP CONSTRAINT fk_d459c58e6a8abcde");
		$this->addSql("DROP TABLE typo3_flow_security_policy_role");
		$this->addSql("DROP TABLE typo3_flow_security_authorization_resource_securitypublis_861cb");
		$this->addSql("DROP TABLE typo3_flow_security_account_roles_join");
		$this->addSql("DROP TABLE typo3_flow_security_policy_role_parentroles_join");
		$this->addSql("ALTER TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration DROP dtype");
		$this->addSql("ALTER TABLE typo3_flow_security_account ADD roleidentifiers TEXT DEFAULT NULL");
		$this->addSql("COMMENT ON COLUMN typo3_flow_security_account.roleidentifiers IS '(DC2Type:simple_array)'");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("CREATE TABLE typo3_flow_security_policy_role (identifier VARCHAR(255) NOT NULL, sourcehint VARCHAR(6) NOT NULL, PRIMARY KEY(identifier))");
		$this->addSql("CREATE TABLE typo3_flow_security_authorization_resource_securitypublis_861cb (persistence_object_identifier VARCHAR(40) NOT NULL, allowedroles TEXT NOT NULL, PRIMARY KEY(persistence_object_identifier))");
		$this->addSql("COMMENT ON COLUMN typo3_flow_security_authorization_resource_securitypublis_861cb.allowedroles IS '(DC2Type:array)'");
		$this->addSql("CREATE TABLE typo3_flow_security_account_roles_join (flow_security_account VARCHAR(40) NOT NULL, flow_policy_role VARCHAR(255) NOT NULL, PRIMARY KEY(flow_security_account, flow_policy_role))");
		$this->addSql("CREATE INDEX idx_adf11bbc23a1047c ON typo3_flow_security_account_roles_join (flow_policy_role)");
		$this->addSql("CREATE INDEX idx_adf11bbc58842efc ON typo3_flow_security_account_roles_join (flow_security_account)");
		$this->addSql("CREATE TABLE typo3_flow_security_policy_role_parentroles_join (flow_policy_role VARCHAR(255) NOT NULL, parent_role VARCHAR(255) NOT NULL, PRIMARY KEY(flow_policy_role, parent_role))");
		$this->addSql("CREATE INDEX idx_d459c58e6a8abcde ON typo3_flow_security_policy_role_parentroles_join (parent_role)");
		$this->addSql("CREATE INDEX idx_d459c58e23a1047c ON typo3_flow_security_policy_role_parentroles_join (flow_policy_role)");
		$this->addSql("ALTER TABLE typo3_flow_security_authorization_resource_securitypublis_861cb ADD CONSTRAINT fk_234846d521e3d446 FOREIGN KEY (persistence_object_identifier) REFERENCES typo3_flow_resource_publishing_abstractpublishingconfiguration (persistence_object_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
		$this->addSql("ALTER TABLE typo3_flow_security_account_roles_join ADD CONSTRAINT fk_adf11bbc58842efc FOREIGN KEY (flow_security_account) REFERENCES typo3_flow_security_account (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
		$this->addSql("ALTER TABLE typo3_flow_security_account_roles_join ADD CONSTRAINT fk_adf11bbc23a1047c FOREIGN KEY (flow_policy_role) REFERENCES typo3_flow_security_policy_role (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join ADD CONSTRAINT fk_d459c58e23a1047c FOREIGN KEY (flow_policy_role) REFERENCES typo3_flow_security_policy_role (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join ADD CONSTRAINT fk_d459c58e6a8abcde FOREIGN KEY (parent_role) REFERENCES typo3_flow_security_policy_role (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
		$this->addSql("ALTER TABLE typo3_flow_security_account DROP roleidentifiers");
		$this->addSql("ALTER TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration ADD dtype VARCHAR(255) NOT NULL");
	}
}