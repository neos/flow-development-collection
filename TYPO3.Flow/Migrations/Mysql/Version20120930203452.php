<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema,
	TYPO3\Flow\Persistence\Doctrine\Service;

/**
 * Adjust flow3 to flow
 */
class Version20120930203452 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

			// collect foreign keys pointing to "our" tables
		$tableNames = array(
			'typo3_flow3_resource_publishing_abstractpublishingconfiguration',
			'typo3_flow3_resource_resource',
			'typo3_flow3_security_account',
			'typo3_flow3_security_authorization_resource_securitypubli_6180a',
		);
		$foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'flow3_persistence_identifier', 'persistence_object_identifier');

			// drop FK constraints
		foreach ($foreignKeyHandlingSql['drop'] as $sql) {
			$this->addSql($sql);
		}

			// rename identifier fields
		$this->addSql("ALTER TABLE typo3_flow3_resource_publishing_abstractpublishingconfiguration DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow3_resource_publishing_abstractpublishingconfiguration CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_resource_publishing_abstractpublishingconfiguration ADD PRIMARY KEY (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource ADD PRIMARY KEY (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_flow3_security_account DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow3_security_account CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_security_account ADD PRIMARY KEY (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a ADD PRIMARY KEY (persistence_object_identifier)");

			// add back FK constraints
		foreach ($foreignKeyHandlingSql['add'] as $sql) {
			$this->addSql($sql);
		}

			// rename tables
		$this->addSql("RENAME TABLE typo3_flow3_mvc_routing_objectpathmapping TO typo3_flow_mvc_routing_objectpathmapping");
		$this->addSql("RENAME TABLE typo3_flow3_resource_publishing_abstractpublishingconfiguration TO typo3_flow_resource_publishing_abstractpublishingconfiguration");
		$this->addSql("RENAME TABLE typo3_flow3_resource_resource TO typo3_flow_resource_resource");
		$this->addSql("RENAME TABLE typo3_flow3_resource_resourcepointer TO typo3_flow_resource_resourcepointer");
		$this->addSql("RENAME TABLE typo3_flow3_security_account TO typo3_flow_security_account");
		$this->addSql("RENAME TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a TO typo3_flow_security_authorization_resource_securitypublis_861cb");
		$this->addSql("RENAME TABLE typo3_flow3_security_policy_role TO typo3_flow_security_policy_role");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

			// collect foreign keys pointing to "our" tables
		$tableNames = array(
			'typo3_flow_resource_publishing_abstractpublishingconfiguration',
			'typo3_flow_resource_resource',
			'typo3_flow_security_account',
			'typo3_flow_security_authorization_resource_securitypublis_861cb',
		);
		$foreignKeyHandlingSql = \TYPO3\Flow\Persistence\Doctrine\Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'persistence_object_identifier', 'flow3_persistence_identifier');

			// drop FK constraints
		foreach ($foreignKeyHandlingSql['drop'] as $sql) {
			$this->addSql($sql);
		}

			// rename identifier fields
		$this->addSql("ALTER TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration ADD PRIMARY KEY (flow3_persistence_identifier)");
		$this->addSql("ALTER TABLE typo3_flow_resource_resource DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow_resource_resource CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow_resource_resource ADD PRIMARY KEY (flow3_persistence_identifier)");
		$this->addSql("ALTER TABLE typo3_flow_security_account DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow_security_account CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow_security_account ADD PRIMARY KEY (flow3_persistence_identifier)");
		$this->addSql("ALTER TABLE typo3_flow_security_authorization_resource_securitypublis_861cb DROP PRIMARY KEY");
		$this->addSql("ALTER TABLE typo3_flow_security_authorization_resource_securitypublis_861cb CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
		$this->addSql("ALTER TABLE typo3_flow_security_authorization_resource_securitypublis_861cb ADD PRIMARY KEY (flow3_persistence_identifier)");

			// add back FK constraints
		foreach ($foreignKeyHandlingSql['add'] as $sql) {
			$this->addSql($sql);
		}

			// rename tables
		$this->addSql("RENAME TABLE typo3_flow_mvc_routing_objectpathmapping TO typo3_flow3_mvc_routing_objectpathmapping");
		$this->addSql("RENAME TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration TO typo3_flow3_resource_publishing_abstractpublishingconfiguration");
		$this->addSql("RENAME TABLE typo3_flow_resource_resource TO typo3_flow3_resource_resource");
		$this->addSql("RENAME TABLE typo3_flow_resource_resourcepointer TO typo3_flow3_resource_resourcepointer");
		$this->addSql("RENAME TABLE typo3_flow_security_account TO typo3_flow3_security_account");
		$this->addSql("RENAME TABLE typo3_flow_security_authorization_resource_securitypublis_861cb TO typo3_flow3_security_authorization_resource_securitypubli_6180a");
		$this->addSql("RENAME TABLE typo3_flow_security_policy_role TO typo3_flow3_security_policy_role");
	}

}

?>