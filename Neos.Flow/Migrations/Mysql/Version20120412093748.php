<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust shortened table names to correct maximum length
 */
class Version20120412093748 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("RENAME TABLE typo3_flow3_security_authorization_resource_securitypublis_6180a TO typo3_flow3_security_authorization_resource_securitypubli_6180a");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("RENAME TABLE typo3_flow3_security_authorization_resource_securitypubli_6180a TO typo3_flow3_security_authorization_resource_securitypublis_6180a");
    }
}
