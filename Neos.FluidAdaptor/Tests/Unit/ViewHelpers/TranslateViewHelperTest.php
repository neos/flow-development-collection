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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\ViewHelpers\TranslateViewHelper;

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
     * @var Translator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockTranslator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translateViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\TranslateViewHelper::class, ['renderChildren']);

        $this->request->expects(self::any())->method('getControllerPackageKey')->will(self::returnValue('Neos.FluidAdaptor'));

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
        $this->mockTranslator->expects(self::once())->method('translateByOriginalLabel', 'Untranslated Label', 'Main', 'Neos.Flow', [], null, $this->dummyLocale)->will(self::returnValue('Translated Label'));

        $this->translateViewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Untranslated Label'));
        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => null, 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperTranslatesById()
    {
        $this->mockTranslator->expects(self::once())->method('translateById', 'some.label', 'Main', 'Neos.Flow', [], null, $this->dummyLocale)->will(self::returnValue('Translated Label'));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesValueIfIdIsNotFound()
    {
        $this->translateViewHelper->expects(self::never())->method('renderChildren');

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => 'Default from value', 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Default from value', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesRenderChildrenIfIdIsNotFound()
    {
        $this->translateViewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Default from renderChildren'));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Default from renderChildren', $result);
    }

    /**
     * @test
     */
    public function viewHelperReturnsIdWhenRenderChildrenReturnsEmptyResultIfIdIsNotFound()
    {
        $this->mockTranslator->expects(self::once())->method('translateById', 'some.label', 'Main', 'Neos.Flow', [], null, $this->dummyLocale)->will(self::returnValue('some.label'));

        $this->translateViewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(null));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('some.label', $result);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfGivenLocaleIdentifierIsInvalid()
    {
        $this->expectException(Exception::class);
        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'INVALIDLOCALE']);
        $this->translateViewHelper->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfNoPackageCouldBeResolved()
    {
        $this->expectException(Exception::class);
        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getControllerPackageKey')->willReturn('');

        $mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $mockControllerContext->method('getRequest')->willReturn($mockRequest);

        $this->renderingContext->setControllerContext($mockControllerContext);

        $this->injectDependenciesIntoViewHelper($this->translateViewHelper);

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label']);
        $this->translateViewHelper->render();
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
        $this->mockTranslator->expects(self::any())->method('translateById', $id)->will(self::returnValue($translatedId));
        $this->mockTranslator->expects(self::any())->method('translateByOriginalLabel', $value)->will(self::returnValue($translatedValue));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => $id, 'value' => $value]);
        $actualResult = $this->translateViewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }
}
