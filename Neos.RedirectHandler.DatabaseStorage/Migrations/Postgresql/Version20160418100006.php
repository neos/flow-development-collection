<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Initial migration for the "Redirect" entity
 */
class Version20160418100006 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('CREATE TABLE neos_redirecthandler_databasestorage_domain_model_redirect (persistence_object_identifier VARCHAR(40) NOT NULL, creationdatetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lastmodificationdatetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, version INT DEFAULT 1 NOT NULL, sourceuripath VARCHAR(4000) NOT NULL, sourceuripathhash VARCHAR(32) NOT NULL, targeturipath VARCHAR(500) NOT NULL, targeturipathhash VARCHAR(32) NOT NULL, statuscode INT NOT NULL, host VARCHAR(255) DEFAULT NULL, hitcounter INT NOT NULL, lasthit TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(persistence_object_identifier))');
        $this->addSql('CREATE INDEX sourceuripathhash ON neos_redirecthandler_databasestorage_domain_model_redirect (sourceuripathhash, host)');
        $this->addSql('CREATE INDEX targeturipathhash ON neos_redirecthandler_databasestorage_domain_model_redirect (targeturipathhash, host)');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_redirecthandler_databasestorage_domain_60892 ON neos_redirecthandler_databasestorage_domain_model_redirect (sourceuripathhash, host)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP TABLE neos_redirecthandler_databasestorage_domain_model_redirect');
    }
}
