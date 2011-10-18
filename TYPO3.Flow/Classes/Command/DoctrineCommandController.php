<?php
namespace TYPO3\FLOW3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Command controller for tasks related to Doctrine
 *
 * @FLOW3\Scope("singleton")
 */
class DoctrineCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\Doctrine\Service
	 */
	protected $doctrineService;

	/**
	 * Injects the FLOW3 settings, only the persistence part is kept for further use
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
	 * The validate command checks if the current class model schema is valid. Any
	 * inconsistencies in the relations between models (for example caused by wrong
	 * or missing annotations) will be reported.
	 *
	 * Note that this command does not check the table structure in the database in
	 * any way.
	 *
	 * @return void
	 * @see typo3.flow3:doctrine:entitystatus
	 */
	public function validateCommand() {
		$this->outputLine();
		$classesAndErrors = $this->doctrineService->validateMapping();
		if (count($classesAndErrors) === 0) {
			$this->outputLine('Mapping validation passed, no errors were found.');
		} else {
			$this->outputLine('Mapping validation FAILED!');
			foreach ($classesAndErrors as $className => $errors) {
				$this->outputLine('  %s', array($className));
				foreach ($errors as $errorMessage) {
					$this->outputLine('    %s', array($errorMessage));
				}
			}
			$this->quit(1);
		}
	}

	/**
	 * Create the database schema
	 *
	 * Creates a new database schema based on the current mapping information.
	 *
	 * @param string $output A file to write SQL to, instead of executing it
	 * @return void
	 * @see typo3.flow3:doctrine:update
	 * @see typo3.flow3:doctrine:migrate
	 */
	public function createCommand($output = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->doctrineService->createSchema($output);
			$this->outputLine('Created database schema.');
		} else {
			$this->outputLine('Database schema creation has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Update the database schema
	 *
	 * This comand updates the database schema without using existing migrations.
	 * It will, unless --unsafe-mode is set, not drop foreign keys, sequences and
	 * tables.
	 *
	 * @param boolean $unsafeMode If set, foreign keys, sequences and tables can potentially be dropped.
	 * @param string $output A file to write SQL to, instead of executing the update directly
	 * @return void
	 * @see typo3.flow3:doctrine:create
	 * @see typo3.flow3:doctrine:migrate
	 */
	public function updateCommand($unsafeMode = FALSE, $output = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->doctrineService->updateSchema(!$unsafeMode, $output);
			$this->outputLine('Executed a database schema update.');
		} else {
			$this->outputLine('Database schema update has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Compile the Doctrine proxy classes
	 *
	 * @return void
	 * @FLOW3\Internal
	 */
	public function compileProxiesCommand() {
		$this->doctrineService->compileProxies();
	}

	/**
	 * Show the current status of entities and mappings
	 *
	 * Shows basic information about which entities exist and possibly if their
	 * mapping information contains errors or not. To run a full validation, use
	 * the validate command.
	 *
	 * @param boolean $dumpMappingData
	 * @return void
	 * @see typo3.flow3:doctrine:validate
	 */
	public function entityStatusCommand($dumpMappingData = FALSE) {
		$info = $this->doctrineService->getEntityStatus();

		if ($info === array()) {
			$this->output('You do not have any mapped Doctrine ORM entities according to the current configuration. ');
			$this->outputLine('If you have entities or mapping files you should check your mapping configuration for errors.');
		} else {
			$this->outputLine('Found %d mapped entities:', array(count($info)));
			foreach ($info as $entityClassName => $entityStatus) {
				if ($entityStatus instanceof \Doctrine\Common\Persistence\Mapping\ClassMetadata) {
					$this->outputLine('[OK]   %s', array($entityClassName));
					if ($dumpMappingData) {
						\TYPO3\FLOW3\Error\Debugger::clearState();
						$this->outputLine(\TYPO3\FLOW3\Error\Debugger::renderDump($entityStatus, 0, TRUE, TRUE));
					}
				} else {
					$this->outputLine('[FAIL] %s', array($entityClassName));
					$this->outputLine($entityStatus);
					$this->outputLine();
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
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$dqlSatetements = $this->request->getExceedingArguments();
			$hydrationMode = 'Doctrine\ORM\Query::HYDRATE_' . strtoupper(str_replace('-', '_', $hydrationModeName));
			if (!defined($hydrationMode)) {
				throw new \InvalidArgumentException('Hydration mode "' . $hydrationModeName . '" does not exist. It should be either: object, array, scalar or single-scalar.');
			}

			foreach ($dqlSatetements as $dql) {
				$resultSet = $this->doctrineService->runDql($dql, $hydrationMode, $firstResult, $maxResult);
				\Doctrine\Common\Util\Debug::dump($resultSet, $depth);
			}
		} else {
			$this->outputLine('DQL query is not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Show the current migration status
	 *
	 * @return void
	 * @see typo3.flow3:doctrine:migrate
	 * @see typo3.flow3:doctrine:migrationexecute
	 * @see typo3.flow3:doctrine:migrationgenerate
	 * @see typo3.flow3:doctrine:migrationversion
	 */
	public function migrationStatusCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->outputLine($this->doctrineService->getMigrationStatus());
		} else {
			$this->outputLine('Doctrine migration status not available, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Migrate the database schema
	 *
	 * This command adjusts the database structure by applying one or more doctrine
	 * migrations provided by one or more active FLOW3 packages.
	 *
	 * @param string $version The version to migrate to
	 * @param string $output A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @return void
	 * @see typo3.flow3:doctrine:migrationstatus
	 * @see typo3.flow3:doctrine:migrationexecute
	 * @see typo3.flow3:doctrine:migrationgenerate
	 * @see typo3.flow3:doctrine:migrationversion
	 */
	public function migrateCommand($version = NULL, $output = NULL, $dryRun = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$output = $this->doctrineService->executeMigrations($version, $output, $dryRun);
			if ($output != '') {
				$this->outputLine($output);
			} else {
				$this->outputLine('No migration was neccessary.');
			}
		} else {
			$this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
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
	 * @see typo3.flow3:doctrine:migrate
	 * @see typo3.flow3:doctrine:migrationstatus
	 * @see typo3.flow3:doctrine:migrationgenerate
	 * @see typo3.flow3:doctrine:migrationversion
	 */
	public function migrationExecuteCommand($version, $direction = 'up', $output = NULL, $dryRun = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->outputLine($this->doctrineService->executeMigration($version, $direction, $output, $dryRun));
		} else {
			$this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Mark/unmark a migration as migrated
	 *
	 * @param string $version The migration to execute
	 * @param boolean $add The migration to mark as migrated
	 * @param boolean $delete The migration to mark as not migrated
	 * @return void
	 * @see typo3.flow3:doctrine:migrate
	 * @see typo3.flow3:doctrine:migrationstatus
	 * @see typo3.flow3:doctrine:migrationexecute
	 * @see typo3.flow3:doctrine:migrationgenerate
	 */
	public function migrationVersionCommand($version, $add = FALSE, $delete = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			if ($add === FALSE && $delete === FALSE) {
				throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
			}
			$this->outputLine($this->doctrineService->markAsMigrated($version, $add ?: FALSE));
		} else {
			$this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
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
	 * @see typo3.flow3:doctrine:migrate
	 * @see typo3.flow3:doctrine:migrationstatus
	 * @see typo3.flow3:doctrine:migrationexecute
	 * @see typo3.flow3:doctrine:migrationversion
	 */
	public function migrationGenerateCommand($diffAgainstCurrent = TRUE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->outputLine(sprintf('Generated new migration class to "%s".', $this->doctrineService->generateMigration($diffAgainstCurrent)));
		} else {
			$this->outputLine('Doctrine migration generation has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

}

?>
