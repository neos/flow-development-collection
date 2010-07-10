<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\Cldr;

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
 * Testcase for the CldrParser
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CldrParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function parsesCorrectly() {
		$mockFilenamePath = __DIR__ . '/../Fixtures/MockCldrData.xml';
		$mockParsedData = require(__DIR__ . '/../Fixtures/MockParsedCldrData.php');

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->once())->method('has')->with($mockFilenamePath)->will($this->returnValue(FALSE));

		$parser = new \F3\FLOW3\Locale\Cldr\CldrParser();
		$parser->injectCache($mockCache);
		
		$result = $parser->getParsedData($mockFilenamePath);
		$this->assertEquals($mockParsedData, $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getValueOfAttributeWorks() {
		$mockAttributesString = 'foo="bar" foo2="bar2"';

		$this->assertEquals('bar', \F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttribute($mockAttributesString, 1));
		$this->assertEquals('bar2', \F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttribute($mockAttributesString, 2));
		$this->assertEquals(FALSE, \F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttribute($mockAttributesString, 4));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getValueOfAttributeByNameWorks() {
		$mockAttributesString = 'source="locale" path="../eraAbbr"';

		$this->assertEquals('locale', \F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttributeByName($mockAttributesString, 'source'));
		$this->assertEquals('../eraAbbr', \F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttributeByName($mockAttributesString, 'path'));
		$this->assertEquals(FALSE, \F3\FLOW3\Locale\Cldr\CldrParser::getValueOfAttributeByName($mockAttributesString, 'notavailable'));
	}
}

?>
