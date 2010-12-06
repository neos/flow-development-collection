<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests;

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
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A base test case for functional tests
 *
 * Subclass this base class if you want to take advantage of the framework
 * capabilities, for example are in need of the object manager.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
abstract class FunctionalTestCase extends \F3\FLOW3\Tests\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Core\Bootstrap
	 */
	protected static $flow3;

	/**
	 * Initialize FLOW3
	 */
	public static function setUpBeforeClass() {
		if (!self::$flow3) {
			if (!isset($_SERVER['FLOW3_ROOTPATH'])) {
				exit('The environment variable FLOW3_ROOTPATH must be defined in order to run functional tests.');
			}
			require_once($_SERVER['FLOW3_ROOTPATH'] . 'Packages/Framework/FLOW3/Classes/Core/Bootstrap.php');

			\F3\FLOW3\Core\Bootstrap::defineConstants();

			self::$flow3 = new \F3\FLOW3\Core\Bootstrap('Testing');
			self::$flow3->initialize();
		}
	}

	/**
	 * Partially shutdown FLOW3 to save caches (e.g. Reflection Service)
	 */
	public static function tearDownAfterClass() {
		if (self::$flow3) {
			self::$flow3->getObjectManager()->get('F3\FLOW3\Reflection\ReflectionService')->shutdownObject();
		}
	}

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function runBare() {
		$this->objectManager = self::$flow3->getObjectManager();
		parent::runBare();
	}
}
?>