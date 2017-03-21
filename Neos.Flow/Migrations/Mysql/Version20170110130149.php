<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\MySQL57Platform;
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

        // Renaming of indexes is only possible with MySQL version 5.7+
        if ($this->connection->getDatabasePlatform() instanceof MySQL57Platform) {
            $this->addSql('ALTER TABLE neos_flow_security_account RENAME INDEX flow_identity_typo3_flow_security_account TO flow_identity_neos_flow_security_account');
        } else {
            $this->addSql('DROP INDEX flow_identity_typo3_flow_security_account ON neos_flow_security_account');
            $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_flow_security_account ON neos_flow_security_account (accountidentifier, authenticationprovidername)');
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        // Renaming of indexes is only possible with MySQL version 5.7+
        if ($this->connection->getDatabasePlatform() instanceof MySQL57Platform) {
            $this->addSql('ALTER TABLE neos_flow_security_account RENAME INDEX flow_identity_neos_flow_security_account TO flow_identity_typo3_flow_security_account');
        } else {
            $this->addSql('DROP INDEX flow_identity_neos_flow_security_account ON neos_flow_security_account');
            $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_flow_security_account ON neos_flow_security_account (accountidentifier, authenticationprovidername)');
        }
    }
}
