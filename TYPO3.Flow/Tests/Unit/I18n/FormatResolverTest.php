<?php
namespace F3\FLOW3\Tests\Unit\I18n;

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
 * Testcase for the FormatResolver
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FormatResolverTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->sampleLocale = new \F3\FLOW3\I18n\Locale('en_GB');
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function placeholdersAreResolvedCorrectly() {
		$mockNumberFormatter = $this->getMock('F3\FLOW3\I18n\Formatter\NumberFormatter');
		$mockNumberFormatter->expects($this->at(0))->method('format')->with(1, $this->sampleLocale)->will($this->returnValue('1.0'));
		$mockNumberFormatter->expects($this->at(1))->method('format')->with(2, $this->sampleLocale, array('percent'))->will($this->returnValue('200%'));

		$formatResolver = $this->getAccessibleMock('F3\FLOW3\I18n\FormatResolver', array('getFormatter'));
		$formatResolver->expects($this->exactly(2))->method('getFormatter')->with('number')->will($this->returnValue($mockNumberFormatter));

		$result = $formatResolver->resolvePlaceholders('Foo {0,number}, bar {1,number,percent}', array(1, 2), $this->sampleLocale);
		$this->assertEquals('Foo 1.0, bar 200%', $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsStringCastedArgumentWhenFormatterNameIsNotSet() {
		$formatResolver = new \F3\FLOW3\I18n\FormatResolver();
		$result = $formatResolver->resolvePlaceholders('{0}', array(123), $this->sampleLocale);
		$this->assertEquals('123', $result);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\I18n\Exception\InvalidFormatPlaceholderException
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function throwsExceptionWhenInvalidPlaceholderEncountered() {
		$formatResolver = new \F3\FLOW3\I18n\FormatResolver();
		$formatResolver->resolvePlaceholders('{0,damaged {1}', array(), $this->sampleLocale);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\I18n\Exception\IndexOutOfBoundsException
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function throwsExceptionWhenInsufficientNumberOfArgumentsProvided() {
		$formatResolver = new \F3\FLOW3\I18n\FormatResolver();
		$formatResolver->resolvePlaceholders('{0}', array(), $this->sampleLocale);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\I18n\Exception\UnknownFormatterException
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function throwsExceptionWhenFormatterDoesNotExist() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get', 'F3\FLOW3\I18n\Formatter\FooFormatter')->will($this->throwException(new \F3\FLOW3\I18n\Exception\UnknownFormatterException()));

		$formatResolver = new \F3\FLOW3\I18n\FormatResolver();
		$formatResolver->injectObjectManager($mockObjectManager);

		$formatResolver->resolvePlaceholders('{0,foo}', array(123), $this->sampleLocale);
	}
}

?>