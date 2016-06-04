<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\CurrencyViewHelper
 */
class CurrencyViewHelperTest extends UnitTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\Format\CurrencyViewHelper')->setMethods(array('renderChildren'))->getMock();
    }

    /**
     * @test
     */
    public function viewHelperRoundsFloatCorrectly()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersCurrencySign()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
        $actualResult = $this->viewHelper->render('foo');
        $this->assertEquals('123,00 foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsDecimalSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $actualResult = $this->viewHelper->render('', '|');
        $this->assertEquals('12.345|00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsThousandsSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $actualResult = $this->viewHelper->render('', ',', '|');
        $this->assertEquals('12|345,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNullValues()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(null));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNegativeAmounts()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(-123.456));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('-123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $mockNumberFormatter = $this->getMockBuilder('TYPO3\Flow\I18n\Formatter\NumberFormatter')->setMethods(array('formatCurrencyNumber'))->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->setArguments(array('forceLocale' => 'de_DE'));
        $this->viewHelper->render('EUR', '#', '*');
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new \TYPO3\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder('TYPO3\Flow\I18n\Service')->setMethods(array('getConfiguration'))->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder('TYPO3\Flow\I18n\Formatter\NumberFormatter')->setMethods(array('formatCurrencyNumber'))->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));

        $this->viewHelper->setArguments(array('forceLocale' => true));
        $this->viewHelper->render('EUR');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function viewHelperThrowsExceptionIfLocaleIsUsedWithoutExplicitCurrencySign()
    {
        $localizationConfiguration = new \TYPO3\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder('TYPO3\Flow\I18n\Service')->setMethods(array('getConfiguration'))->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $this->viewHelper->setArguments(array('forceLocale' => true));
        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $localizationConfiguration = new \TYPO3\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder('TYPO3\Flow\I18n\Service')->setMethods(array('getConfiguration'))->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder('TYPO3\Flow\I18n\Formatter\NumberFormatter')->setMethods(array('formatCurrencyNumber'))->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber')->will($this->throwException(new \TYPO3\Flow\I18n\Exception()));
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $this->viewHelper->setArguments(array('forceLocale' => true));
        $this->viewHelper->render('$');
    }
}
