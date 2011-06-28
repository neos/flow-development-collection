<?php
namespace TYPO3\FLOW3\Command;

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
 * Command controller for tasks related to Doctrine
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class DoctrineCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Persistence\Doctrine\Service
	 */
	protected $doctrineService;

	/**
	 * Injects the FLOW3 settings, only the persistence part is kept for further use.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings['persistence'];
	}

	/**
	 * Validate the class/table mappings
	 *
	 * The validate command checks if the current class model schema matches the table structure in the database.
	 *
	 * @return void
	 */
	public function validateCommand() {
		$this->response->appendContent('');
		$classesAndErrors = $this->doctrineService->validateMapping();
		if (count($classesAndErrors) === 0) {
			$this->response->appendContent('Mapping validation passed, no errors were found.');
		} else {
			$this->response->appendContent('Mapping validation FAILED!');
			foreach ($classesAndErrors as $className => $errors) {
				$this->response->appendContent('  ' . $className);
				foreach ($errors as $errorMessage) {
					$this->response->appendContent('    ' . $errorMessage);
				}
			}
		}
	}

	/**
	 * Create the database schema based on current mapping information
	 *
	 * @param string $output A file to write SQL to, instead of executing it
	 * @return void
	 */
	public function createCommand($output = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->doctrineService->createSchema($output);
		} else {
			$this->response->appendContent('Database schema creation has been SKIPPED, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

	/**
	 * Update the database schema, not using migrations
	 *
	 * It will, unless $safeMode is set to FALSE, not drop foreign keys, sequences and tables.
	 *
	 * @param boolean $safeMode
	 * @param string $output A file to write SQL to, instead of executing it
	 * @return void
	 */
	public function updateCommand($safeMode = TRUE, $output = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->doctrineService->updateSchema($safeMode, $output);
		} else {
			$this->response->appendContent('Database schema update has been SKIPPED, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

	/**
	 * Compile the Doctrine proxy classes
	 *
	 * @return void
	 */
	public function compileProxiesCommand() {
		$this->doctrineService->compileProxies();
	}

	/**
	 * Show the current status of entities and mappings
	 *
	 * Shows basic information about which entities exist and possibly if their
	 * mapping information contains errors or not. To run a full validation,
	 * use the validate command.
	 *
	 * @return void
	 */
	public function entityStatusCommand() {
		$info = $this->doctrineService->getEntityStatus();

		if ($info === array()) {
			$this->response->appendContent('You do not have any mapped Doctrine ORM entities according to the current configuration. ' .
			'If you have entities or mapping files you should check your mapping configuration for errors.');
		} else {
			$this->response->appendContent('Found ' . count($info) . ' mapped entities:');
			foreach ($info as $entityClassName => $entityStatus) {
				if ($entityStatus === TRUE) {
					$this->response->appendContent('[OK]   ' . $entityClassName);
				} else {
					$this->response->appendContent('[FAIL] ' . $entityClassName);
					$this->response->appendContent($entityStatus);
					$this->response->appendContent('');
				}
			}
		}
	}

	/**
	 * Run arbitrary DQL and display results
	 *
	 * @param integer $depth
	 * @param string $hydrationModeName
	 * @param integer $firstResult
	 * @param integer $maxResult
	 * @return void
	 * @throws \LogicException
	 * @throws \RuntimeException
	 */
	public function dqlCommand($depth = 3, $hydrationModeName = 'object', $firstResult = NULL, $maxResult = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$dqlSatetements = $this->request->getCommandLineArguments();
			$hydrationMode = 'Doctrine\ORM\Query::HYDRATE_' . strtoupper(str_replace('-', '_', $hydrationModeName));
			if (!defined($hydrationMode)) {
				throw new \InvalidArgumentException('Hydration mode "' . $hydrationModeName . '" does not exist. It should be either: object, array, scalar or single-scalar.');
			}

			foreach ($dqlSatetements as $dql) {
				$resultSet = $this->doctrineService->runDql($dql, $hydrationMode, $firstResult, $maxResult);
				\Doctrine\Common\Util\Debug::dump($resultSet, $depth);
			}
		} else {
			$this->response->appendContent('DQL query is not possible, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

	/**
	 * Show the current migration status
	 *
	 * @return void
	 */
	public function migrationStatusCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->getMigrationStatus());
		} else {
			$this->response->appendContent('Doctrine migration status not available, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

	/**
	 * Migrate the database schema
	 *
	 * @param string $version The version to migrate to
	 * @param string $output A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @return void
	 */
	public function migrateCommand($version = NULL, $output = NULL, $dryRun = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->executeMigrations($version, $output, $dryRun));
		} else {
			$this->response->appendContent('Doctrine migration not possible, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

	/**
	 * Execute a single migration
	 *
	 * @param string $version The migration to execute
	 * @param string $direction Whether to execute the migration up (default) or down
	 * @param string $output A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @return void
	 */
	public function migrationExecuteCommand($version, $direction = 'up', $output = NULL, $dryRun = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->executeMigration($version, $direction, $output, $dryRun));
		} else {
			$this->response->appendContent('Doctrine migration not possible, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

	/**
	 * Mark/unmark a migration as migrated
	 *
	 * @param string $version The migration to execute
	 * @param boolean $add The migration to mark as migrated
	 * @param boolean $delete The migration to mark as not migrated
	 * @return void
	 */
	public function migrationVersionCommand($version, $add = FALSE, $delete = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			if ($add === FALSE && $delete === FALSE) {
				throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
			}
			$this->response->appendContent($this->doctrineService->markAsMigrated($version, $add ?: FALSE));
		} else {
			$this->response->appendContent('Doctrine migration not possible, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

	/**
	 * Generate a new migration
	 *
	 * If $diffAgainstCurrent is TRUE, it generates a migration file with the
	 * diff between current DB structure and the found mapping metadata.
	 *
	 * Otherwise an empty migration skeleton is generated.
	 *
	 * @param boolean $diffAgainstCurrent
	 * @return void
	 */
	public function migrationGenerateCommand($diffAgainstCurrent = TRUE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent(sprintf('Generated new migration class to "%s".', $this->doctrineService->generateMigration($diffAgainstCurrent)));
		} else {
			$this->response->appendContent('Doctrine migration generation has been SKIPPED, the driver and path backend options are not set in /Configuration/Settings.yaml.');
		}
	}

}

?>
