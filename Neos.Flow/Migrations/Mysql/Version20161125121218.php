<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename role identifiers in neos_flow_security_account
 */
class Version20161125121218 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'TYPO3.Flow:', 'Neos.Flow:')");
        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'TYPO3.Neos:', 'Neos.Neos:')");
        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'TYPO3.TYPO3CR:', 'Neos.ContentRepository:')");
        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'TYPO3.Setup:', 'Neos.Setup:')");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'Neos.Flow:', 'TYPO3.Flow:')");
        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'Neos.Neos:', 'TYPO3.Neos:')");
        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'Neos.ContentRepository:', 'TYPO3.TYPO3CR:')");
        $this->addSql("UPDATE neos_flow_security_account SET roleidentifiers=REPLACE(roleidentifiers, 'Neos.Setup:', 'TYPO3.Setup:')");
    }
}
