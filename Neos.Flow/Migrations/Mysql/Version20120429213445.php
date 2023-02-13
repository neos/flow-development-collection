<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create unique indexes for identity properties
 */
class Version20120429213445 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_flow3_security_account ON typo3_flow3_security_account (accountidentifier, authenticationprovidername)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP INDEX flow3_identity_typo3_flow3_security_account ON typo3_flow3_security_account");
    }
}
