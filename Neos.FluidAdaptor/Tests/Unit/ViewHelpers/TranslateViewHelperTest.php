<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Translator;
use Neos\FluidAdaptor\ViewHelpers\TranslateViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Test case for the Translate ViewHelper
 */
class TranslateViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TranslateViewHelper
     */
    protected $translateViewHelper;

    /**
     * @var Locale
     */
    protected $dummyLocale;

    /**
     * @var Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockTranslator;

    public function setUp()
    {
        parent::setUp();

        $this->translateViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\TranslateViewHelper::class, array('renderChildren'));

        $this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Neos.FluidAdaptor'));

        $this->dummyLocale = new Locale('de_DE');

        $this->mockTranslator = $this->getMockBuilder(\Neos\Flow\I18n\Translator::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->translateViewHelper, 'translator', $this->mockTranslator);

        $this->injectDependenciesIntoViewHelper($this->translateViewHelper);
    }

    /**
     * @test
     */
    public function viewHelperTranslatesByOriginalLabel()
    {
        $this->mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Untranslated Label', 'Main', 'Neos.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('Translated Label'));

        $this->translateViewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Untranslated Label'));

        $result = $this->translateViewHelper->render(null, null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperTranslatesById()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'Neos.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('Translated Label'));

        $result = $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesValueIfIdIsNotFound()
    {
        $this->translateViewHelper->expects($this->never())->method('renderChildren');

        $result = $this->translateViewHelper->render('some.label', 'Default from value', array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Default from value', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesRenderChildrenIfIdIsNotFound()
    {
        $this->translateViewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Default from renderChildren'));

        $result = $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Default from renderChildren', $result);
    }

    /**
     * @test
     */
    public function viewHelperReturnsIdWhenRenderChildrenReturnsEmptyResultIfIdIsNotFound()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'Neos.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('some.label'));

        $this->translateViewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(null));

        $result = $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('some.label', $result);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfGivenLocaleIdentifierIsInvalid()
    {
        $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'INVALIDLOCALE');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfNoPackageCouldBeResolved()
    {
        $mockRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue(null));

        $mockControllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        $this->renderingContext->setControllerContext($mockControllerContext);

        $this->injectDependenciesIntoViewHelper($this->translateViewHelper);

        $this->translateViewHelper->render('some.label');
    }

    /**
     * @return array
     */
    public function translationFallbackDataProvider()
    {
        return [
            # id & value specified with all 4 combinations of available translations for id/label
            ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'],
            ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'],
            ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Some label'],
            ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'],

            # only value specified with all 4 combinations of available translations for id/label
            ['id' => null, 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['id' => null, 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => ''],
            ['id' => null, 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['id' => null, 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => ''],

            # only id specified with all 4 combinations of available translations for id/label
            ['id' => 'some.id', 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'],
            ['id' => 'some.id', 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'],
            ['id' => 'some.id', 'value' => null, 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'some.id'],
            ['id' => 'some.id', 'value' => null, 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'some.id'],

            # neither id nor value specified with all 4 combinations of available translations for id/label
            ['id' => null, 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['id' => null, 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => ''],
            ['id' => null, 'value' => null, 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['id' => null, 'value' => null, 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => ''],
        ];
    }

    /**
     * @test
     * @dataProvider translationFallbackDataProvider
     * @param string $id
     * @param string $value
     * @param string $translatedId
     * @param string $translatedValue
     * @param string $expectedResult
     */
    public function translationFallbackTests($id, $value, $translatedId, $translatedValue, $expectedResult)
    {
        $this->mockTranslator->expects($this->any())->method('translateById', $id)->will($this->returnValue($translatedId));
        $this->mockTranslator->expects($this->any())->method('translateByOriginalLabel', $value)->will($this->returnValue($translatedValue));
        $actualResult = $this->translateViewHelper->render($id, $value);
        $this->assertSame($expectedResult, $actualResult);
    }
}
