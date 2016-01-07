<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Initial migration for the "Redirection" entity
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

        $this->addSql("CREATE TABLE neos_redirecthandler_databasestorage_domain_model_redirection (persistence_object_identifier VARCHAR(40) NOT NULL, sourceuripath VARCHAR(4000) NOT NULL, sourceuripathhash VARCHAR(32) NOT NULL, targeturipath VARCHAR(500) NOT NULL, targeturipathhash VARCHAR(32) NOT NULL, statuscode INT NOT NULL, INDEX targeturipathhash (targeturipathhash), UNIQUE INDEX flow_identity_neos_redirecthandler_databasestorage_domain_c66fa (sourceuripathhash), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE neos_redirecthandler_databasestorage_domain_model_redirection");
    }
}
