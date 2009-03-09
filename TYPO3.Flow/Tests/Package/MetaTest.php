<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package;

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
 * @package FLOW3
 * @subpackage Package
 * @version $Id:F3\FLOW3\Package\Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the meta class
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class MetaDataTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function settingInvalidPackageStateThrowsInvalidArgumentException() {
		$this->markTestIncomplete();
#		$meta = new \F3\FLOW3\Package\MetaData('YetAnotherTestPackage');
#		$meta->setState('Invalid');
	}
}
?>