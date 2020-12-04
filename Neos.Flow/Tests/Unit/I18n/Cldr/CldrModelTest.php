<?php
namespace Neos\Flow\Tests\Unit\I18n\Cldr;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the CldrModel
 */
class CldrModelTest extends UnitTestCase
{
    /**
     * @var I18n\Cldr\CldrModel
     */
    protected $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $samplePaths = ['foo', 'bar', 'baz'];
        $sampleParsedFile1 = require(__DIR__ . '/../Fixtures/MockParsedCldrFile1.php');
        $sampleParsedFile2 = require(__DIR__ . '/../Fixtures/MockParsedCldrFile2.php');
        $sampleParsedFile3 = require(__DIR__ . '/../Fixtures/MockParsedCldrFile3.php');

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects(self::once())->method('has')->with(md5('foo;bar;baz'))->will(self::returnValue(false));

        $mockCldrParser = $this->createMock(I18n\Cldr\CldrParser::class);
        $mockCldrParser->expects(self::exactly(3))->method('getParsedData')->withConsecutive(['foo'], ['bar'], ['baz'])->willReturnOnConsecutiveCalls($sampleParsedFile1, $sampleParsedFile2, $sampleParsedFile3);

        $this->model = new I18n\Cldr\CldrModel($samplePaths);
        $this->model->injectCache($mockCache);
        $this->model->injectParser($mockCldrParser);
        $this->model->initializeObject();
    }

    /**
     * @test
     */
    public function mergesMultipleFilesAndResolvesAliasesCorrectly()
    {
        $sampleParsedFilesMerged = require(__DIR__ . '/../Fixtures/MockParsedCldrFilesMerged.php');

        self::assertEquals($sampleParsedFilesMerged, $this->model->getRawData('/'));
    }

    /**
     * @test
     */
    public function returnsRawArrayCorrectly()
    {
        $result = $this->model->getRawArray('dates/calendars/calendar[@type="gregorian"]/months/monthContext[@type="format"]/monthWidth[@type="abbreviated"]');
        self::assertEquals(2, count($result));
        self::assertEquals('jan', $result['month[@type="1"]']);
    }

    /**
     * @test
     */
    public function returnsElementCorrectly()
    {
        $result = $this->model->getElement('localeDisplayNames/localeDisplayPattern/localePattern');
        self::assertEquals('{0} ({1})', $result);

        $result = $this->model->getElement('localeDisplayNames/variants');
        self::assertEquals(false, $result);
    }

    /**
     * When the path points to a leaf, getRawArray() should return false.
     *
     * @test
     */
    public function getRawArrayAlwaysReturnsArrayOrFalse()
    {
        $result = $this->model->getRawArray('localeDisplayNames/localeDisplayPattern/localePattern');
        self::assertEquals(false, $result);
    }

    /**
     * @test
     */
    public function returnsNodeNameCorrectly()
    {
        $sampleNodeString1 = 'calendar';
        $sampleNodeString2 = 'calendar[@type="gregorian"]';

        self::assertEquals('calendar', $this->model->getNodeName($sampleNodeString1));
        self::assertEquals('calendar', $this->model->getNodeName($sampleNodeString2));
    }

    /**
     * @test
     */
    public function returnsAttributeValueCorrectly()
    {
        $sampleNodeString = 'dateFormatLength[@type="medium"][@alt="proposed"]';

        self::assertEquals('medium', $this->model->getAttributeValue($sampleNodeString, 'type'));
        self::assertEquals('proposed', $this->model->getAttributeValue($sampleNodeString, 'alt'));
        self::assertEquals(false, $this->model->getAttributeValue($sampleNodeString, 'dateFormatLength'));
    }
}
