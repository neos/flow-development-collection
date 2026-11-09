<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\Flow\I18n;
use Neos\Flow\I18n\Configuration;
use Neos\Flow\I18n\Formatter\DatetimeFormatter;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for date view helper \Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper
 */
final class DateViewHelperTest extends ViewHelperBaseTestcase
{
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(DateViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
    }

    #[Test]
    public function viewHelperFormatsDateCorrectly()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime('1980-12-13')]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-13', $actualResult);
    }

    #[Test]
    public function viewHelperFormatsDateStringCorrectly()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => '1980-12-13']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-13', $actualResult);
    }

    #[Test]
    public function viewHelperRespectsCustomFormat()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime('1980-02-01'), 'format' => 'd.m.Y']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('01.02.1980', $actualResult);
    }

    #[Test]
    public function viewHelperReturnsEmptyStringIfNULLIsGiven()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((null));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('', $actualResult);
    }

    #[Test]
    public function viewHelperThrowsExceptionIfDateStringCantBeParsed()
    {
        $this->expectException(Exception::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => 'foo']);
        $actualResult = $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((new \DateTime('1980-12-13')));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-13', $actualResult);
    }

    #[Test]
    public function dateArgumentHasPriorityOverChildNodes()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => '1980-12-12']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-12', $actualResult);
    }

    #[Test]
    public function viewHelperThrowsExceptionIfInvalidLocaleIdentifierIsGiven()
    {
        $this->expectException(InvalidVariableException::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime(), 'forceLocale' => '123-not-existing-locale']);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperCallsDateTimeFormatterWithCorrectlyBuiltConfigurationArguments()
    {
        $dateTime = new \DateTime();
        $locale = new Locale('de');
        $formatType = 'date';

        $mockDatetimeFormatter = $this->getMockBuilder(DatetimeFormatter::class)->onlyMethods(['format'])->getMock();
        $mockDatetimeFormatter
            ->expects($this->once())
            ->method('format')
            ->with($dateTime, $locale, [0 => $formatType, 1 => null]);
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments(
            $this->viewHelper,
            ['date' => $dateTime, 'format' => null, 'localeFormatType' => $formatType, 'forceLocale' => $locale]
        );
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(Service::class)->onlyMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->willReturn(($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(DatetimeFormatter::class)->onlyMethods(['format'])->getMock();
        $mockDatetimeFormatter->expects($this->once())->method('format');
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime(), 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $this->expectException(Exception::class);
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(Service::class)->onlyMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->willReturn(($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(DatetimeFormatter::class)->onlyMethods(['format'])->getMock();
        $mockDatetimeFormatter->expects($this->once())->method('format')->willThrowException(new I18n\Exception());
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime(), 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperCallsDateTimeFormatterWithCustomFormat()
    {
        $dateTime = new \DateTime();
        $locale = new Locale('de');
        $cldrFormatString = 'MM';

        $mockDatetimeFormatter = $this->getMockBuilder(DatetimeFormatter::class)->onlyMethods(['formatDateTimeWithCustomPattern'])->getMock();
        $mockDatetimeFormatter
            ->expects($this->once())
            ->method('formatDateTimeWithCustomPattern')
            ->with($dateTime, $cldrFormatString, $locale);
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments(
            $this->viewHelper,
            ['date' => $dateTime, 'format' => null, 'localeFormatType' => null, 'localeFormatLength' => null, 'cldrFormat' => $cldrFormatString, 'forceLocale' => $locale]
        );
        $this->viewHelper->render();
    }
}
