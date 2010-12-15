<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Property;

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
 * Testcase for Mapping Results
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class MappingResultsTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function addErrorSetsErrorForProperty() {
		$mockError = $this->getMock('F3\FLOW3\Validation\PropertyError', array('dummy'), array('foo'));
		
		$mappingResults = $this->getAccessibleMock('F3\FLOW3\Property\MappingResults', array('dummy'), array(), '', FALSE);
		$mappingResults->addError($mockError, 'foo');
		
		$this->assertEquals($mockError, $mappingResults->getErrorForProperty('foo'));
	}
}
?>