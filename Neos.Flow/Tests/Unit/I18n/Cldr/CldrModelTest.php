<?php

declare(strict_types=1);

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
use Neos\Flow\I18n;
use Neos\Flow\I18n\Cldr\CldrModel;
use Neos\Flow\I18n\Cldr\CldrParser;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the CldrModel
 */
final class CldrModelTest extends UnitTestCase
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

        $mockCache = $this->createMock(VariableFrontend::class);
        $mockCache->expects($this->once())->method('has')->with(md5('foo;bar;baz'))->willReturn((false));

        $mockCldrParser = $this->createMock(CldrParser::class);
        $matcher = self::exactly(3);
        $mockCldrParser->expects($matcher)->method('getParsedData')->willReturnCallback(function (...$parameters) use ($matcher, $sampleParsedFile1, $sampleParsedFile2, $sampleParsedFile3) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('foo', $parameters[0]);
                return $sampleParsedFile1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('bar', $parameters[0]);
                return $sampleParsedFile2;
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('baz', $parameters[0]);
                return $sampleParsedFile3;
            }
        });

        $this->model = new CldrModel($samplePaths);
        $this->model->injectCache($mockCache);
        $this->model->injectParser($mockCldrParser);
        $this->model->initializeObject();
    }

    #[Test]
    public function mergesMultipleFilesAndResolvesAliasesCorrectly()
    {
        $sampleParsedFilesMerged = require(__DIR__ . '/../Fixtures/MockParsedCldrFilesMerged.php');

        self::assertEquals($sampleParsedFilesMerged, $this->model->getRawData('/'));
    }

    #[Test]
    public function returnsRawArrayCorrectly()
    {
        $result = $this->model->getRawArray('dates/calendars/calendar[@type="gregorian"]/months/monthContext[@type="format"]/monthWidth[@type="abbreviated"]');
        self::assertCount(2, $result);
        self::assertEquals('jan', $result['month[@type="1"]']);
    }

    #[Test]
    public function returnsElementCorrectly()
    {
        $result = $this->model->getElement('localeDisplayNames/localeDisplayPattern/localePattern');
        self::assertEquals('{0} ({1})', $result);

        $result = $this->model->getElement('localeDisplayNames/variants');
        self::assertEquals(false, $result);
    }

    /**
     * When the path points to a leaf, getRawArray() should return false.
     */
    #[Test]
    public function getRawArrayAlwaysReturnsArrayOrFalse()
    {
        $result = $this->model->getRawArray('localeDisplayNames/localeDisplayPattern/localePattern');
        self::assertEquals(false, $result);
    }

    #[Test]
    public function returnsNodeNameCorrectly()
    {
        $sampleNodeString1 = 'calendar';
        $sampleNodeString2 = 'calendar[@type="gregorian"]';

        self::assertEquals('calendar', $this->model->getNodeName($sampleNodeString1));
        self::assertEquals('calendar', $this->model->getNodeName($sampleNodeString2));
    }

    #[Test]
    public function returnsAttributeValueCorrectly()
    {
        $sampleNodeString = 'dateFormatLength[@type="medium"][@alt="proposed"]';

        self::assertEquals('medium', $this->model->getAttributeValue($sampleNodeString, 'type'));
        self::assertEquals('proposed', $this->model->getAttributeValue($sampleNodeString, 'alt'));
        self::assertEquals(false, $this->model->getAttributeValue($sampleNodeString, 'dateFormatLength'));
    }
}
