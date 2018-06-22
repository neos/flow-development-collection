<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180622080718 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Add index to "sha1" column of the "persistentresource" table for better read performance';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE INDEX IDX_6954B1F63332102A ON neos_flow_resourcemanagement_persistentresource (sha1)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX IDX_6954B1F63332102A ON neos_flow_resourcemanagement_persistentresource');
    }
}
