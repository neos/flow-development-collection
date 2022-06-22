<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename the table for object-path mapping used in routing
 */
class Version20120328152041 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("RENAME TABLE typo3_flow3_mvc_web_routing_objectpathmapping TO typo3_flow3_mvc_routing_objectpathmapping");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("RENAME TABLE typo3_flow3_mvc_routing_objectpathmapping TO typo3_flow3_mvc_web_routing_objectpathmapping");
    }
}
