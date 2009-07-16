<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Backend;

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
 * Testcase for the abstract cache backend
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractBackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Cache\Backend\AbstractBackend
	 */
	protected $backend;

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$className = uniqid('ConcreteBackend_');
		eval('
			class ' . $className. ' extends \F3\FLOW3\Cache\Backend\AbstractBackend {
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
		$this->backend = new $className('Testing');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorCallsSetterMethodsForAllSpecifiedOptions() {
		$className = get_class($this->backend);
		$backend = new $className('Testing', array('someOption' => 'someValue'));
		$this->assertSame('someValue', $backend->getSomeOption());
	}
}
?>