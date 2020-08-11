<?php
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

use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\Flow\I18n;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for date view helper \Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper
 */
class DateViewHelperTest extends ViewHelperBaseTestcase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(['renderChildren'])->getMock();
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateCorrectly()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime('1980-12-13')]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateStringCorrectly()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => '1980-12-13']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCustomFormat()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime('1980-02-01'), 'format' => 'd.m.Y']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('01.02.1980', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsEmptyStringIfNULLIsGiven()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(null));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionIfDateStringCantBeParsed()
    {
        $this->expectException(Exception::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => 'foo']);
        $actualResult = $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(new \DateTime('1980-12-13')));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function dateArgumentHasPriorityOverChildNodes()
    {
        $this->viewHelper->expects(self::never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => '1980-12-12']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('1980-12-12', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionIfInvalidLocaleIdentifierIsGiven()
    {
        $this->expectException(Exception\InvalidVariableException::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime(), 'forceLocale' => '123-not-existing-locale']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperCallsDateTimeFormatterWithCorrectlyBuiltConfigurationArguments()
    {
        $dateTime = new \DateTime();
        $locale = new I18n\Locale('de');
        $formatType = 'date';

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(['format'])->getMock();
        $mockDatetimeFormatter
            ->expects(self::once())
            ->method('format')
            ->with($dateTime, $locale, [0 => $formatType, 1 => null]);
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments(
            $this->viewHelper,
            ['date' => $dateTime, 'format' => null, 'localeFormatType' => $formatType, 'forceLocale' => $locale]
        );
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects(self::once())->method('getConfiguration')->will(self::returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(['format'])->getMock();
        $mockDatetimeFormatter->expects(self::once())->method('format');
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime(), 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $this->expectException(Exception::class);
        $localizationConfiguration = new I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects(self::once())->method('getConfiguration')->will(self::returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(['format'])->getMock();
        $mockDatetimeFormatter->expects(self::once())->method('format')->will(self::throwException(new I18n\Exception()));
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime(), 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperCallsDateTimeFormatterWithCustomFormat()
    {
        $dateTime = new \DateTime();
        $locale = new I18n\Locale('de');
        $cldrFormatString = 'MM';

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(['formatDateTimeWithCustomPattern'])->getMock();
        $mockDatetimeFormatter
            ->expects(self::once())
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
