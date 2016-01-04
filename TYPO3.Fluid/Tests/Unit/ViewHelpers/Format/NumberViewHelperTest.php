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

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\NumberViewHelper
 */
class NumberViewHelperTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function formatNumberDefaultsToEnglishNotationWithTwoDecimals()
    {
        $viewHelper = $this->getMock(\TYPO3\Fluid\ViewHelpers\Format\NumberViewHelper::class, array('renderChildren'));
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
        $actualResult = $viewHelper->render();
        $this->assertEquals('3,333.33', $actualResult);
    }

    /**
     * @test
     */
    public function formatNumberWithDecimalsDecimalPointAndSeparator()
    {
        $viewHelper = $this->getMock(\TYPO3\Fluid\ViewHelpers\Format\NumberViewHelper::class, array('renderChildren'));
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
        $actualResult = $viewHelper->render(3, ',', '.');
        $this->assertEquals('3.333,333', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $numberFormatterMock = $this->getMock(\TYPO3\Flow\I18n\Formatter\NumberFormatter::class, array('formatDecimalNumber'));
        $numberFormatterMock->expects($this->once())->method('formatDecimalNumber');

        $viewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Format\NumberViewHelper::class, array('renderChildren'));
        $viewHelper->_set('numberFormatter', $numberFormatterMock);
        $viewHelper->setArguments(array('forceLocale' => 'de_DE'));
        $viewHelper->render(2, '#', '*');
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Format\NumberViewHelper::class, array('renderChildren'));

        $localizationConfiguration = new \TYPO3\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMock(\TYPO3\Flow\I18n\Service::class, array('getConfiguration'));
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMock(\TYPO3\Flow\I18n\Formatter\NumberFormatter::class, array('formatDecimalNumber'));
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber');
        $this->inject($viewHelper, 'numberFormatter', $mockNumberFormatter);

        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $viewHelper->setArguments(array('forceLocale' => true));
        $viewHelper->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Format\NumberViewHelper::class, array('renderChildren'));

        $localizationConfiguration = new \TYPO3\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMock(\TYPO3\Flow\I18n\Service::class, array('getConfiguration'));
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMock(\TYPO3\Flow\I18n\Formatter\NumberFormatter::class, array('formatDecimalNumber'));
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber')->will($this->throwException(new \TYPO3\Flow\I18n\Exception()));
        $this->inject($viewHelper, 'numberFormatter', $mockNumberFormatter);

        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $viewHelper->setArguments(array('forceLocale' => true));
        $viewHelper->render();
    }
}
