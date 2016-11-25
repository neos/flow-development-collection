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

use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\ViewHelpers\Format;
use Neos\Flow\I18n;

/**
 * Test for date view helper \Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper
 */
class DateViewHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function viewHelperFormatsDateCorrectly()
    {
        $viewHelper = new Format\DateViewHelper();
        $actualResult = $viewHelper->render(new \DateTime('1980-12-13'));
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateStringCorrectly()
    {
        $viewHelper = new Format\DateViewHelper();
        $actualResult = $viewHelper->render('1980-12-13');
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCustomFormat()
    {
        $viewHelper = new Format\DateViewHelper();
        $actualResult = $viewHelper->render(new \DateTime('1980-02-01'), 'd.m.Y');
        $this->assertEquals('01.02.1980', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsEmptyStringIfNULLIsGiven()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(null));
        $actualResult = $viewHelper->render();
        $this->assertEquals('', $actualResult);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function viewHelperThrowsExceptionIfDateStringCantBeParsed()
    {
        $viewHelper = new Format\DateViewHelper();
        $viewHelper->render('foo');
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(new \DateTime('1980-12-13')));
        $actualResult = $viewHelper->render();
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function dateArgumentHasPriorityOverChildNodes()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();
        $viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $viewHelper->render('1980-12-12');
        $this->assertEquals('1980-12-12', $actualResult);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function viewHelperThrowsExceptionIfInvalidLocaleIdentifierIsGiven()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();
        $viewHelper->setArguments(array('forceLocale' => '123-not-existing-locale'));
        $viewHelper->render(new \DateTime());
    }

    /**
     * @test
     */
    public function viewHelperCallsDateTimeFormatterWithCorrectlyBuiltConfigurationArguments()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();

        $dateTime = new \DateTime();
        $locale = new I18n\Locale('de');
        $formatType = 'date';

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(array('format'))->getMock();
        $mockDatetimeFormatter
            ->expects($this->once())
            ->method('format')
            ->with($dateTime, $locale, array(0 => $formatType, 1 => null));
        $this->inject($viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $viewHelper->setArguments(array('forceLocale' => $locale));
        $viewHelper->render($dateTime, null, $formatType);
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();

        $localizationConfiguration = new I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(array('getConfiguration'))->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(array('format'))->getMock();
        $mockDatetimeFormatter->expects($this->once())->method('format');
        $this->inject($viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $viewHelper->setArguments(array('forceLocale' => true));
        $viewHelper->render(new \DateTime());
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();

        $localizationConfiguration = new I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(array('getConfiguration'))->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($viewHelper, 'localizationService', $mockLocalizationService);

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(array('format'))->getMock();
        $mockDatetimeFormatter->expects($this->once())->method('format')->will($this->throwException(new I18n\Exception()));
        $this->inject($viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $viewHelper->setArguments(array('forceLocale' => true));
        $viewHelper->render(new \DateTime());
    }

    /**
     * @test
     */
    public function viewHelperCallsDateTimeFormatterWithCustomFormat()
    {
        /** @var $viewHelper Format\DateViewHelper|\PHPUnit_Framework_MockObject_MockObject */
        $viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\DateViewHelper::class)->setMethods(array('renderChildren'))->getMock();

        $dateTime = new \DateTime();
        $locale = new I18n\Locale('de');
        $cldrFormatString = 'MM';

        $mockDatetimeFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class)->setMethods(array('formatDateTimeWithCustomPattern'))->getMock();
        $mockDatetimeFormatter
            ->expects($this->once())
            ->method('formatDateTimeWithCustomPattern')
            ->with($dateTime, $cldrFormatString, $locale);
        $this->inject($viewHelper, 'datetimeFormatter', $mockDatetimeFormatter);

        $viewHelper->setArguments(array('forceLocale' => $locale));
        $viewHelper->render($dateTime, null, null, null, $cldrFormatString);
    }
}
