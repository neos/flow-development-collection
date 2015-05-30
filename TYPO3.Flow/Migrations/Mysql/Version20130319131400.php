<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use TYPO3\Flow\Utility\Files;

/**
 * Adjust tables for Role handling
 */
class Version20130319131400 extends AbstractMigration {

	/**
	 * @\TYPO3\Flow\Annotations\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("CREATE TABLE typo3_flow_security_account_roles_join (flow_security_account VARCHAR(40) NOT NULL, flow_policy_role VARCHAR(255) NOT NULL, INDEX IDX_ADF11BBC58842EFC (flow_security_account), INDEX IDX_ADF11BBC23A1047C (flow_policy_role), PRIMARY KEY(flow_security_account, flow_policy_role)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_flow_security_policy_role_parentroles_join (flow_policy_role VARCHAR(255) NOT NULL, parent_role VARCHAR(255) NOT NULL, INDEX IDX_D459C58E23A1047C (flow_policy_role), INDEX IDX_D459C58E6A8ABCDE (parent_role), PRIMARY KEY (flow_policy_role, parent_role)) ENGINE = InnoDB");

		$this->addSql("ALTER TABLE typo3_flow_security_account_roles_join ADD CONSTRAINT FK_ADF11BBC58842EFC FOREIGN KEY (flow_security_account) REFERENCES typo3_flow_security_account (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_flow_security_account_roles_join ADD CONSTRAINT FK_ADF11BBC23A1047C FOREIGN KEY (flow_policy_role) REFERENCES typo3_flow_security_policy_role (identifier)");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join ADD CONSTRAINT FK_D459C58E23A1047C FOREIGN KEY (flow_policy_role) REFERENCES typo3_flow_security_policy_role (identifier)");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role_parentroles_join ADD CONSTRAINT FK_D459C58E6A8ABCDE FOREIGN KEY (parent_role) REFERENCES typo3_flow_security_policy_role (identifier)");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role ADD sourcehint VARCHAR(6) NOT NULL");

		$this->migrateAccountRolesUp();

		$this->addSql("ALTER TABLE typo3_flow_security_account DROP roles");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("DROP TABLE typo3_flow_security_account_roles_join");
		$this->addSql("DROP TABLE typo3_flow_security_policy_role_parentroles_join");
		$this->addSql("ALTER TABLE typo3_flow_security_policy_role DROP sourcehint");
		$this->addSql("ALTER TABLE typo3_flow_security_account ADD roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");

		$this->migrateAccountRolesDown();

		$this->addSql("TRUNCATE TABLE typo3_flow_security_policy_role");
	}

	/**
	 * Generate SQL statements to migrate accounts up to referenced roles.
	 *
	 * @return void
	 */
	protected function migrateAccountRolesUp() {
		$rolesSql = array();
		$accountRolesSql = array();
		$rolesToMigrate = array();

		$accountsResult = $this->connection->executeQuery('SELECT DISTINCT(roles) FROM typo3_flow_security_account');
		while ($accountIdentifierAndRoles = $accountsResult->fetch(\PDO::FETCH_ASSOC)) {
			$roleIdentifiers = unserialize($accountIdentifierAndRoles['roles']);
			foreach ($roleIdentifiers as $roleIdentifier) {
				$rolesToMigrate[$roleIdentifier] = TRUE;
			}
		}

		$roleIdentifierMap = $this->getRoleIdentifierMap($rolesToMigrate);

		$accountsResult = $this->connection->executeQuery('SELECT persistence_object_identifier, roles FROM typo3_flow_security_account');
		while ($accountIdentifierAndRoles = $accountsResult->fetch(\PDO::FETCH_ASSOC)) {
			$accountIdentifier = $accountIdentifierAndRoles['persistence_object_identifier'];
			$roleIdentifiers = unserialize($accountIdentifierAndRoles['roles']);
			foreach ($roleIdentifiers as $roleIdentifier) {
				$roleIdentifier = $roleIdentifierMap[$roleIdentifier];
				$rolesSql[$roleIdentifier] = "INSERT INTO typo3_flow_security_policy_role (identifier, sourcehint) VALUES (" . $this->connection->quote($roleIdentifier) . ", 'policy')";
				$accountRolesSql[] = "INSERT INTO typo3_flow_security_account_roles_join (flow_security_account, flow_policy_role) VALUES (" . $this->connection->quote($accountIdentifier) . ", " . $this->connection->quote($roleIdentifier) . ")";
			}
		}

		foreach ($rolesSql as $sql) {
			$this->addSql($sql);
		}
		foreach ($accountRolesSql as $sql) {
			$this->addSql($sql);
		}
	}

	/**
	 * Generate SQL statements to migrate accounts down to embedded roles.
	 *
	 * @return void
	 */
	protected function migrateAccountRolesDown() {
		$accountsWithRoles = array();

		$accountRolesResult = $this->connection->executeQuery('SELECT j.flow_security_account, r.identifier FROM typo3_flow_security_account_roles_join as j LEFT JOIN typo3_flow_security_policy_role AS r ON j.flow_policy_role = r.identifier');
		while ($accountIdentifierAndRole = $accountRolesResult->fetch(\PDO::FETCH_ASSOC)) {
			$accountIdentifier = $accountIdentifierAndRole['flow_security_account'];
			$roleIdentifier = $accountIdentifierAndRole['identifier'];
			$accountsWithRoles[$accountIdentifier][] = substr($roleIdentifier, strrpos($roleIdentifier, ':') + 1);
		}

		foreach ($accountsWithRoles as $accountIdentifier => $roles) {
			$this->addSql("UPDATE typo3_flow_security_account SET roles = " . $this->connection->quote(serialize($roles)) . " WHERE persistence_object_identifier = " . $this->connection->quote($accountIdentifier));
		}
	}

	/**
	 * Returns the given array indexed by "old" role identifiers with the
	 * "new" identifiers added as values to their matching index.
	 *
	 * @param array $map
	 * @return array
	 */
	protected function getRoleIdentifierMap(array $map) {
		$rolesFromPolicy = $this->loadRolesFromPolicyFiles();

		foreach ($rolesFromPolicy as $newRoleIdentifier) {
			$map[substr($newRoleIdentifier, strrpos($newRoleIdentifier, ':') + 1)] = $newRoleIdentifier;
		}

		$map['Anonymous'] ='Anonymous';
		$map['Everybody'] = 'Everybody';

		return $map;
	}

	/**
	 * Reads all Policy.yaml files below Packages, extracts the roles and prepends
	 * them with the package key "guessed" from the path.
	 *
	 * @return array
	 */
	protected function loadRolesFromPolicyFiles() {
		$roles = array();

		$yamlPathsAndFilenames = Files::readDirectoryRecursively(__DIR__ . '/../../../../../Packages', 'yaml', TRUE);
		$configurationPathsAndFilenames = array_filter($yamlPathsAndFilenames,
			function ($pathAndFileName) {
				if (basename($pathAndFileName) === 'Policy.yaml') {
					return TRUE;
				} else {
					return FALSE;
				}
			}
		);

		$yamlSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		foreach ($configurationPathsAndFilenames as $pathAndFilename) {
			if (preg_match('%Packages/.+/([^/]+)/Configuration/(?:Development|Production|Policy).+%', $pathAndFilename, $matches) === 0) {
				continue;
			};
			$packageKey = $matches[1];
			$configuration = $yamlSource->load(substr($pathAndFilename, 0, -5));
			if (isset($configuration['roles']) && is_array($configuration['roles'])) {
				foreach ($configuration['roles'] as $roleIdentifier => $parentRoles) {
					$roles[$packageKey . ':' . $roleIdentifier] = TRUE;
				}
			}
		}

		return array_keys($roles);
	}

}
