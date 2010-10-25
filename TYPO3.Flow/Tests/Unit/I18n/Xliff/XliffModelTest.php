<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\I18n\Xliff;

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
 * Testcase for the XliffModel
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class XliffModelTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Xliff\XliffModel
	 */
	protected $model;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$mockFilename = 'foo';
		$mockParsedData = require(__DIR__ . '/../Fixtures/MockParsedXliffData.php');

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->with(md5($mockFilename))->will($this->returnValue(FALSE));

		$mockXliffParser = $this->getMock('F3\FLOW3\I18n\Xliff\XliffParser');
		$mockXliffParser->expects($this->once())->method('getParsedData')->with($mockFilename)->will($this->returnValue($mockParsedData));

		$this->model = new \F3\FLOW3\I18n\Xliff\XliffModel($mockFilename);
		$this->model->injectCache($mockCache);
		$this->model->injectParser($mockXliffParser);
		$this->model->initializeObject();
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function targetIsReturnedCorretlyWhenSourceProvided() {
		$result = $this->model->getTargetBySource('Source string');
		$this->assertEquals('Translated string', $result);

		$result = $this->model->getTargetBySource('Source singular', 0);
		$this->assertEquals('Translated singular', $result);

		$result = $this->model->getTargetBySource('Source singular', 2);
		$this->assertEquals('Translated plural 2', $result);

		$result = $this->model->getTargetBySource('Not existing label');
		$this->assertFalse($result);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function targetIsReturnedCorrectlyWhenIdProvided() {
		$result = $this->model->getTargetByTransUnitId('key1');
		$this->assertEquals('Translated string', $result);

		$result = $this->model->getTargetByTransUnitId('key2', 1);
		$this->assertEquals('Translated plural 1', $result);

		$result = $this->model->getTargetByTransUnitId('not.existing');
		$this->assertFalse($result);
	}
}

?>