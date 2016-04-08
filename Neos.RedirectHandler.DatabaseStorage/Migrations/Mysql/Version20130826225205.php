<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Initial migration for the "Redirect" entity
 */
class Version20130826225205 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('CREATE TABLE neos_redirecthandler_databasestorage_domain_model_redirect (persistence_object_identifier VARCHAR(40) NOT NULL, creationdatetime DATETIME NOT NULL, lastmodificationdatetime DATETIME NOT NULL, version INT DEFAULT 1 NOT NULL, sourceuripath VARCHAR(4000) NOT NULL, sourceuripathhash VARCHAR(32) NOT NULL, targeturipath VARCHAR(500) NOT NULL, targeturipathhash VARCHAR(32) NOT NULL, statuscode INT NOT NULL, host VARCHAR(255) DEFAULT NULL, hitcounter INT NOT NULL, lasthit DATETIME DEFAULT NULL, INDEX sourceuripathhash (targeturipathhash, host), INDEX targeturipathhash (targeturipathhash, host), UNIQUE INDEX flow_identity_neos_redirecthandler_databasestorage_domain_60892 (sourceuripathhash, host), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('DROP TABLE neos_redirecthandler_databasestorage_domain_model_redirect');
    }
}
