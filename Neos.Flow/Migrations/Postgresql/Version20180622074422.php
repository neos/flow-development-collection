<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180622074422 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add index to "sha1" column of the "resource" table for better read performance';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        if (array_key_exists('typo3_flow_resource_resource', $this->sm->listTableNames()) !== false
            && array_key_exists(strtolower('IDX_35DC14F03332102A'), $this->sm->listTableIndexes('typo3_flow_resource_resource')) === false) {
            $this->addSql('CREATE INDEX IDX_35DC14F03332102A ON typo3_flow_resource_resource (sha1)');
        } elseif (array_key_exists(strtolower('IDX_35DC14F03332102A'), $this->sm->listTableIndexes('neos_flow_resourcemanagement_persistentresource')) === false) {
            $this->addSql('CREATE INDEX IDX_35DC14F03332102A ON neos_flow_resourcemanagement_persistentresource (sha1)');
        }
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP INDEX IDX_35DC14F03332102A');
    }
}
