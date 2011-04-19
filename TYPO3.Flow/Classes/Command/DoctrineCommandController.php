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
	protected $service;

	/**
	 * @inject
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

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
	 * @return void
	 */
	public function helpCommand() {
		$this->response->appendContent('Available commands:
		validate, compileproxies
		create, update, updateandclean');
	}

	/**
	 * Action for validating the mapping
	 *
	 * @return void
	 */
	public function validateCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->response->appendContent('');
			$classesAndErrors = $this->service->validateMapping();
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
	 * Action for updating the database schema
	 *
	 * @return void
	 */
	public function createCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->service->createSchema();
			$this->response->appendContent('The database schema has been created.');
		} else {
			$this->response->appendContent('Database schema creation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Action for updating the database schema
	 *
	 * @return void
	 */
	public function updateCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->service->updateSchema();
			$this->response->appendContent('The database schema has been updated.');
		} else {
			$this->response->appendContent('Database schema update has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Action for updating the database schema
	 *
	 * @return void
	 */
	public function updateAndCleanCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->service->updateSchema(FALSE);
			$this->response->appendContent('The database schema has been updated.');
		} else {
			$this->response->appendContent('Database schema update has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Action for compiling Doctrine proxies
	 *
	 * @return void
	 */
	public function compileProxiesCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->service->compileProxies();
			$this->response->appendContent('Doctrine proxies have been compiled.');
		} else {
			$this->response->appendContent('Doctrine proxy compilation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

}

?>