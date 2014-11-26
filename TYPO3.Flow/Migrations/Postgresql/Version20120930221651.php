<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema,
	TYPO3\Flow\Persistence\Doctrine\Service;

/**
 * Adjust flow3 to flow
 */
class Version20120930221651 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

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
		$this->addSql("ALTER TABLE typo3_flow3_resource_publishing_abstractpublishingconfiguration RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");
		$this->addSql("ALTER TABLE typo3_flow3_security_account RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");
		$this->addSql("ALTER TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");

		// add back FK constraints
		foreach ($foreignKeyHandlingSql['add'] as $sql) {
			$this->addSql($sql);
		}

		// rename tables
		$this->addSql("ALTER TABLE typo3_flow3_mvc_routing_objectpathmapping RENAME TO typo3_flow_mvc_routing_objectpathmapping");
		$this->addSql("ALTER TABLE typo3_flow3_resource_publishing_abstractpublishingconfiguration RENAME TO typo3_flow_resource_publishing_abstractpublishingconfiguration");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resource RENAME TO typo3_flow_resource_resource");
		$this->addSql("ALTER TABLE typo3_flow3_resource_resourcepointer RENAME TO typo3_flow_resource_resourcepointer");
		$this->addSql("ALTER TABLE typo3_flow3_security_account RENAME TO typo3_flow_security_account");
		$this->addSql("ALTER TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a RENAME TO typo3_flow_security_authorization_resource_securitypublis_861cb");
		$this->addSql("ALTER TABLE typo3_flow3_security_policy_role RENAME TO typo3_flow_security_policy_role");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		// collect foreign keys pointing to "our" tables
		$tableNames = array(
			'typo3_flow_resource_publishing_abstractpublishingconfiguration',
			'typo3_flow_resource_resource',
			'typo3_flow_security_account',
			'typo3_flow_security_authorization_resource_securitypublis_861cb',
		);
		$foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'persistence_object_identifier', 'flow3_persistence_identifier');

		// drop FK constraints
		foreach ($foreignKeyHandlingSql['drop'] as $sql) {
			$this->addSql($sql);
		}

		// rename identifier fields
		$this->addSql("ALTER TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");
		$this->addSql("ALTER TABLE typo3_flow_resource_resource RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");
		$this->addSql("ALTER TABLE typo3_flow_security_account RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");
		$this->addSql("ALTER TABLE typo3_flow_security_authorization_resource_securitypublis_861cb RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");

		// add back FK constraints
		foreach ($foreignKeyHandlingSql['add'] as $sql) {
			$this->addSql($sql);
		}

		// rename tables
		$this->addSql("ALTER TABLE typo3_flow_mvc_routing_objectpathmapping RENAME TO typo3_flow3_mvc_routing_objectpathmapping");
		$this->addSql("ALTER TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration RENAME TO typo3_flow3_resource_publishing_abstractpublishingconfiguration");
		$this->addSql("ALTER TABLE typo3_flow_resource_resource RENAME TO typo3_flow3_resource_resource");
		$this->addSql("ALTER TABLE typo3_flow_resource_resourcepointer RENAME TO typo3_flow3_resource_resourcepointer");
		$this->addSql("ALTER TABLE typo3_flow_security_account RENAME TO typo3_flow3_security_account");
		$this->addSql("ALTER TABLE typo3_flow_security_authorization_resource_securitypublis_861cb RENAME TO typo3_flow3_security_authorization_resource_securitypubli_6180a");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role RENAME TO typo3_flow3_security_policy_role");
	}

}
