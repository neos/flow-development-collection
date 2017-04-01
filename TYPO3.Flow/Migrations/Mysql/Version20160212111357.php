<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust some (old) index names to current Doctrine DBAL behavior (see https://jira.neos.io/browse/FLOW-427)
 */
class Version20160212111357 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_flow_resource_resource DROP FOREIGN KEY FK_B4D45B32A4A851AF");
        $this->addSql("ALTER TABLE typo3_flow_resource_resource DROP FOREIGN KEY typo3_flow_resource_resource_ibfk_1");
        $this->addSql("DROP INDEX idx_b4d45b323cb65d1 ON typo3_flow_resource_resource");
        $this->addSql("CREATE INDEX IDX_35DC14F03CB65D1 ON typo3_flow_resource_resource (resourcepointer)");
        $this->addSql("DROP INDEX idx_b4d45b32a4a851af ON typo3_flow_resource_resource");
        $this->addSql("CREATE INDEX IDX_35DC14F0A4A851AF ON typo3_flow_resource_resource (publishingconfiguration)");
        $this->addSql("ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT FK_B4D45B32A4A851AF FOREIGN KEY (publishingconfiguration) REFERENCES typo3_flow_resource_publishing_abstractpublishingconfiguration (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT typo3_flow_resource_resource_ibfk_1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow_resource_resourcepointer (hash)");

        $this->addSql("ALTER TABLE typo3_flow_security_account DROP FOREIGN KEY typo3_flow_security_account_ibfk_1");
        $this->addSql("DROP INDEX idx_65efb31c89954ee0 ON typo3_flow_security_account");
        $this->addSql("CREATE INDEX IDX_D3B6BCC889954EE0 ON typo3_flow_security_account (party)");
        $this->addSql("DROP INDEX flow3_identity_typo3_flow3_security_account ON typo3_flow_security_account");
        $this->addSql("CREATE UNIQUE INDEX flow_identity_typo3_flow_security_account ON typo3_flow_security_account (accountidentifier, authenticationprovidername)");
        $this->addSql("ALTER TABLE typo3_flow_security_account ADD CONSTRAINT typo3_flow_security_account_ibfk_1 FOREIGN KEY (party) REFERENCES typo3_party_domain_model_abstractparty (persistence_object_identifier)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_flow_resource_resource DROP FOREIGN KEY typo3_flow_resource_resource_ibfk_1");
        $this->addSql("ALTER TABLE typo3_flow_resource_resource DROP FOREIGN KEY FK_B4D45B32A4A851AF");
        $this->addSql("DROP INDEX idx_35dc14f03cb65d1 ON typo3_flow_resource_resource");
        $this->addSql("CREATE INDEX IDX_B4D45B323CB65D1 ON typo3_flow_resource_resource (resourcepointer)");
        $this->addSql("DROP INDEX idx_35dc14f0a4a851af ON typo3_flow_resource_resource");
        $this->addSql("CREATE INDEX IDX_B4D45B32A4A851AF ON typo3_flow_resource_resource (publishingconfiguration)");
        $this->addSql("ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT typo3_flow_resource_resource_ibfk_1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow_resource_resourcepointer (hash)");
        $this->addSql("ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT FK_B4D45B32A4A851AF FOREIGN KEY (publishingconfiguration) REFERENCES typo3_flow_resource_publishing_abstractpublishingconfiguration (persistence_object_identifier)");

        $this->addSql("ALTER TABLE typo3_flow_security_account DROP FOREIGN KEY typo3_flow_security_account_ibfk_1");
        $this->addSql("DROP INDEX flow_identity_typo3_flow_security_account ON typo3_flow_security_account");
        $this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_flow3_security_account ON typo3_flow_security_account (accountidentifier, authenticationprovidername)");
        $this->addSql("DROP INDEX idx_d3b6bcc889954ee0 ON typo3_flow_security_account");
        $this->addSql("CREATE INDEX IDX_65EFB31C89954EE0 ON typo3_flow_security_account (party)");
        $this->addSql("ALTER TABLE typo3_flow_security_account ADD CONSTRAINT typo3_flow_security_account_ibfk_1 FOREIGN KEY (party) REFERENCES typo3_party_domain_model_abstractparty (persistence_object_identifier)");
    }
}
