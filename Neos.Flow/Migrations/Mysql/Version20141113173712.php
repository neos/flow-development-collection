<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Remove table and relations for Role entity, instead role identifiers are now stored as simple comma separated list in the account table
 */
class Version20141113173712 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        // skip execution of corresponding sql queries if migration has been applied already (see https://review.typo3.org/36299)
        $this->skipIf(array_key_exists('roleidentifiers', $this->sm->listTableColumns('typo3_flow_security_account')), 'Migration not needed, already applied earlier.');

        $this->addSql("ALTER TABLE typo3_flow_security_account ADD roleidentifiers LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)'");
        $this->addSql("ALTER TABLE typo3_flow_security_account_roles_join DROP FOREIGN KEY FK_ADF11BBC23A1047C");

        $this->migrateAccountRolesUp();

        $this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join DROP FOREIGN KEY FK_D459C58E6A8ABCDE");
        $this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join DROP FOREIGN KEY FK_D459C58E23A1047C");
        $this->addSql("DROP TABLE typo3_flow_security_account_roles_join");
        $this->addSql("DROP TABLE typo3_flow_security_authorization_resource_securitypublis_861cb");
        $this->addSql("DROP TABLE typo3_flow_security_policy_role");
        $this->addSql("DROP TABLE typo3_flow_security_policy_role_parentroles_join");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_flow_security_account_roles_join (flow_security_account VARCHAR(40) NOT NULL, flow_policy_role VARCHAR(255) NOT NULL, INDEX IDX_ADF11BBC58842EFC (flow_security_account), INDEX IDX_ADF11BBC23A1047C (flow_policy_role), PRIMARY KEY(flow_security_account, flow_policy_role)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_flow_security_authorization_resource_securitypublis_861cb (persistence_object_identifier VARCHAR(40) NOT NULL, allowedroles LONGTEXT NOT NULL COMMENT '(DC2Type:array)', PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_flow_security_policy_role (identifier VARCHAR(255) NOT NULL, sourcehint VARCHAR(6) NOT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_flow_security_policy_role_parentroles_join (flow_policy_role VARCHAR(255) NOT NULL, parent_role VARCHAR(255) NOT NULL, INDEX IDX_D459C58E23A1047C (flow_policy_role), INDEX IDX_D459C58E6A8ABCDE (parent_role), PRIMARY KEY(flow_policy_role, parent_role)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

        $this->migrateAccountRolesDown();

        $this->addSql("ALTER TABLE typo3_flow_security_account_roles_join ADD CONSTRAINT FK_ADF11BBC58842EFC FOREIGN KEY (flow_security_account) REFERENCES typo3_flow_security_account (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_flow_security_account_roles_join ADD CONSTRAINT FK_ADF11BBC23A1047C FOREIGN KEY (flow_policy_role) REFERENCES typo3_flow_security_policy_role (identifier)");

        $this->addSql("ALTER TABLE typo3_flow_security_authorization_resource_securitypublis_861cb ADD CONSTRAINT FK_234846D521E3D446 FOREIGN KEY (persistence_object_identifier) REFERENCES typo3_flow_resource_publishing_abstractpublishingconfiguration (persistence_object_identifier) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join ADD CONSTRAINT FK_D459C58E6A8ABCDE FOREIGN KEY (parent_role) REFERENCES typo3_flow_security_policy_role (identifier)");
        $this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join ADD CONSTRAINT FK_D459C58E23A1047C FOREIGN KEY (flow_policy_role) REFERENCES typo3_flow_security_policy_role (identifier)");
        $this->addSql("ALTER TABLE typo3_flow_security_account DROP roleidentifiers");
    }

    /**
     * Generate SQL statements to migrate accounts up to embedded roles.
     *
     * @return void
     */
    protected function migrateAccountRolesUp()
    {
        $accountsWithRoles = array();

        $accountRolesResult = $this->connection->executeQuery('SELECT j.flow_security_account, r.identifier FROM typo3_flow_security_account_roles_join as j LEFT JOIN typo3_flow_security_policy_role AS r ON j.flow_policy_role = r.identifier');
        while ($accountIdentifierAndRole = $accountRolesResult->fetch(\PDO::FETCH_ASSOC)) {
            $accountIdentifier = $accountIdentifierAndRole['flow_security_account'];
            $accountsWithRoles[$accountIdentifier][] = $accountIdentifierAndRole['identifier'];
        }

        foreach ($accountsWithRoles as $accountIdentifier => $roles) {
            $this->addSql("UPDATE typo3_flow_security_account SET roleidentifiers = " . $this->connection->quote(implode(',', $roles)) . " WHERE persistence_object_identifier = " . $this->connection->quote($accountIdentifier));
        }
    }

    /**
     * Generate SQL statements to migrate accounts down to embedded roles.
     *
     * @return void
     */
    protected function migrateAccountRolesDown()
    {
        $allRolesAndAccounts = array();
        $accountRolesResult = $this->connection->executeQuery('SELECT persistence_object_identifier, roleidentifiers FROM typo3_flow_security_account');
        while ($accountIdentifierAndRoles = $accountRolesResult->fetch(\PDO::FETCH_ASSOC)) {
            $accountIdentifier = $accountIdentifierAndRoles['persistence_object_identifier'];
            $roleIdentifiers = explode(',', $accountIdentifierAndRoles['roleidentifiers']);
            foreach ($roleIdentifiers as $roleIdentifier) {
                if (!isset($allRolesAndAccounts[$roleIdentifier])) {
                    $allRolesAndAccounts[$roleIdentifier] = array();
                }
                $allRolesAndAccounts[$roleIdentifier][] = $accountIdentifier;
            }
        }
        $this->addSql("INSERT INTO typo3_flow_security_policy_role (identifier, sourcehint) VALUES ('Everybody', 'system')");
        $this->addSql("INSERT INTO typo3_flow_security_policy_role (identifier, sourcehint) VALUES ('Anonymous', 'system')");
        $this->addSql("INSERT INTO typo3_flow_security_policy_role (identifier, sourcehint) VALUES ('AuthenticatedUser', 'system')");
        foreach ($allRolesAndAccounts as $roleIdentifier => $accountIdentifiers) {
            $this->addSql("INSERT INTO typo3_flow_security_policy_role (identifier, sourcehint) VALUES (" . $this->connection->quote($roleIdentifier) . ", 'policy')");
            foreach ($accountIdentifiers as $accountIdentifier) {
                $this->addSql("INSERT INTO typo3_flow_security_account_roles_join (flow_security_account, flow_policy_role) VALUES (" . $this->connection->quote($accountIdentifier) . ", " . $this->connection->quote($roleIdentifier) . ")");
            }
        }
    }
}
