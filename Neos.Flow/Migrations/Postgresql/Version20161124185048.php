<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.Flow to Neos.Flow.
 */
class Version20161124185048 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("ALTER TABLE typo3_flow_mvc_routing_objectpathmapping RENAME TO neos_flow_mvc_routing_objectpathmapping");
        $this->addSql("ALTER TABLE typo3_flow_resourcemanagement_persistentresource RENAME TO neos_flow_resourcemanagement_persistentresource");
        $this->addSql("ALTER TABLE typo3_flow_security_account RENAME TO neos_flow_security_account");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("ALTER TABLE neos_flow_mvc_routing_objectpathmapping RENAME TO typo3_flow_mvc_routing_objectpathmapping");
        $this->addSql("ALTER TABLE neos_flow_resourcemanagement_persistentresource RENAME TO typo3_flow_resourcemanagement_persistentresource");
        $this->addSql("ALTER TABLE neos_flow_security_account RENAME TO typo3_flow_security_account");
    }
}
