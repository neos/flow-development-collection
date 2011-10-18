<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n\Xliff;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the XliffModel
 *
 */
class XliffModelTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\Xliff\XliffModel
	 */
	protected $model;

	/**
	 * @return void
	 */
	public function setUp() {
		$mockFilename = 'foo';
		$mockParsedData = require(__DIR__ . '/../Fixtures/MockParsedXliffData.php');

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->with(md5($mockFilename))->will($this->returnValue(FALSE));

		$mockXliffParser = $this->getMock('TYPO3\FLOW3\I18n\Xliff\XliffParser');
		$mockXliffParser->expects($this->once())->method('getParsedData')->with($mockFilename)->will($this->returnValue($mockParsedData));

		$this->model = new \TYPO3\FLOW3\I18n\Xliff\XliffModel($mockFilename);
		$this->model->injectCache($mockCache);
		$this->model->injectParser($mockXliffParser);
		$this->model->initializeObject();
	}

	/**
	 * @test
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