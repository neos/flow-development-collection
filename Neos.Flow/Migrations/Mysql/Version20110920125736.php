<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the needed table for the generic identity routepart handler
 */
class Version20110920125736 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_flow3_mvc_web_routing_objectpathmapping (objecttype VARCHAR(255) NOT NULL, uripattern VARCHAR(255) NOT NULL, pathsegment VARCHAR(255) NOT NULL, identifier VARCHAR(255) DEFAULT NULL, PRIMARY KEY(objecttype, uripattern, pathsegment)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE typo3_flow3_mvc_web_routing_objectpathmapping");
    }
}
