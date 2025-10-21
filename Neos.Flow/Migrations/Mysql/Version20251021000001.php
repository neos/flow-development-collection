<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251021000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for Neos.Flow';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $this->addSql('CREATE TABLE neos_flow_mvc_routing_objectpathmapping (objecttype VARCHAR(255) NOT NULL, uripattern VARCHAR(255) NOT NULL, pathsegment VARCHAR(255) NOT NULL, identifier VARCHAR(255) NOT NULL, INDEX IDX_535A651E772E836ADCCB5599802C8F9D (identifier, uripattern, pathsegment), PRIMARY KEY(objecttype, uripattern, pathsegment)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_flow_resourcemanagement_persistentresource (persistence_object_identifier VARCHAR(40) NOT NULL, collectionname VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, filesize NUMERIC(20, 0) NOT NULL, relativepublicationpath VARCHAR(255) NOT NULL, mediatype VARCHAR(100) NOT NULL, sha1 VARCHAR(40) NOT NULL, INDEX IDX_35DC14F03332102A (sha1), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_flow_security_account (persistence_object_identifier VARCHAR(40) NOT NULL, accountidentifier VARCHAR(255) NOT NULL, authenticationprovidername VARCHAR(255) NOT NULL, credentialssource VARCHAR(255) DEFAULT NULL, creationdate DATETIME NOT NULL, expirationdate DATETIME DEFAULT NULL, lastsuccessfulauthenticationdate DATETIME DEFAULT NULL, failedauthenticationcount INT DEFAULT NULL, roleidentifiers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', UNIQUE INDEX flow_identity_neos_flow_security_account (accountidentifier, authenticationprovidername), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $this->addSql('DROP TABLE neos_flow_mvc_routing_objectpathmapping');
        $this->addSql('DROP TABLE neos_flow_resourcemanagement_persistentresource');
        $this->addSql('DROP TABLE neos_flow_security_account');
    }
}
