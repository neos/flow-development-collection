<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170110130149 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Adjust foreign key and index names to the renaming of TYPO3.Flow to Neos.Flow';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_typo3_flow_security_account ON neos_flow_security_account');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_flow_security_account ON neos_flow_security_account (accountidentifier, authenticationprovidername)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_neos_flow_security_account ON neos_flow_security_account');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_flow_security_account ON neos_flow_security_account (accountidentifier, authenticationprovidername)');
    }
}
