<?php
namespace TYPO3\FLOW3\Tests\Unit\Log\Backend;

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
 * Testcase for the abstract log backend
 *
 */
class AbstractBackendTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Log\Backend\AbstractBackend
	 */
	protected $backendClassName;

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->backendClassName = 'ConcreteBackend_' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $this->backendClassName . ' extends \TYPO3\FLOW3\Log\Backend\AbstractBackend {
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorCallsSetterMethodsForAllSpecifiedOptions() {
		$className = $this->backendClassName;
		$backend = new $className(array('someOption' => 'someValue'));
		$this->assertSame('someValue', $backend->getSomeOption());
	}

}
?>