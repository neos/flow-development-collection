<?php
declare(ENCODING = 'utf-8');
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

}

?>