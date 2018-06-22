<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180622074421 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Add index to "sha1" column of the "resource" table for better read performance';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE INDEX IDX_35DC14F03332102A ON typo3_flow_resource_resource (sha1)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX IDX_35DC14F03332102A ON typo3_flow_resource_resource');
    }
}
