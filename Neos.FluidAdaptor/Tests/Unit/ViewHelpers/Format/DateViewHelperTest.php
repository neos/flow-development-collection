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

use Neos\Flow\I18n;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for date view helper \Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper
 */
class DateViewHelperTest extends ViewHelperBaseTestcase
{
    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(['renderChildren', 'registerRenderMethodArguments'])->getMock();
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateCorrectly()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime('1980-12-13')]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateStringCorrectly()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => '1980-12-13']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCustomFormat()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime('1980-02-01'), 'format' => 'd.m.Y']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('01.02.1980', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsEmptyStringIfNULLIsGiven()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(null));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('', $actualResult);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function viewHelperThrowsExceptionIfDateStringCantBeParsed()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => 'foo']);
        $actualResult = $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(new \DateTime('1980-12-13')));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function dateArgumentHasPriorityOverChildNodes()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => '1980-12-12']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('1980-12-12', $actualResult);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function viewHelperThrowsExceptionIfInvalidLocaleIdentifierIsGiven()
    {
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
            ->expects($this->once())
            ->method('format')
            ->with($dateTime, $locale, [0 => $formatType, 1 => null]);
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper,
            ['date' => $dateTime, 'format' => null, 'localeFormatType' => $formatType, 'forceLocale' => $locale]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(['format'])->getMock();
        $mockDatetimeFormatter->expects($this->once())->method('format');
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['date' => new \DateTime(), 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $localizationConfiguration = new I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(['format'])->getMock();
        $mockDatetimeFormatter->expects($this->once())->method('format')->will($this->throwException(new I18n\Exception()));
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
            ->expects($this->once())
            ->method('formatDateTimeWithCustomPattern')
            ->with($dateTime, $cldrFormatString, $locale);
        $this->inject($this->viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper,
            ['date' => $dateTime, 'format' => null, 'localeFormatType' => null, 'localeFormatLength' => null, 'cldrFormat' => $cldrFormatString, 'forceLocale' => $locale]);
        $this->viewHelper->render();
    }
}
