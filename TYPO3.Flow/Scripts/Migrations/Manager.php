<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\Files;

/**
 * The central hub of the code migration tool in Flow.
 */
class Manager {

	const STATE_NOT_MIGRATED = 0;
	const STATE_MIGRATED = 1;

	const EVENT_MIGRATION_START = 'migrationStart';
	const EVENT_MIGRATION_EXECUTE = 'migrationExecute';
	const EVENT_MIGRATION_SKIPPED_LOCAL_CHANGES = 'migrationSkippedLocalChanges';
	const EVENT_MIGRATION_SKIPPED_ALREADY_APPLIED = 'migrationSkippedAlreadyApplied';
	const EVENT_MIGRATION_EXECUTED = 'migrationExecuted';
	const EVENT_MIGRATION_DONE = 'migrationDone';

	/**
	 * @var string
	 */
	protected $packagesPath = FLOW_PATH_PACKAGES;

	/**
	 * @var array
	 */
	protected $ignoredPackageCategories = array('Framework', 'Libraries');

	/**
	 * @var array
	 */
	protected $packagesData = NULL;

	/**
	 * @var AbstractMigration[]
	 */
	protected $migrations = NULL;

	/**
	 * Callbacks to be invoked when an event is triggered
	 *
	 * @see triggerEvent()
	 * @var array
	 */
	protected $eventCallbacks;

	/**
	 * Allows to set the packages path.
	 *
	 * The level directly inside is expected to consist of package "categories"
	 * (Framework, Application, Plugins, ...).
	 *
	 * @param string $packagesPath
	 * @return void
	 */
	public function setPackagesPath($packagesPath) {
		$this->packagesPath = $packagesPath;
	}

	/**
	 * Returns the migration status for all packages.
	 *
	 * @param string $packageKey key of the package to migrate, or NULL to migrate all packages
	 * @param string $versionNumber version of the migration to fetch the status for (e.g. "20120126163610"), or NULL to consider all migrations
	 * @return array in the format [<versionNumber> => ['migration' => <AbstractMigration>, 'state' => <STATE_*>], [...]]
	 */
	public function getStatus($packageKey = NULL, $versionNumber = NULL) {
		$status = array();
		$migrations = $this->getMigrations($versionNumber);
		foreach ($this->getPackagesData($packageKey) as $packageKey => $packageData) {
			$packageStatus = array();
			foreach ($migrations as $migration) {
				if ($this->hasMigrationApplied($packageData['path'], $migration)) {
					$state = self::STATE_MIGRATED;
				} else {
					$state = self::STATE_NOT_MIGRATED;
				}
				$packageStatus[$migration->getVersionNumber()] = array('migration' => $migration, 'state' => $state);
			}
			$status[$packageKey] = $packageStatus;
		}
		return $status;
	}

	/**
	 * This iterates over available migrations and applies them to
	 * the existing packages if
	 * - the package needs the migration
	 * - is a clean git working copy
	 *
	 * @param string $packageKey key of the package to migrate, or NULL to migrate all packages
	 * @param string $versionNumber version of the migration to execute (e.g. "20120126163610"), or NULL to execute all migrations
	 * @return void
	 */
	public function migrate($packageKey = NULL, $versionNumber = NULL) {
		$packagesData = $this->getPackagesData($packageKey);
		foreach ($this->getMigrations($versionNumber) as $migration) {
			$this->triggerEvent(self::EVENT_MIGRATION_START, array($migration));
			foreach ($packagesData as $key => $packageData) {
				if ($packageKey === NULL && $this->shouldPackageBeSkippedByDefault($packageData)) {
					continue;
				}
				$this->migratePackage($key, $packageData, $migration);
			}
			$this->triggerEvent(self::EVENT_MIGRATION_DONE, array($migration));
		}
	}

	/**
	 * By default we skip "TYPO3.*" packages and all packages of the ignored categories (@see ignoredPackageCategories)
	 *
	 * @param array $packageData @see getPackagesData()
	 * @return boolean
	 */
	protected function shouldPackageBeSkippedByDefault(array $packageData) {
		if (strpos($packageData['packageKey'], 'TYPO3.') === 0) {
			return TRUE;
		}
		if (in_array($packageData['category'], $this->ignoredPackageCategories)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Apply the given migration to the package and commit the result.
	 *
	 * @param string $packageKey
	 * @param array $packageData
	 * @param AbstractMigration $migration
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function migratePackage($packageKey, array $packageData, AbstractMigration $migration) {
		$packagePath = $packageData['path'];
		if (!Git::isWorkingCopyClean($packagePath)) {
			$this->triggerEvent(self::EVENT_MIGRATION_SKIPPED_LOCAL_CHANGES, array($migration, $packageKey, 'working copy contains local changes'));
			return;
		}
		if ($this->hasMigrationApplied($packagePath, $migration)) {
			$this->triggerEvent(self::EVENT_MIGRATION_SKIPPED_ALREADY_APPLIED, array($migration, $packageKey, 'migration already applied'));
			return;
		}
		$this->triggerEvent(self::EVENT_MIGRATION_EXECUTE, array($migration, $packageKey));
		try {
			$migration->prepare($this->packagesData[$packageKey]);
			$migration->up();
			$migration->execute();
			$migrationResult = $this->commitMigration($packagePath, $migration);
			$this->triggerEvent(self::EVENT_MIGRATION_EXECUTED, array($migration, $packageKey, $migrationResult));
		} catch (\Exception $exception) {
			throw new \RuntimeException(sprintf('Applying migration "%s" to "%s" failed.', $migration->getIdentifier(), $packageKey), 1421692982, $exception);
		}
	}

	/**
	 * Whether or not the given migration has been applied in the given path
	 *
	 * @param string $packagePath
	 * @param AbstractMigration $migration
	 * @return boolean
	 */
	protected function hasMigrationApplied($packagePath, AbstractMigration $migration) {
		return Git::logContains($packagePath, 'Migration: ' . $migration->getIdentifier());
	}

	/**
	 * Commit changes done to the package described by $packageData. The migration
	 * that was did the changes is given with $versionNumber and $versionPackageKey
	 * and will be recorded in the commit message.
	 *
	 * @param string $packagePath
	 * @param AbstractMigration $migration
	 * @return string
	 */
	protected function commitMigration($packagePath, AbstractMigration $migration) {
		$migrationIdentifier = $migration->getIdentifier();
		$commitMessage = sprintf('[TASK] Apply migration %s', $migrationIdentifier) . chr(10) . chr(10);
		$description = $migration->getDescription();
		if ($description !== NULL) {
			$commitMessage .= wordwrap($description, 72);
		} else {
			$commitMessage .= wordwrap(sprintf('This commit contains the result of applying migration %s to this package.', $migrationIdentifier), 72);
		}
		$commitMessage .= chr(10) . chr(10);
		if (Git::isWorkingCopyClean($packagePath)) {
			$commitMessage .= wordwrap('Note: This migration did not produce any changes, so the commit simply marks the migration as applied. This makes sure it will not be applied again.', 72) . chr(10) . chr(10);
		}
		$commitMessage .= sprintf('Migration: %s', $migrationIdentifier);

		list ($returnCode, $output) = Git::commitAll($packagePath, $commitMessage);
		if ($returnCode === 0) {
			return '    ' . implode(PHP_EOL . '    ', $output) . PHP_EOL;
		} else {
			return '    No changes were committed.' . PHP_EOL;
		}
	}

	/**
	 * Attaches a new event handler
	 *
	 * @param string $eventIdentifier one of the EVENT_* constants
	 * @param \Closure $callback a closure to be invoked when the corresponding event was triggered
	 */
	public function on($eventIdentifier, \Closure $callback) {
		$this->eventCallbacks[$eventIdentifier][] = $callback;
	}

	/**
	 * Trigger a custom event
	 *
	 * @param string $eventIdentifier one of the EVENT_* constants
	 * @param array $eventData optional arguments to be passed to the handler closure
	 */
	protected function triggerEvent($eventIdentifier, array $eventData = NULL) {
		if (!isset($this->eventCallbacks[$eventIdentifier])) {
			return;
		}
		/** @var \Closure $callback */
		foreach ($this->eventCallbacks[$eventIdentifier] as $callback) {
			call_user_func_array($callback, $eventData);
		}
	}


	/**
	 * Initialize the manager: read package information and register migrations.
	 *
	 * @return void
	 */
	protected function initialize() {
		if ($this->packagesData !== NULL) {
			return;
		}
		$this->packagesData = Tools::getPackagesData($this->packagesPath);

		$this->migrations = array();
		foreach ($this->packagesData as $packageKey => $packageData) {
			$this->registerMigrationFiles(Files::concatenatePaths(array($this->packagesPath, $packageData['category'], $packageKey)));
		}
		ksort($this->migrations);
	}

	/**
	 * Look for code migration files in the given package path and register them
	 * for further action.
	 *
	 * @param string $packagePath
	 * @return void
	 */
	protected function registerMigrationFiles($packagePath) {
		$packagePath = rtrim($packagePath, '/');
		$packageKey = substr($packagePath, strrpos($packagePath, '/') + 1);
		$migrationsDirectory = Files::concatenatePaths(array($packagePath, 'Migrations/Code'));
		if (!is_dir($migrationsDirectory)) {
			return;
		}

		$migrationFilenames = Files::readDirectoryRecursively($migrationsDirectory, '.php');
		foreach ($migrationFilenames as $filenameAndPath) {
			/** @noinspection PhpIncludeInspection */
			require_once($filenameAndPath);
			$baseFilename = basename($filenameAndPath, '.php');
			$className = '\\TYPO3\\Flow\\Core\\Migrations\\' . $baseFilename;
			/** @var AbstractMigration $migration */
			$migration = new $className($this, $packageKey);
			$this->migrations[$migration->getVersionNumber()] = $migration;
		}
	}


	/**
	 * @param string $versionNumber if specified only the migration with the specified version is returned
	 * @return AbstractMigration[]
	 * @throws \InvalidArgumentException
	 */
	protected function getMigrations($versionNumber = NULL) {
		$this->initialize();

		if ($versionNumber === NULL) {
			return $this->migrations;
		}
		if (!isset($this->migrations[$versionNumber])) {
			throw new \InvalidArgumentException(sprintf('Migration "%s" was not found', $versionNumber), 1421667040);
		}
		return array($versionNumber => $this->migrations[$versionNumber]);
	}


	/**
	 * @param string $packageKey if specified, only the package data for the given key is returned
	 * @return array in the format ['<packageKey' => ['packageKey' => '<packageKey>', 'category' => <Application/Framework/...>, 'path' => '<packagePath>', 'meta' => '<packageMetadata>', 'composerManifest' => '<composerData>'], [...]]
	 * @throws \InvalidArgumentException
	 */
	protected function getPackagesData($packageKey = NULL) {
		$this->initialize();

		if ($packageKey === NULL) {
			return $this->packagesData;
		}
		if (!isset($this->packagesData[$packageKey])) {
			throw new \InvalidArgumentException(sprintf('Package "%s" was not found', $packageKey), 1421667044);
		}
		return array($packageKey => $this->packagesData[$packageKey]);
	}

}
