<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Command;

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
class DoctrineCommandController extends \F3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @inject
	 * @var \F3\FLOW3\Persistence\Doctrine\Service
	 */
	protected $doctrineService;

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
	 * Validates the class/table mappings
	 *
	 * The validate command checks if the current class model schema matches the table structure in the database.
	 *
	 * @return void
	 */
	public function validateCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent('');
			$classesAndErrors = $this->doctrineService->validateMapping();
			if (count($classesAndErrors) === 0) {
				$this->response->appendContent('Mapping validation results: PASSED, no errors found. :o)');
			} else {
				$this->response->appendContent('Mapping validation results: FAILED!');
				foreach ($classesAndErrors as $className => $errors) {
					$this->response->appendContent('  ' . $className);
					foreach ($errors as $errorMessage) {
						$this->response->appendContent('    ' . $errorMessage);
					}
				}
			}
		} else {
			$this->response->appendContent('Mapping validation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Creates the database schema
	 *
	 * @return void
	 */
	public function createCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->doctrineService->createSchema();
		} else {
			$this->response->appendContent('Database schema creation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Updates the database schema without data loss
	 *
	 * @return void
	 */
	public function updateCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->doctrineService->updateSchema();
		} else {
			$this->response->appendContent('Database schema update has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Updates database schema and removes obsolete tables / fields
	 *
	 * @return void
	 */
	public function updateAndCleanCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->doctrineService->updateSchema(FALSE);
		} else {
			$this->response->appendContent('Database schema update has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Compiles the Doctrine proxy classes
	 *
	 * @return void
	 */
	public function compileProxiesCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->doctrineService->compileProxies();
		} else {
			$this->response->appendContent('Doctrine proxy compilation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Shows the current migration status
	 *
	 * @return void
	 */
	public function migrationStatusCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->getMigrationStatus());
		} else {
			$this->response->appendContent('Doctrine migration status not available, the driver and path backend options are not set.');
		}
	}

	/**
	 * Migrates the database schema
	 *
	 * @return void
	 */
	public function migrateCommand() {
		$version = NULL; // we need arguments for commands...
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->executeMigrations($version));
		} else {
			$this->response->appendContent('Doctrine migration not possible, the driver and path backend options are not set.');
		}
	}

	/**
	 * Generates a migration diff
	 *
	 * @return void
	 */
	public function migrationDiffCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->generateDiffMigration());
		} else {
			$this->response->appendContent('Doctrine migration generation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Generates an empty migration
	 *
	 * @return void
	 */
	public function migrationGenerateCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->generateEmptyMigration());
		} else {
			$this->response->appendContent('Doctrine migration generation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Executes a single migration
	 *
	 * @return void
	 */
	public function migrationExecuteCommand() {
		$version = NULL; // we need arguments for commands...
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent($this->doctrineService->executeMigration($version));
		} else {
			$this->response->appendContent('Doctrine migration not possible, the driver and path backend options are not set.');
		}
	}

}

?>
