<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust DB schema to a clean state (remove cruft that built up in the past)
 */
class Version20150309184456 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_flow_resource_resource ALTER relativepublicationpath DROP DEFAULT");
        $this->addSql("ALTER INDEX IF EXISTS flow3_identity_typo3_flow3_security_account RENAME TO flow_identity_typo3_flow_security_account");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER INDEX IF EXISTS flow_identity_typo3_flow_security_account RENAME TO flow3_identity_typo3_flow3_security_account");
        $this->addSql("ALTER TABLE typo3_flow_resource_resource ALTER relativepublicationpath SET DEFAULT ''");
    }
}
