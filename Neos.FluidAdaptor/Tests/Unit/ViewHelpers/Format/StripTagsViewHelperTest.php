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
require_once(__DIR__ . '/../Fixtures/UserWithoutToString.php');
require_once(__DIR__ . '/../Fixtures/UserWithToString.php');

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithoutToString;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithToString;
use Neos\FluidAdaptor\ViewHelpers\Format\StripTagsViewHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\StripTagsViewHelper
 */
final class StripTagsViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\StripTagsViewHelper|MockObject
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(StripTagsViewHelper::class)->onlyMethods(['buildRenderChildrenClosure', 'renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function viewHelperDeactivatesEscapingInterceptor()
    {
        self::assertFalse($this->viewHelper->isEscapingInterceptorEnabled());
    }

    #[Test]
    public function renderUsesValueAsSourceIfSpecified()
    {
        $string = 'Some string';
        $this->viewHelper->method('buildRenderChildrenClosure')->willReturn(function () {
            throw new \Exception('rendderChildrenClosure was invoked but should not have been');
        });
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $string]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($string, $actualResult);
    }

    #[Test]
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $string = 'Some string';
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn($string);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($string, $actualResult);
    }

    /**
     * Data Provider for the render tests
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function stringsTestDataProvider(): \Iterator
    {
        yield ['This is a sample text without special characters.', 'This is a sample text without special characters.'];
        yield ['This is a sample text <b>with <i>some</i> tags</b>.', 'This is a sample text with some tags.'];
        yield ['This text contains some &quot;&Uuml;mlaut&quot;.', 'This text contains some &quot;&Uuml;mlaut&quot;.'];
    }

    #[DataProvider('stringsTestDataProvider')]
    #[Test]
    public function renderCorrectlyConvertsIntoPlaintext($source, $expectedResult)
    {
        $this->viewHelper->method('buildRenderChildrenClosure')->willReturn(function () {
            throw new \Exception('rendderChildrenClosure was invoked but should not have been');
        });
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function renderReturnsUnmodifiedSourceIfItIsANumber()
    {
        $source = 123.45;
        $this->viewHelper->method('buildRenderChildrenClosure')->willReturn(function () {
            throw new \Exception('rendderChildrenClosure was invoked but should not have been');
        });
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($source, $actualResult);
    }

    #[Test]
    public function renderConvertsObjectsToStrings()
    {
        $user = new UserWithToString('Xaver <b>Cross-Site</b>');
        $expectedResult = 'Xaver Cross-Site';
        $this->viewHelper->method('buildRenderChildrenClosure')->willReturn(function () {
            throw new \Exception('renderChildrenClosure was invoked but should not have been');
        });
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $user]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function renderDoesNotModifySourceIfItIsAnObjectThatCantBeConvertedToAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = new UserWithoutToString('Xaver <b>Cross-Site</b>');
        $this->viewHelper->method('buildRenderChildrenClosure')->willReturn(function () {
            throw new \Exception('renderChildrenClosure was invoked but should not have been');
        });
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $user]);
        $this->viewHelper->render();
    }
}
