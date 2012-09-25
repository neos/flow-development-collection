<?php
namespace TYPO3\Flow\Tests\Unit\Log\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the abstract log backend
 *
 */
class AbstractBackendTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Log\Backend\AbstractBackend
	 */
	protected $backendClassName;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->backendClassName = 'ConcreteBackend_' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $this->backendClassName . ' extends \TYPO3\Flow\Log\Backend\AbstractBackend {
				public function open() {}
				public function append($message, $severity = 1, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {}
				public function close() {}
				public function setSomeOption($value) {
					$this->someOption = $value;
				}
				public function getSomeOption() {
					return $this->someOption;
				}
			}
		');
	}

	/**
	 * @test
	 */
	public function theConstructorCallsSetterMethodsForAllSpecifiedOptions() {
		$className = $this->backendClassName;
		$backend = new $className(array('someOption' => 'someValue'));
		$this->assertSame('someValue', $backend->getSomeOption());
	}

}
?>