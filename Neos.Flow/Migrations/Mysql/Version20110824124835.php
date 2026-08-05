<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename FLOW3 tables to follow FQCN
 */
class Version20110824124835 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof MySQLPlatform));

        // Explicitly normalize the FK constraint name after the table rename.
        // Old MariaDB auto-renamed it (typo3_flow3_resource_resource_ibfk_1 already set); MariaDB 12+ does not (flow3_resource_resource_ibfk_1 still present).
        // Drop before rename and re-add after
        foreach ($this->sm->listTableForeignKeys('flow3_resource_resource') as $foreignKey) {
            if (in_array('flow3_resource_resourcepointer', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE flow3_resource_resource DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }

        $this->addSql("RENAME TABLE flow3_policy_role TO typo3_flow3_security_policy_role");
        $this->addSql("RENAME TABLE flow3_resource_resource TO typo3_flow3_resource_resource");
        $this->addSql("RENAME TABLE flow3_resource_resourcepointer TO typo3_flow3_resource_resourcepointer");
        $this->addSql("RENAME TABLE flow3_resource_securitypublishingconfiguration TO typo3_flow3_security_authorization_resource_securitypublis_6180a");
        $this->addSql("RENAME TABLE flow3_security_account TO typo3_flow3_security_account");

        $this->addSql("ALTER TABLE typo3_flow3_resource_resource ADD CONSTRAINT typo3_flow3_resource_resource_ibfk_1 FOREIGN KEY (flow3_resource_resourcepointer) REFERENCES typo3_flow3_resource_resourcepointer (hash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof MySQLPlatform));

        // Explicitly normalize the FK constraint name after the table rename back.
        foreach ($this->sm->listTableForeignKeys('typo3_flow3_resource_resource') as $foreignKey) {
            if (in_array('flow3_resource_resourcepointer', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_flow3_resource_resource DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }

        $this->addSql("RENAME TABLE typo3_flow3_security_policy_role TO flow3_policy_role");
        $this->addSql("RENAME TABLE typo3_flow3_resource_resource TO flow3_resource_resource");
        $this->addSql("RENAME TABLE typo3_flow3_resource_resourcepointer TO flow3_resource_resourcepointer");
        $this->addSql("RENAME TABLE typo3_flow3_security_authorization_resource_securitypublis_6180a TO flow3_resource_securitypublishingconfiguration");
        $this->addSql("RENAME TABLE typo3_flow3_security_account TO flow3_security_account");

        $this->addSql("ALTER TABLE flow3_resource_resource ADD CONSTRAINT flow3_resource_resource_ibfk_1 FOREIGN KEY (flow3_resource_resourcepointer) REFERENCES flow3_resource_resourcepointer (hash)");
    }
}
