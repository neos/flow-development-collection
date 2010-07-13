<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Formatter;

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
 * Testcase for the NumberFormatter
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberFormatterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $dummyLocale;

	/**
	 * @var float
	 */
	protected $dummyNumber;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->dummyLocale = new \F3\FLOW3\I18n\Locale('en_GB');
		$this->dummyNumber = 123.456;
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatWorks() {
		$mockReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\NumbersReader');
		$mockReader->expects($this->at(0))->method('formatDecimalNumber')->with($this->dummyNumber, $this->dummyLocale, 'default')->will($this->returnValue('bar1'));
		$mockReader->expects($this->at(1))->method('formatPercentNumber')->with($this->dummyNumber, $this->dummyLocale, 'default')->will($this->returnValue('bar2'));

		$formatter = new \F3\FLOW3\I18n\Formatter\NumberFormatter();
		$formatter->injectNumbersReader($mockReader);

		$result = $formatter->format($this->dummyNumber, $this->dummyLocale);
		$this->assertEquals('bar1', $result);

		$result = $formatter->format($this->dummyNumber, $this->dummyLocale, array('percent'));
		$this->assertEquals('bar2', $result);
	}
}

?>
