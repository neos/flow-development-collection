<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Core\ApplicationContext;

/**
 * Testcase for the abstract cache backend
 *
 */
class AbstractBackendTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Cache\Backend\AbstractBackend
	 */
	protected $backend;

	/**
	 * @return void
	 */
	public function setUp() {
		$className = 'ConcreteBackend_' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $className. ' extends \TYPO3\Flow\Cache\Backend\AbstractBackend {
				public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {}
				public function get($entryIdentifier) {}
				public function has($entryIdentifier) {}
				public function remove($entryIdentifier) {}
				public function flush() {}
				public function flushByTag($tag) {}
				public function findIdentifiersByTag($tag) {}
				public function collectGarbage() {}
				public function setSomeOption($value) {
					$this->someOption = $value;
				}
				public function getSomeOption() {
					return $this->someOption;
				}
			}
		');
		$this->backend = new $className(new ApplicationContext('Testing'));
	}

	/**
	 * @test
	 */
	public function theConstructorCallsSetterMethodsForAllSpecifiedOptions() {
		$className = get_class($this->backend);
		$backend = new $className(new ApplicationContext('Testing'), array('someOption' => 'someValue'));
		$this->assertSame('someValue', $backend->getSomeOption());
	}
}
