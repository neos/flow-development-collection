<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\DBAL\Migrations\Version;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Utility\Files;

/**
 * Service class for tasks related to Doctrine
 *
 * @Flow\Scope("singleton")
 */
class Service {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var array
	 */
	public $output = array();

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @return void
	 */
	protected function initializeObject() {
		$connection = $this->entityManager->getConnection();
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('array', 'objectarray');
	}

	/**
	 * Validates the metadata mapping for Doctrine, using the SchemaValidator
	 * of Doctrine.
	 *
	 * @return array
	 */
	public function validateMapping() {
		try {
			$validator = new \Doctrine\ORM\Tools\SchemaValidator($this->entityManager);
			return $validator->validateMapping();
		} catch (\Exception $exception) {
			return array(array($exception->getMessage()));
		}
	}

	/**
	 * Creates the needed DB schema using Doctrine's SchemaTool. If tables already
	 * exist, this will thow an exception.
	 *
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @return string
	 */
	public function createSchema($outputPathAndFilename = NULL) {
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		if ($outputPathAndFilename === NULL) {
			$schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
		} else {
			file_put_contents($outputPathAndFilename, implode(PHP_EOL, $schemaTool->getCreateSchemaSql($this->entityManager->getMetadataFactory()->getAllMetadata())));
		}
	}

	/**
	 * Updates the DB schema using Doctrine's SchemaTool. The $safeMode flag is passed
	 * to SchemaTool unchanged.
	 *
	 * @param boolean $safeMode
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @return string
	 */
	public function updateSchema($safeMode = TRUE, $outputPathAndFilename = NULL) {
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		if ($outputPathAndFilename === NULL) {
			$schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata(), $safeMode);
		} else {
			file_put_contents($outputPathAndFilename, implode(PHP_EOL, $schemaTool->getUpdateSchemaSql($this->entityManager->getMetadataFactory()->getAllMetadata(), $safeMode)));
		}
	}

	/**
	 * Compiles the Doctrine proxy class code using the Doctrine ProxyFactory.
	 *
	 * @return void
	 */
	public function compileProxies() {
		Files::emptyDirectoryRecursively(Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies')));
		$proxyFactory = $this->entityManager->getProxyFactory();
		$proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());
	}

	/**
	 * Returns information about which entities exist and possibly if their
	 * mapping information contains errors or not.
	 *
	 * @return array
	 */
	public function getEntityStatus() {
		$entityClassNames = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		$info = array();
		foreach ($entityClassNames as $entityClassName) {
			try {
				$info[$entityClassName] = $this->entityManager->getClassMetadata($entityClassName);
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
		if ($firstResult !== NULL) {
			$query->setFirstResult($firstResult);
		}
		if ($maxResult !== NULL) {
			$query->setMaxResults($maxResult);
		}

		return $query->execute(array(), $hydrationMode);
	}

	/**
	 * Return the configuration needed for Migrations.
	 *
	 * @return \Doctrine\DBAL\Migrations\Configuration\Configuration
	 */
	protected function getMigrationConfiguration() {
		$this->output = array();
		$that = $this;
		$outputWriter = new \Doctrine\DBAL\Migrations\OutputWriter(
			function ($message) use ($that) {
				$that->output[] = $message;
			}
		);

		$connection = $this->entityManager->getConnection();
		if ($connection->getSchemaManager()->tablesExist(array('flow3_doctrine_migrationstatus')) === TRUE) {
			// works for SQLite, MySQL, PostgreSQL, Oracle
			// does not work for SQL Server
			$connection->exec('ALTER TABLE flow3_doctrine_migrationstatus RENAME TO flow_doctrine_migrationstatus');
		}

		$configuration = new \Doctrine\DBAL\Migrations\Configuration\Configuration($connection, $outputWriter);
		$configuration->setMigrationsNamespace('TYPO3\Flow\Persistence\Doctrine\Migrations');
		$configuration->setMigrationsDirectory(\TYPO3\Flow\Utility\Files::concatenatePaths(array(FLOW_PATH_DATA, 'DoctrineMigrations')));
		$configuration->setMigrationsTableName('flow_doctrine_migrationstatus');

		$configuration->createMigrationTable();

		$databasePlatformName = $this->getDatabasePlatformName();
		foreach ($this->packageManager->getActivePackages() as $package) {
			$configuration->registerMigrationsFromDirectory(
				\TYPO3\Flow\Utility\Files::concatenatePaths(array(
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
			$currentVersionFormatted = $configuration->formatVersion($currentVersion) . ' (' . $currentVersion . ')';
		} else {
			$currentVersionFormatted = 0;
		}
		$latestVersion = $configuration->getLatestVersion();
		if ($latestVersion) {
			$latestVersionFormatted = $configuration->formatVersion($latestVersion) . ' (' . $latestVersion . ')';
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
			'Migrations Target Directory'  => $configuration->getMigrationsDirectory(),
			'Current Version'       => $currentVersionFormatted,
			'Latest Version'        => $latestVersionFormatted,
			'Executed Migrations'   => $executedMigrations,
			'Available Migrations'  => $availableMigrations,
			'New Migrations'        => $newMigrations
		);
		foreach ($info as $name => $value) {
			$output .= '    >> ' . $name . ': ' . str_repeat(' ', 50 - strlen($name)) . $value . PHP_EOL;
		}

		if ($migrations = $configuration->getMigrations()) {
			$output .= "\n == Migration Versions\n";
			foreach ($migrations as $version) {
				$packageKey = $this->getPackageKeyFromMigrationVersion($version);
				$croppedPackageKey = strlen($packageKey) < 24 ? $packageKey : substr($packageKey, 0, 23) . '~';
				$packageKeyColumn = ' ' . str_pad($croppedPackageKey, 24, ' ');
				$status = $version->isMigrated() ? 'migrated' : 'not migrated';
				$output .= '    >> ' . $configuration->formatVersion($version->getVersion()) . ' (' . $version->getVersion() . ')' . $packageKeyColumn . str_repeat(' ', 4) . $status . PHP_EOL;
			}
		}

		return $output;
	}

	/**
	 * Tries to find out a package key which the Version belongs to. If no
	 * package could be found, an empty string is returned.
	 *
	 * @param Version $version
	 * @return string
	 */
	protected function getPackageKeyFromMigrationVersion(Version $version) {
		$sortedAvailablePackages = $this->packageManager->getAvailablePackages();
		usort($sortedAvailablePackages, function (PackageInterface $packageOne, PackageInterface $packageTwo) {
			return strlen($packageTwo->getPackagePath()) - strlen($packageOne->getPackagePath());
		});

		$reflectedClass = new \ReflectionClass($version->getMigration());
		$classPathAndFilename = Files::getUnixStylePath($reflectedClass->getFileName());

		/** @var $package PackageInterface */
		foreach ($sortedAvailablePackages as $package) {
			$packagePath = Files::getUnixStylePath($package->getPackagePath());
			if (strpos($classPathAndFilename, $packagePath) === 0) {
				return $package->getPackageKey();
			}
		}

		return '';
	}

	/**
	 * Execute all new migrations, up to $version if given.
	 *
	 * If $outputPathAndFilename is given, the SQL statements will be written to the given file instead of executed.
	 *
	 * @param string $version The version to migrate to
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @param boolean $quiet Whether to do a quiet run or not
	 * @return string
	 */
	public function executeMigrations($version = NULL, $outputPathAndFilename = NULL, $dryRun = FALSE, $quiet = FALSE) {
		$configuration = $this->getMigrationConfiguration();
		$migration = new \Doctrine\DBAL\Migrations\Migration($configuration);

		if ($outputPathAndFilename !== NULL) {
			$migration->writeSqlFile($outputPathAndFilename, $version);
		} else {
			$migration->migrate($version, $dryRun);
		}

		if ($quiet === TRUE) {
			$output = '';
			foreach ($this->output as $line) {
				$line = strip_tags($line);
				if (strpos($line, '  ++ migrating ') !== FALSE || strpos($line, '  -- reverting ') !== FALSE) {
					$output .= substr($line, -15);
				}
			}
			return $output;
		} else {
			return strip_tags(implode(PHP_EOL, $this->output));
		}
	}

	/**
	 * Execute a single migration in up or down direction. If $path is given, the
	 * SQL statements will be written to the file in $path instead of executed.
	 *
	 * @param string $version The version to migrate to
	 * @param string $direction
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @return string
	 */
	public function executeMigration($version, $direction = 'up', $outputPathAndFilename = NULL, $dryRun = FALSE) {
		$version = $this->getMigrationConfiguration()->getVersion($version);

		if ($outputPathAndFilename !== NULL) {
			$version->writeSqlFile($outputPathAndFilename, $direction);
		} else {
			$version->execute($direction, $dryRun);
		}
		return strip_tags(implode(PHP_EOL, $this->output));
	}

	/**
	 * Add a migration version to the migrations table or remove it.
	 *
	 * This does not execute any migration code but simply records a version
	 * as migrated or not.
	 *
	 * @param string $version The version to add or remove
	 * @param boolean $markAsMigrated
	 * @return void
	 * @throws \Doctrine\DBAL\Migrations\MigrationException
	 * @throws \LogicException
	 */
	public function markAsMigrated($version, $markAsMigrated) {
		$configuration = $this->getMigrationConfiguration();

		if ($version === 'all') {
			foreach ($configuration->getMigrations() as $version) {
				if ($markAsMigrated === TRUE && $configuration->hasVersionMigrated($version) === FALSE) {
					$version->markMigrated();
				} elseif ($markAsMigrated === FALSE && $configuration->hasVersionMigrated($version) === TRUE) {
					$version->markNotMigrated();
				}
			}
		} else {
			if ($configuration->hasVersion($version) === FALSE) {
				throw \Doctrine\DBAL\Migrations\MigrationException::unknownMigrationVersion($version);
			}

			$version = $configuration->getVersion($version);

			if ($markAsMigrated === TRUE) {
				if ($configuration->hasVersionMigrated($version) === TRUE) {
					throw new \Doctrine\DBAL\Migrations\MigrationException(sprintf('The version "%s" already exists in the version table.', $version));
				}
				$version->markMigrated();
			} else {
				if ($configuration->hasVersionMigrated($version) === FALSE) {
					throw new \Doctrine\DBAL\Migrations\MigrationException(sprintf('The version "%s" does not exist in the version table.', $version));
				}
				$version->markNotMigrated();
			}
		}
	}
	/**
	 * Generates a new migration file and returns the path to it.
	 *
	 * If $diffAgainstCurrent is TRUE, it generates a migration file with the
	 * diff between current DB structure and the found mapping metadata.
	 *
	 * Otherwise an empty migration skeleton is generated.
	 *
	 * @param boolean $diffAgainstCurrent
	 * @return string Path to the new file
	 */
	public function generateMigration($diffAgainstCurrent = TRUE) {
		$configuration = $this->getMigrationConfiguration();
		$up = NULL;
		$down = NULL;

		if ($diffAgainstCurrent === TRUE) {
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
		}

		return $this->writeMigrationClassToFile($configuration, $up, $down);
	}

	/**
	 * @param \Doctrine\DBAL\Migrations\Configuration\Configuration $configuration
	 * @param string $up
	 * @param string $down
	 * @return string
	 * @throws \RuntimeException
	 */
	protected function writeMigrationClassToFile(\Doctrine\DBAL\Migrations\Configuration\Configuration $configuration, $up, $down) {
		$namespace = $configuration->getMigrationsNamespace();
		$className = 'Version' . date('YmdHis');
		$up = $up === NULL ? '' : "\n		" . implode("\n		", explode("\n", $up));
		$down = $down === NULL ? '' : "\n		" . implode("\n		", explode("\n", $down));

		$path = \TYPO3\Flow\Utility\Files::concatenatePaths(array($configuration->getMigrationsDirectory(), $className . '.php'));
		try {
			\TYPO3\Flow\Utility\Files::createDirectoryRecursively(dirname($path));
		} catch (\TYPO3\Flow\Utility\Exception $exception) {
			throw new \RuntimeException(sprintf('Migration target directory "%s" does not exist.', dirname($path)), 1303298536, $exception);
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
EOT;
		file_put_contents($path, $code);

		return $path;
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
	 * Get name of current database platform
	 *
	 * @return string
	 */
	public function getDatabasePlatformName() {
		return ucfirst($this->entityManager->getConnection()->getDatabasePlatform()->getName());
	}

	/**
	 * This serves a rather strange use case: renaming columns used in FK constraints.
	 *
	 * For a column that is used in a FK constraint to be renamed, the FK constraint has to be
	 * dropped first, then the column can be renamed and last the FK constraint needs to be
	 * added back (using the new name, of course).
	 *
	 * This method helps with the task of handling the FK constraints during this. Given a list
	 * of tables that contain columns to be renamed and a search/replace pair for the column name,
	 * it will return an array with arrays with drop and add SQL statements.
	 *
	 * Use them like this before and after renaming the affected fields:
	 *
	 * // collect foreign keys pointing to "our" tables
	 * $tableNames = array(...);
	 * $foreignKeyHandlingSql = $this->getForeignKeyHandlingSql($schema, $tableNames, 'old_name', 'new_name');
	 *
	 * // drop FK constraints
	 * foreach ($foreignKeyHandlingSql['drop'] as $sql) {
	 *     $this->addSql($sql);
	 * }
	 *
	 * // rename columns now
	 *
	 * // add back FK constraints
	 * foreach ($foreignKeyHandlingSql['add'] as $sql) {
	 *     $this->addSql($sql);
	 * }
	 *
	 * @param \Doctrine\DBAL\Schema\Schema $schema
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 * @param array $tableNames
	 * @param string $search
	 * @param string $replace
	 * @return array
	 */
	static public function getForeignKeyHandlingSql(\Doctrine\DBAL\Schema\Schema $schema, \Doctrine\DBAL\Platforms\AbstractPlatform $platform, $tableNames, $search, $replace) {
		$foreignKeyHandlingSql = array('drop' => array(), 'add' => array());
		$tables = $schema->getTables();
		foreach ($tables as $table) {
			$foreignKeys = $table->getForeignKeys();
			foreach ($foreignKeys as $foreignKey) {
				if (!in_array($table->getName(), $tableNames) && !in_array($foreignKey->getForeignTableName(), $tableNames)) {
					continue;
				}

				$localColumns = $foreignKey->getLocalColumns();
				$foreignColumns = $foreignKey->getForeignColumns();
				if (in_array($search, $foreignColumns) || in_array($search, $localColumns)) {
					if (in_array($foreignKey->getLocalTableName(), $tableNames)) {
						array_walk(
							$localColumns,
							function (&$value) use ($search, $replace) {
								if ($value === $search) {
									$value = $replace;
								}
							}
						);
					}
					if (in_array($foreignKey->getForeignTableName(), $tableNames)) {
						array_walk(
							$foreignColumns,
							function (&$value) use ($search, $replace) {
								if ($value === $search) {
									$value = $replace;
								}
							}
						);
					}
					$newForeignKey = clone $foreignKey;
					\TYPO3\Flow\Reflection\ObjectAccess::setProperty($newForeignKey, '_localColumnNames', $localColumns, TRUE);
					\TYPO3\Flow\Reflection\ObjectAccess::setProperty($newForeignKey, '_foreignColumnNames', $foreignColumns, TRUE);
					$foreignKeyHandlingSql['drop'][] = $platform->getDropForeignKeySQL($foreignKey, $table);
					$foreignKeyHandlingSql['add'][] = $platform->getCreateForeignKeySQL($newForeignKey, $table);
				}
			}
		}

		return $foreignKeyHandlingSql;
	}
}
