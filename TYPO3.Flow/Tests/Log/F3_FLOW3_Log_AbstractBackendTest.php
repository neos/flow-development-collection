<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log;

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
 * @subpackage Log
 * @version $Id:\F3\FLOW3\AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the abstract log backend
 *
 * @package FLOW3
 * @subpackage Log
 * @version $Id:\F3\FLOW3\AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AbstractBackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Log\AbstractBackend
	 */
	protected $backend;

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$className = uniqid('ConcreteBackend_');
		eval('
			class ' . $className. ' extends \F3\FLOW3\Log\AbstractBackend {
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
		$this->backend = new $className($this->objectManager->getContext());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorCallsSetterMethodsForAllSpecifiedOptions() {
		$className = get_class($this->backend);
		$backend = new $className($this->objectManager->getContext(), array('someOption' => 'someValue'));
		$this->assertSame('someValue', $backend->getSomeOption());
	}

}
?>