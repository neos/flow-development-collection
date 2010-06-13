<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\CLDR;

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
 * Testcase for the CLDRModel
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CLDRModelTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Locale\CLDR\CLDRModel
	 */
	protected $model;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$mockFilenamePath = __DIR__ . '/../Fixtures/MockCLDRData.xml';

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->once())->method('has')->with($mockFilenamePath)->will($this->returnValue(FALSE));

		$this->model = new \F3\FLOW3\Locale\CLDR\CLDRModel();
		$this->model->injectCache($mockCache);
		$this->model->initializeObject($mockFilenamePath);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getRawArrayWorks() {
		$result = $this->model->getRawArray('dates/calendars/calendar/type="gregorian"/dateFormats/dateFormatLength');
		$this->assertEquals(4, count($result));
		$this->assertEquals(TRUE, isset($result['type="full"']));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getOneElementWorks() {
		$result = $this->model->getOneElement('dates/calendars/calendar/type="gregorian"/dateFormats/dateFormatLength/type="full"/dateFormat/pattern');
		$this->assertEquals('EEEE, d MMMM y', $result);

		$result = $this->model->getOneElement('dates/calendars/calendar/type="gregorian"/dateFormats/dateFormatLength/type="full"/dateFormat');
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function aliasesAreResolvedCorrectly() {
		$result = $this->model->getRawArray('dates/calendars/calendar/type="gregorian"/dateFormats/dateFormatLength/type="short"/dateFormat/pattern');
		$this->assertEquals('dd-MM-yyyy', $result['']);
		$this->assertEquals('d MMM y', $result['alt="proposed-x1001" draft="unconfirmed"']);

		$result = $this->model->getOneElement('dates/calendars/calendar/type="buddhist"/dateFormats/dateFormatLength/type="full"/dateFormat/pattern');
		$this->assertEquals('EEEE, d MMMM y', $result);
	}
}

?>