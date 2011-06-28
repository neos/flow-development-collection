<?php
namespace TYPO3\Kickstart\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 package "Kickstart".                  *
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

require_once(__DIR__ . '/../../../Resources/Private/PHP/Sho_Inflect.php');

/**
 * Testcase for the Inflector
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class InflectorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {
	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function humanizeCamelCaseConvertsCamelCaseToSpacesAndUppercasesFirstWord() {
		$inflector = new \TYPO3\Kickstart\Utility\Inflector();
		$humanized = $inflector->humanizeCamelCase('BlogAuthor');
		$this->assertEquals('Blog author', $humanized);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function pluralizePluralizesWords() {
		$inflector = new \TYPO3\Kickstart\Utility\Inflector();
		$this->assertEquals('boxes', $inflector->pluralize('box'));
		$this->assertEquals('foos', $inflector->pluralize('foo'));
	}
}
?>