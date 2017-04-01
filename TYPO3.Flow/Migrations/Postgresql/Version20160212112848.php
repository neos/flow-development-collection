<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust some (old) index names to current Doctrine DBAL behavior (see https://jira.neos.io/browse/FLOW-427)
 */
class Version20160212112848 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        // typo3_flow_resource_resource
        $this->addSql("ALTER INDEX idx_b4d45b323cb65d1 RENAME TO IDX_35DC14F03CB65D1");
        $this->addSql("ALTER INDEX idx_b4d45b32a4a851af RENAME TO IDX_35DC14F0A4A851AF");

        // typo3_flow_security_account
        $this->addSql("ALTER INDEX idx_65efb31c89954ee0 RENAME TO IDX_D3B6BCC889954EE0");
        $this->addSql("ALTER INDEX flow3_identity_typo3_flow3_security_account RENAME TO flow_identity_typo3_flow_security_account");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        // typo3_flow_security_account
        $this->addSql("ALTER INDEX flow_identity_typo3_flow_security_account RENAME TO flow3_identity_typo3_flow3_security_account");
        $this->addSql("ALTER INDEX idx_d3b6bcc889954ee0 RENAME TO idx_65efb31c89954ee0");

        // typo3_flow_resource_resource
        $this->addSql("ALTER INDEX idx_35dc14f03cb65d1 RENAME TO idx_b4d45b323cb65d1");
        $this->addSql("ALTER INDEX idx_35dc14f0a4a851af RENAME TO idx_b4d45b32a4a851af");
    }
}
