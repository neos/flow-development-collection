<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * FLOW3 Migration
 */
class Version20110613223837 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE flow3_resource_resourcepointer (hash VARCHAR(255) NOT NULL, PRIMARY KEY(hash)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE flow3_resource_resource (flow3_persistence_identifier VARCHAR(40) NOT NULL, flow3_resource_resourcepointer VARCHAR(255) DEFAULT NULL, filename VARCHAR(255) DEFAULT NULL, fileextension VARCHAR(255) DEFAULT NULL, INDEX IDX_11FFD19FD0275681 (flow3_resource_resourcepointer), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE flow3_security_account (flow3_persistence_identifier VARCHAR(40) NOT NULL, party_abstractparty VARCHAR(40) DEFAULT NULL, accountidentifier VARCHAR(255) DEFAULT NULL, authenticationprovidername VARCHAR(255) DEFAULT NULL, credentialssource VARCHAR(255) DEFAULT NULL, creationdate DATETIME DEFAULT NULL, expirationdate DATETIME DEFAULT NULL, roles LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', INDEX IDX_44D0753B38110E12 (party_abstractparty), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE flow3_resource_securitypublishingconfiguration (flow3_persistence_identifier VARCHAR(40) NOT NULL, allowedroles LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE flow3_policy_role (identifier VARCHAR(255) NOT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE flow3_resource_resource ADD CONSTRAINT flow3_resource_resource_ibfk_1 FOREIGN KEY (flow3_resource_resourcepointer) REFERENCES flow3_resource_resourcepointer(hash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE flow3_resource_resource DROP FOREIGN KEY flow3_resource_resource_ibfk_1");
        $this->addSql("DROP TABLE flow3_resource_resourcepointer");
        $this->addSql("DROP TABLE flow3_resource_resource");
        $this->addSql("DROP TABLE flow3_security_account");
        $this->addSql("DROP TABLE flow3_resource_securitypublishingconfiguration");
        $this->addSql("DROP TABLE flow3_policy_role");
    }
}
