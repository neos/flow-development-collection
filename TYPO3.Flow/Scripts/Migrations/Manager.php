<?php
namespace TYPO3\FLOW3\Core\Migrations;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Utility\Files;

/**
 * The central hub of the code migration tool in FLOW3.
 */
class Manager {

	const STATE_NOT_MIGRATED = 0;
	const STATE_MIGRATED = 1;

	/**
	 * @var string
	 */
	protected $packagesPath = FLOW3_PATH_PACKAGES;

	/**
	 * @var array
	 */
	protected $packagesData = array();

	/**
	 * @var array
	 */
	protected $migrations = array();

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
	 * @return array
	 */
	public function getStatus() {
		$this->initialize();

		$status = array();
		foreach ($this->packagesData as $packageKey => $packageData) {
			$packageStatus = array();
			foreach ($this->migrations as $versionNumber => $versionInstance) {
				$migrationIdentifier = $versionInstance->getPackageKey() . '-' . $versionNumber;

				if (Git::hasMigrationApplied($packageData['path'], $migrationIdentifier)) {
					$state = self::STATE_MIGRATED;
				} else {
					$state = self::STATE_NOT_MIGRATED;
				}
				$packageStatus[$versionNumber] = array('source' => $migrationIdentifier, 'state' => $state);
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
	 * @param string $packageKey
	 * @return void
	 * @throws \RuntimeException
	 */
	public function migrate($packageKey = NULL) {
		$this->initialize();

		foreach ($this->migrations as $migrationInstance) {
			$migrationInstance->up();
		}

		foreach ($this->migrations as $migrationInstance) {
			echo 'Applying ' . $migrationInstance->getIdentifier() . PHP_EOL;
			if ($packageKey !== NULL) {
				if (array_key_exists($packageKey, $this->packagesData)) {
					$this->migratePackage($packageKey, $this->packagesData[$packageKey], $migrationInstance);
				} else {
					echo '  Package "' . $packageKey . '" was not found.' . PHP_EOL;
				}
			} else {
				foreach ($this->packagesData as $key => $packageData) {
					if ($packageData['category'] === 'Framework' || $packageData['category'] === 'Vendor') {
						continue;
					}
					$this->migratePackage($key, $packageData, $migrationInstance);
				}
			}
			$migrationInstance->outputNotesAndWarnings();
			echo 'Done with ' . $migrationInstance->getIdentifier() . PHP_EOL . PHP_EOL;
		}
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
		if (Git::isWorkingCopyClean($packageData['path'])) {
			echo '  Migrating ' . $packageKey . PHP_EOL;
			if (Git::hasMigrationApplied($packageData['path'], $migration->getIdentifier())) {
				echo '    Skipping ' . $packageKey . ', the migration is already applied.' . PHP_EOL;
			} else {
				try {
					$migration->execute($packageData);
					echo Git::commitMigration($packageData['path'], $migration->getIdentifier());
				} catch (\Exeption $exception) {
					throw new \RuntimeException('Applying migration "' .$migration->getIdentifier() . '" to "' . $packageKey . '" failed.', 0, $exception);
				}
			}
		} else {
			echo '    Skipping ' . $packageKey . ', the working copy is dirty.' . PHP_EOL;
		}
	}


	/**
	 * Initialize the manager: read package information and register migrations.
	 *
	 * @return void
	 */
	protected function initialize() {
		$this->packagesData = Tools::getPackagesData($this->packagesPath);

		$this->migrations = array();
		foreach ($this->packagesData as $packageKey => $packageData) {
			$this->registerMigrationFiles(Files::concatenatePaths(array($this->packagesPath, $packageData['category'], $packageKey)));
		}
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
			require_once($filenameAndPath);
			$baseFilename = basename($filenameAndPath, '.php');
			$version = substr($baseFilename, 7);
			$classname = 'TYPO3\FLOW3\Core\Migrations\\' . $baseFilename;
			$this->migrations[$version] = new $classname($this, $packageKey);
		}
		ksort($this->migrations);
	}

}

?>