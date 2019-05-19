<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbstractMigration;

/**
 * Add additional index for neos_flow_mvc_routing_objectpathmapping query speedup
 */
class Version20180827132710 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Add additional index for neos_flow_mvc_routing_objectpathmapping query speedup';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE INDEX IDX_535A651E772E836ADCCB5599802C8F9D ON neos_flow_mvc_routing_objectpathmapping (identifier, uripattern, pathsegment)');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX IDX_535A651E772E836ADCCB5599802C8F9D ON neos_flow_mvc_routing_objectpathmapping');
     }
}
