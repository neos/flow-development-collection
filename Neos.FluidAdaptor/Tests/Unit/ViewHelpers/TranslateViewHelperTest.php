<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Test case for the Translate ViewHelper
 */
final class TranslateViewHelperTest extends ViewHelperBaseTestcase
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
     * @var Translator|MockObject
     */
    protected $mockTranslator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translateViewHelper = $this->getAccessibleMock(TranslateViewHelper::class, ['renderChildren']);

        $this->request->method('getControllerPackageKey')->willReturn(('Neos.FluidAdaptor'));

        $this->dummyLocale = new Locale('de_DE');

        $this->mockTranslator = $this->createMock(Translator::class);
        $this->inject($this->translateViewHelper, 'translator', $this->mockTranslator);

        $this->injectDependenciesIntoViewHelper($this->translateViewHelper);
    }

    #[Test]
    public function viewHelperTranslatesByOriginalLabel()
    {
        $this->mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Untranslated Label', 'Main', 'Neos.Flow', [], null, $this->dummyLocale)->willReturn(('Translated Label'));

        $this->translateViewHelper->expects($this->once())->method('renderChildren')->willReturn(('Untranslated Label'));
        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => null, 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Translated Label', $result);
    }

    #[Test]
    public function viewHelperTranslatesById()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'Neos.Flow', [], null, $this->dummyLocale)->willReturn(('Translated Label'));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Translated Label', $result);
    }

    #[Test]
    public function viewHelperUsesValueIfIdIsNotFound()
    {
        $this->translateViewHelper->expects($this->never())->method('renderChildren');

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => 'Default from value', 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Default from value', $result);
    }

    #[Test]
    public function viewHelperUsesRenderChildrenIfIdIsNotFound()
    {
        $this->translateViewHelper->expects($this->once())->method('renderChildren')->willReturn(('Default from renderChildren'));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('Default from renderChildren', $result);
    }

    #[Test]
    public function viewHelperReturnsIdWhenRenderChildrenReturnsEmptyResultIfIdIsNotFound()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'Neos.Flow', [], null, $this->dummyLocale)->willReturn(('some.label'));

        $this->translateViewHelper->expects($this->once())->method('renderChildren')->willReturn((null));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'de_DE']);
        $result = $this->translateViewHelper->render();
        self::assertEquals('some.label', $result);
    }

    #[Test]
    public function renderThrowsExceptionIfGivenLocaleIdentifierIsInvalid()
    {
        $this->expectException(Exception::class);
        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label', 'value' => null, 'arguments' => [], 'source' => 'Main', 'package' => null, 'quantity' => null, 'locale' => 'INVALIDLOCALE']);
        $this->translateViewHelper->render();
    }

    #[Test]
    public function renderThrowsExceptionIfNoPackageCouldBeResolved()
    {
        $this->expectException(Exception::class);
        $mockRequest = $this->createMock(ActionRequest::class);
        $mockRequest->method('getControllerPackageKey')->willReturn('');

        $mockControllerContext = $this->createMock(ControllerContext::class);
        $mockControllerContext->method('getRequest')->willReturn($mockRequest);

        $this->renderingContext->setControllerContext($mockControllerContext);

        $this->injectDependenciesIntoViewHelper($this->translateViewHelper);

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => 'some.label']);
        $this->translateViewHelper->render();
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function translationFallbackDataProvider(): \Iterator
    {
        # id & value specified with all 4 combinations of available translations for id/label
        yield ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'];
        yield ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'];
        yield ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Some label'];
        yield ['id' => 'some.id', 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'];
        # only value specified with all 4 combinations of available translations for id/label
        yield ['id' => null, 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['id' => null, 'value' => 'Some label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => ''];
        yield ['id' => null, 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['id' => null, 'value' => 'Some label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => ''];
        # only id specified with all 4 combinations of available translations for id/label
        yield ['id' => 'some.id', 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'];
        yield ['id' => 'some.id', 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'];
        yield ['id' => 'some.id', 'value' => null, 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'some.id'];
        yield ['id' => 'some.id', 'value' => null, 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'some.id'];
        # neither id nor value specified with all 4 combinations of available translations for id/label
        yield ['id' => null, 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['id' => null, 'value' => null, 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => ''];
        yield ['id' => null, 'value' => null, 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['id' => null, 'value' => null, 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => ''];
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $translatedId
     * @param string $translatedLabel
     * @param string $expectedResult
     */
    #[DataProvider('translationFallbackDataProvider')]
    #[Test]
    public function translationFallbackTests($id, $value, $translatedId, $translatedLabel, $expectedResult)
    {
        $this->mockTranslator->method('translateById')->with($id)->willReturn(($translatedId));
        $this->mockTranslator->method('translateByOriginalLabel')->with($value)->willReturn(($translatedLabel));

        $this->translateViewHelper = $this->prepareArguments($this->translateViewHelper, ['id' => $id, 'value' => $value]);
        $actualResult = $this->translateViewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }
}
