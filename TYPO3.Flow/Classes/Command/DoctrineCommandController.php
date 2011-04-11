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
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
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
	 * @param \Doctrine\Common\Persistence\ObjectManager $entityManager
	 * @return void
	 */
	public function injectEntityManager(\Doctrine\Common\Persistence\ObjectManager $entityManager) {
		$this->entityManager = $entityManager;
	}

	/**
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * @return void
	 */
	public function helpCommand() {
		$this->response->appendContent('Available commands: validate, update, compile');
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
			$classesAndErrors = $this->validateMapping();
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
			$this->response->appendContent('Mapping validation results: SKIPPED, the driver and path backend options are not set.');
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
			$this->updateSchema();
			$this->response->appendContent('The database schema has been updated.');
		} else {
			$this->response->appendContent('   Update has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * Action for compiling Doctrine proxies
	 *
	 * @return void
	 */
	public function compileCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->compileProxies();
			$this->response->appendContent('   Doctrine proxies have been compiled.');
		} else {
			$this->response->appendContent('   Compilation has been SKIPPED, the driver and path backend options are not set.');
		}
	}

	/**
	 * @return array
	 */
	protected function validateMapping() {
		try {
			$result = array();
			$validator = new \Doctrine\ORM\Tools\SchemaValidator($this->entityManager);
			$result = $validator->validateMapping();
		} catch (\Exception $exception) {}
		return $result;
	}

	/**
	 * @return void
	 */
	protected function updateSchema() {
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
	}

	/**
	 * @return void
	 */
	protected function compileProxies() {
		$proxyFactory = $this->entityManager->getProxyFactory();
		$proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());
	}

}

?>