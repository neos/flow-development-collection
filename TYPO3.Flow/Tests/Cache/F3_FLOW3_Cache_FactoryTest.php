<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Cache;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

require_once ('F3_FLOW3_Cache_MockBackend.php');

/**
 * Testcase for the Cache Factory
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FactoryTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createReturnsInstanceOfTheSpecifiedCacheFrontend() {
		$mockCacheManager = $this->getMock('F3::FLOW3::Cache::Manager', array('registerCache'), array(), '', FALSE);
		$factory = new F3::FLOW3::Cache::Factory($this->objectManager, $this->objectFactory, $mockCacheManager);

		$cache = $factory->create('F3_FLOW3_Cache_FactoryTest_Cache', 'F3::FLOW3::Cache::VariableCache', 'F3::FLOW3::Cache::Backend::Null');
		$this->assertType('F3::FLOW3::Cache::VariableCache', $cache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend() {
		$mockCacheManager = $this->getMock('F3::FLOW3::Cache::Manager', array('registerCache'), array(), '', FALSE);
		$factory = new F3::FLOW3::Cache::Factory($this->objectManager, $this->objectFactory, $mockCacheManager);

		$cache = $factory->create('F3_FLOW3_Cache_FactoryTest_Cache', 'F3::FLOW3::Cache::VariableCache', 'F3::FLOW3::Cache::Backend::File');
		$this->assertType('F3::FLOW3::Cache::Backend::File', $cache->getBackend());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createPassesBackendOptionsToTheCreatedBackend() {
		$this->objectManager->registerObject('F3::FLOW3::Cache::MockBackend');
		$backendObjectConfiguration = $this->objectManager->getObjectConfiguration('F3::FLOW3::Cache::MockBackend');
		$backendObjectConfiguration->setScope(F3::FLOW3::Object::Configuration::SCOPE_PROTOTYPE);
		$this->objectManager->setObjectConfiguration($backendObjectConfiguration);

		$mockCacheManager = $this->getMock('F3::FLOW3::Cache::Manager', array('registerCache'), array(), '', FALSE);

		$factory = new F3::FLOW3::Cache::Factory($this->objectManager, $this->objectFactory, $mockCacheManager);

		$someValue = microtime();
		$cache = $factory->create('F3_FLOW3_Cache_FactoryTest_Cache', 'F3::FLOW3::Cache::VariableCache', 'F3::FLOW3::Cache::MockBackend', array('someOption' => $someValue));
		$this->assertType('F3::FLOW3::Cache::MockBackend', $cache->getBackend());
		$this->assertEquals($someValue, $cache->getBackend()->getSomeOption());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createdRegistersTheCacheAtTheCacheManager() {
		$mockCacheManager = $this->getMock('F3::FLOW3::Cache::Manager', array('registerCache'), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('registerCache');

		$factory = new F3::FLOW3::Cache::Factory($this->objectManager, $this->objectFactory, $mockCacheManager);

		$factory->create('F3_FLOW3_Cache_FactoryTest_Cache', 'F3::FLOW3::Cache::VariableCache', 'F3::FLOW3::Cache::Backend::Null');
	}
}
?>