<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Persistence\Doctrine\Service;

/**
 * Adjust flow3 to flow
 */
class Version20120930203452 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof MySQLPlatform));

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

        // Explicitly normalize the FK constraint name after the table rename.
        // Old MariaDB auto-renamed it (typo3_flow_resource_resource_ibfk_1 already set); MariaDB 12+ does not (typo3_flow3_resource_resource_ibfk_1 still present).
        // Drop before rename and re-add after
        foreach ($this->sm->listTableForeignKeys('typo3_flow3_resource_resource') as $foreignKey) {
            if (in_array('resourcepointer', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_flow3_resource_resource DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }

        // rename tables
        $this->addSql("RENAME TABLE typo3_flow3_mvc_routing_objectpathmapping TO typo3_flow_mvc_routing_objectpathmapping");
        $this->addSql("RENAME TABLE typo3_flow3_resource_publishing_abstractpublishingconfiguration TO typo3_flow_resource_publishing_abstractpublishingconfiguration");
        $this->addSql("RENAME TABLE typo3_flow3_resource_resource TO typo3_flow_resource_resource");
        $this->addSql("RENAME TABLE typo3_flow3_resource_resourcepointer TO typo3_flow_resource_resourcepointer");
        $this->addSql("RENAME TABLE typo3_flow3_security_account TO typo3_flow_security_account");
        $this->addSql("RENAME TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a TO typo3_flow_security_authorization_resource_securitypublis_861cb");
        $this->addSql("RENAME TABLE typo3_flow3_security_policy_role TO typo3_flow_security_policy_role");

        $this->addSql("ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT typo3_flow_resource_resource_ibfk_1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow_resource_resourcepointer (hash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof MySQLPlatform));

        // collect foreign keys pointing to "our" tables
        $tableNames = array(
            'typo3_flow_resource_publishing_abstractpublishingconfiguration',
            'typo3_flow_resource_resource',
            'typo3_flow_security_account',
            'typo3_flow_security_authorization_resource_securitypublis_861cb',
        );
        $foreignKeyHandlingSql = \Neos\Flow\Persistence\Doctrine\Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'persistence_object_identifier', 'flow3_persistence_identifier');

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

        // Explicitly normalize the FK constraint name after the table rename back.
        foreach ($this->sm->listTableForeignKeys('typo3_flow_resource_resource') as $foreignKey) {
            if (in_array('resourcepointer', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_flow_resource_resource DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }

        // rename tables
        $this->addSql("RENAME TABLE typo3_flow_mvc_routing_objectpathmapping TO typo3_flow3_mvc_routing_objectpathmapping");
        $this->addSql("RENAME TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration TO typo3_flow3_resource_publishing_abstractpublishingconfiguration");
        $this->addSql("RENAME TABLE typo3_flow_resource_resource TO typo3_flow3_resource_resource");
        $this->addSql("RENAME TABLE typo3_flow_resource_resourcepointer TO typo3_flow3_resource_resourcepointer");
        $this->addSql("RENAME TABLE typo3_flow_security_account TO typo3_flow3_security_account");
        $this->addSql("RENAME TABLE typo3_flow_security_authorization_resource_securitypublis_861cb TO typo3_flow3_security_authorization_resource_securitypubli_6180a");
        $this->addSql("RENAME TABLE typo3_flow_security_policy_role TO typo3_flow3_security_policy_role");

        $this->addSql("ALTER TABLE typo3_flow3_resource_resource ADD CONSTRAINT typo3_flow3_resource_resource_ibfk_1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow3_resource_resourcepointer (hash)");
    }
}
