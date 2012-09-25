<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Cldr;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the CldrModel
 *
 */
class CldrModelTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\CldrModel
	 */
	protected $model;

	/**
	 * @return void
	 */
	public function setUp() {
		$samplePaths = array('foo', 'bar', 'baz');
		$sampleParsedFile1 = require(__DIR__ . '/../Fixtures/MockParsedCldrFile1.php');
		$sampleParsedFile2 = require(__DIR__ . '/../Fixtures/MockParsedCldrFile2.php');
		$sampleParsedFile3 = require(__DIR__ . '/../Fixtures/MockParsedCldrFile3.php');

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->once())->method('has')->with(md5('foo;bar;baz'))->will($this->returnValue(FALSE));

		$mockCldrParser = $this->getMock('TYPO3\Flow\I18n\Cldr\CldrParser');
		$mockCldrParser->expects($this->at(0))->method('getParsedData')->with('foo')->will($this->returnValue($sampleParsedFile1));
		$mockCldrParser->expects($this->at(1))->method('getParsedData')->with('bar')->will($this->returnValue($sampleParsedFile2));
		$mockCldrParser->expects($this->at(2))->method('getParsedData')->with('baz')->will($this->returnValue($sampleParsedFile3));

		$this->model = new \TYPO3\Flow\I18n\Cldr\CldrModel($samplePaths);
		$this->model->injectCache($mockCache);
		$this->model->injectParser($mockCldrParser);
		$this->model->initializeObject();
	}

	/**
	 * @test
	 */
	public function mergesMultipleFilesAndResolvesAliasesCorrectly() {
		$sampleParsedFilesMerged = require(__DIR__ . '/../Fixtures/MockParsedCldrFilesMerged.php');

		$this->assertEquals($sampleParsedFilesMerged, $this->model->getRawData('/'));
	}

	/**
	 * @test
	 */
	public function returnsRawArrayCorrectly() {
		$result = $this->model->getRawArray('dates/calendars/calendar[@type="gregorian"]/months/monthContext[@type="format"]/monthWidth[@type="abbreviated"]');
		$this->assertEquals(2, count($result));
		$this->assertEquals('jan', $result['month[@type="1"]']);
	}

	/**
	 * @test
	 */
	public function returnsElementCorrectly() {
		$result = $this->model->getElement('localeDisplayNames/localeDisplayPattern/localePattern');
		$this->assertEquals('{0} ({1})', $result);

		$result = $this->model->getElement('localeDisplayNames/variants');
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * When the path points to a leaf, getRawArray() should return FALSE.
	 *
	 * @test
	 */
	public function getRawArrayAlwaysReturnsArrayOrFalse() {
		$result = $this->model->getRawArray('localeDisplayNames/localeDisplayPattern/localePattern');
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * @test
	 */
	public function returnsNodeNameCorrectly() {
		$sampleNodeString1 = 'calendar';
		$sampleNodeString2 = 'calendar[@type="gregorian"]';

		$this->assertEquals('calendar', $this->model->getNodeName($sampleNodeString1));
		$this->assertEquals('calendar', $this->model->getNodeName($sampleNodeString2));
	}

	/**
	 * @test
	 */
	public function returnsAttributeValueCorrectly() {
		$sampleNodeString = 'dateFormatLength[@type="medium"][@alt="proposed"]';

		$this->assertEquals('medium', $this->model->getAttributeValue($sampleNodeString, 'type'));
		$this->assertEquals('proposed', $this->model->getAttributeValue($sampleNodeString, 'alt'));
		$this->assertEquals(FALSE, $this->model->getAttributeValue($sampleNodeString, 'dateFormatLength'));
	}
}

?>