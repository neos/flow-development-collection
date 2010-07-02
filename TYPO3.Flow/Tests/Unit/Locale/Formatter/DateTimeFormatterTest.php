<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\Formatter;

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
 * Testcase for the DateTimeFormatter
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DateTimeFormatterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Locale\Locale
	 */
	protected $dummyLocale;

	/**
	 * @var \DateTime
	 */
	protected $dummyDateTime;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->dummyLocale = new \F3\FLOW3\Locale\Locale('en_GB');
		$this->dummyDateTime = new \DateTime('now');
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatWorks() {
		$mockReader = $this->getMock('F3\FLOW3\Locale\Cldr\Reader\DatesReader');
		$mockReader->expects($this->at(0))->method('formatDateTime')->with($this->dummyDateTime, $this->dummyLocale, 'default')->will($this->returnValue('bar1'));
		$mockReader->expects($this->at(1))->method('formatDate')->with($this->dummyDateTime, $this->dummyLocale, 'default')->will($this->returnValue('bar2'));
		$mockReader->expects($this->at(2))->method('formatTime')->with($this->dummyDateTime, $this->dummyLocale, 'full')->will($this->returnValue('bar3'));

		$formatter = new \F3\FLOW3\Locale\Formatter\DateTimeFormatter();
		$formatter->injectDatesReader($mockReader);

		$result = $formatter->format($this->dummyDateTime, $this->dummyLocale);
		$this->assertEquals('bar1', $result);

		$result = $formatter->format($this->dummyDateTime, $this->dummyLocale, array('date'));
		$this->assertEquals('bar2', $result);

		$result = $formatter->format($this->dummyDateTime, $this->dummyLocale, array('time', 'full'));
		$this->assertEquals('bar3', $result);
	}
}

?>
