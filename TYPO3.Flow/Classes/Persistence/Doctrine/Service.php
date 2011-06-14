<?php
namespace F3\FLOW3\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Service class for tasks related to Doctrine
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class Service {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @inject
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Injects the FLOW3 settings, the persistence part is kept
	 * for further use.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings['persistence'];
	}

	/**
	 * Validates the metadata mapping for Doctrine, using the SchemaValidator
	 * of Doctrine.
	 *
	 * @return array
	 */
	public function validateMapping() {
		try {
			$result = array();
			$validator = new \Doctrine\ORM\Tools\SchemaValidator($this->entityManager);
			$result = $validator->validateMapping();
		} catch (\Exception $exception) {}
		return $result;
	}

	/**
	 * Creates the needed DB schema using Doctrine's SchemaTool. If tables already
	 * exist, this will thow an exception.
	 *
	 * @return void
	 */
	public function createSchema() {
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
	}

	/**
	 * Updates the DB schema using Doctrine's SchemaTool. The $safeMode flag is passed
	 * to SchemaTool unchanged.
	 *
	 * @param boolean $safeMode
	 * @return void
	 */
	public function updateSchema($safeMode = TRUE) {
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata(), $safeMode);
	}

	/**
	 * Compiles the Doctrine proxy class code using the Doctrine ProxyFactory.
	 *
	 * @return void
	 */
	public function compileProxies() {
		$proxyFactory = $this->entityManager->getProxyFactory();
		$proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());
	}

	/**
	 * Returns basic information about which entities exist and possibly if their
	 * mapping information contains errors or not.
	 *
	 * @return array
	 */
	public function getInfo() {
		$entityClassNames = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		$info = array();
		foreach ($entityClassNames as $entityClassName) {
			try {
				$this->entityManager->getClassMetadata($entityClassName);
				$info[$entityClassName] = TRUE;
			} catch (\Doctrine\ORM\Mapping\MappingException $e) {
				$info[$entityClassName] = $e->getMessage();
			}
		}

		return $info;
	}

	/**
	 * Run DQL and return the result as-is.
	 *
	 * @param string $dql
	 * @param integer $hydrationMode
	 * @param integer $firstResult
	 * @param integer $maxResult
	 * @return mixed
	 */
	public function runDql($dql, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT, $firstResult = NULL, $maxResult = NULL) {
		$query = $this->entityManager->createQuery($dql);
		if ($firstResult !== NULL){
			$query->setFirstResult($firstResult);
		}
		if ($maxResult !== NULL) {
			$query->setMaxResults($maxResult);
		}

		return $query->execute(array(), constant($hydrationMode));
	}

	/**
	 * Return the configuration needed for Migrations.
	 *
	 * @return \Doctrine\DBAL\Migrations\Configuration\Configuration
	 */
	protected function getMigrationConfiguration() {
		$configuration = new \Doctrine\DBAL\Migrations\Configuration\Configuration($this->entityManager->getConnection());
		$configuration->setMigrationsNamespace('F3\FLOW3\Persistence\Doctrine\Migrations');
		$configuration->setMigrationsDirectory(\F3\FLOW3\Utility\Files::concatenatePaths(array(FLOW3_PATH_CONFIGURATION, 'Doctrine/Migrations')));
		$configuration->setMigrationsTableName('flow3_doctrine_migrationstatus');
		$configuration->createMigrationTable();

		$databasePlatformName = ucfirst($this->entityManager->getConnection()->getDatabasePlatform()->getName());
		foreach ($this->packageManager->getActivePackages() as $package) {
			$configuration->registerMigrationsFromDirectory(
				\F3\FLOW3\Utility\Files::concatenatePaths(array(
					 $package->getPackagePath(),
					 'Migrations',
					 $databasePlatformName
				))
			);
		}

		return $configuration;
	}

	/**
	 * Returns the current migration status formatted as plain text.
	 *
	 * @return string
	 */
	public function getMigrationStatus() {
		$configuration = $this->getMigrationConfiguration();

		$currentVersion = $configuration->getCurrentVersion();
		if ($currentVersion) {
			$currentVersionFormatted = $configuration->formatVersion($currentVersion) . ' ('.$currentVersion.')';
		} else {
			$currentVersionFormatted = 0;
		}
		$latestVersion = $configuration->getLatestVersion();
		if ($latestVersion) {
			$latestVersionFormatted = $configuration->formatVersion($latestVersion) . ' ('.$latestVersion.')';
		} else {
			$latestVersionFormatted = 0;
		}
		$executedMigrations = $configuration->getNumberOfExecutedMigrations();
		$availableMigrations = $configuration->getNumberOfAvailableMigrations();
		$newMigrations = $availableMigrations - $executedMigrations;

		$output = "\n == Configuration\n";

		$info = array(
			'Name'                  => $configuration->getName() ? $configuration->getName() : 'Doctrine Database Migrations',
			'Database Driver'       => $configuration->getConnection()->getDriver()->getName(),
			'Database Name'         => $configuration->getConnection()->getDatabase(),
			'Configuration Source'  => $configuration instanceof \Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration ? $configuration->getFile() : 'manually configured',
			'Version Table Name'    => $configuration->getMigrationsTableName(),
			'Migrations Namespace'  => $configuration->getMigrationsNamespace(),
			'Migrations Directory'  => $configuration->getMigrationsDirectory(),
			'Current Version'       => $currentVersionFormatted,
			'Latest Version'        => $latestVersionFormatted,
			'Executed Migrations'   => $executedMigrations,
			'Available Migrations'  => $availableMigrations,
			'New Migrations'        => $newMigrations
		);
		foreach ($info as $name => $value) {
			$output .= '    >> ' . $name . ': ' . str_repeat(' ', 50 - strlen($name)) . $value . "\n";
		}

		if ($migrations = $configuration->getMigrations()) {
			$output .= "\n == Migration Versions\n";
			foreach ($migrations as $version) {
				$status = $version->isMigrated() ? 'migrated' : "not migrated\n";
				$output .= '    >> ' . $configuration->formatVersion($version->getVersion()) . ' (' . $version->getVersion() . ')' . str_repeat(' ', 30 - strlen($name)) . $status . "\n";
			}
		}

		return $output;
	}

	/**
	 * Generates an empty migration file and returns the path to it.
	 *
	 * @return string
	 */
	public function generateEmptyMigration() {
		$configuration = $this->getMigrationConfiguration();

		$version = date('YmdHis');
		$path = $this->generateMigration($configuration, $version);

		return sprintf('Generated empty migration class to "%s".', $path);
	}

	/**
	 * Generates a migration file with the diff between current DB structure
	 * and the found mapping metadata. The path to the new file is returned.
	 *
	 * @return string
	 */
	public function generateDiffMigration() {
		$configuration = $this->getMigrationConfiguration();

		$connection = $this->entityManager->getConnection();
		$platform = $connection->getDatabasePlatform();
		$metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

		if (empty($metadata)) {
			return 'No mapping information to process.';
		}

		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);

		$fromSchema = $connection->getSchemaManager()->createSchema();
		$toSchema = $tool->getSchemaFromMetadata($metadata);
		$up = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateToSql($toSchema, $platform));
		$down = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateFromSql($toSchema, $platform));

		if (!$up && !$down) {
			return 'No changes detected in your mapping information.';
		}

		$version = date('YmdHis');
		$path = $this->generateMigration($configuration, $version, $up, $down);

		return sprintf('Generated new migration class to "%s" from schema differences.', $path);
	}

	/**
	 * Returns PHP code for a migration file that "executes" the given
	 * array of SQL statements.
	 *
	 * @param \Doctrine\DBAL\Migrations\Configuration\Configuration $configuration
	 * @param array $sql
	 * @return string
	 */
	protected function buildCodeFromSql(\Doctrine\DBAL\Migrations\Configuration\Configuration $configuration, array $sql) {
		$currentPlatform = $configuration->getConnection()->getDatabasePlatform()->getName();
		$code = array(
			"\$this->abortIf(\$this->connection->getDatabasePlatform()->getName() != \"$currentPlatform\");", "",
		);
		foreach ($sql as $query) {
			if (strpos($query, $configuration->getMigrationsTableName()) !== FALSE) {
				continue;
			}
			$code[] = "\$this->addSql(\"$query\");";
		}
		return implode("\n", $code);
	}

	/**
	 * Execute a single migration in up or down direction. If $path is given, the
	 * SQL statements will be writte to the file in $path instead of executed.
	 *
	 * @param  $version
	 * @param string $direction
	 * @param null $path
	 * @param bool $dryRun
	 * @return void
	 */
	public function executeMigration($version, $direction = 'up', $path = NULL, $dryRun = FALSE) {
		$configuration = $this->getMigrationConfiguration();
		$version = $configuration->getVersion($version);

		if ($path !== NULL) {
			$version->writeSqlFile($path, $direction);
		} else {
			$version->execute($direction, $dryRun);
		}
	}

	/**
	 * Execute all new migrations, up to $version if given. If $path is given, the
	 * SQL statements will be writte to the file in $path instead of executed.
	 *
	 * @param null $version
	 * @param null $path
	 * @param bool $dryRun
	 * @return void
	 */
	public function executeMigrations($version = NULL, $path = NULL, $dryRun = FALSE) {
		$configuration = $this->getMigrationConfiguration();
		$migration = new \Doctrine\DBAL\Migrations\Migration($configuration);

		if ($path !== NULL) {
			$migration->writeSqlFile($path, $version);
		} else {
			$migration->migrate($version, $dryRun);
		}
	}

	/**
	 * Writes a migration file with $up and $down code and the given $version
	 * to the configured migrations directory.
	 *
	 * @param \Doctrine\DBAL\Migrations\Configuration\Configuration $configuration
	 * @param string $version
	 * @param string $up
	 * @param string $down
	 * @return string
	 * @throws \RuntimeException
	 */
	protected function generateMigration(\Doctrine\DBAL\Migrations\Configuration\Configuration $configuration, $version, $up = NULL, $down = NULL) {
		$namespace = $configuration->getMigrationsNamespace();
		$className = 'Version' . $version;
		$up = $up === NULL ? '' : "\n		" . implode("\n		", explode("\n", $up));
		$down = $down === NULL ? '' : "\n		" . implode("\n		", explode("\n", $down));

		$path = \F3\FLOW3\Utility\Files::concatenatePaths(array($configuration->getMigrationsDirectory(), 'Version' . $version . '.php'));
		try {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($path));
		} catch (\F3\FLOW3\Utility\Exception $exception) {
			throw new \RuntimeException(sprintf('Migrations directory "%s" does not exist.', dirname($path)), 1303298536, $exception);
		}

		$code = <<<EOT
<?php
namespace $namespace;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class $className extends AbstractMigration {

	/**
	 * @param Schema \$schema
	 * @return void
	 */
	public function up(Schema \$schema) {
			// this up() migration is autogenerated, please modify it to your needs$up
	}

	/**
	 * @param Schema \$schema
	 * @return void
	 */
	public function down(Schema \$schema) {
			// this down() migration is autogenerated, please modify it to your needs$down
	}
}

?>
EOT;
		file_put_contents($path, $code);

		return $path;
	 }

}

?>