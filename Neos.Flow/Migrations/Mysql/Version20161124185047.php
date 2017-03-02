<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.Flow to Neos.Flow.
 */
class Version20161124185047 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE typo3_flow_mvc_routing_objectpathmapping TO neos_flow_mvc_routing_objectpathmapping');
        $this->addSql('RENAME TABLE typo3_flow_resourcemanagement_persistentresource TO neos_flow_resourcemanagement_persistentresource');
        $this->addSql('RENAME TABLE typo3_flow_security_account TO neos_flow_security_account');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE neos_flow_mvc_routing_objectpathmapping TO typo3_flow_mvc_routing_objectpathmapping');
        $this->addSql('RENAME TABLE neos_flow_resourcemanagement_persistentresource TO typo3_flow_resourcemanagement_persistentresource');
        $this->addSql('RENAME TABLE neos_flow_security_account TO typo3_flow_security_account');
    }
}
