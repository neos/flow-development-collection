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

        $this->addSql("CREATE TABLE typo3_flow_http_redirection_redirection (persistence_object_identifier VARCHAR(40) NOT NULL, sourceuripath VARCHAR(500) NOT NULL, targeturipath VARCHAR(500) NOT NULL, statuscode INT NOT NULL, UNIQUE INDEX flow_identity_typo3_flow_http_redirection_redirection (sourceuripath), PRIMARY KEY(persistence_object_identifier)) ENGINE = InnoDB");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE typo3_flow_http_redirection_redirection");
    }
}
