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
use Neos\FluidAdaptor\Core\Widget\Exception\RenderingContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;
use Neos\FluidAdaptor\ViewHelpers\RenderChildrenViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for CycleViewHelper
 *
 */
final class RenderChildrenViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var RenderChildrenViewHelper
     */
    protected $viewHelper;

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(RenderChildrenViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
    }

    #[Test]
    public function renderCallsEvaluateOnTheRootNode(): void
    {
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $renderingContext = $this->createStub(RenderingContextInterface::class);

        $rootNode = $this->createMock(RootNode::class);

        $widgetContext = $this->createMock(WidgetContext::class);
        $this->request->method('getInternalArgument')->with('__widgetContext')->willReturn($widgetContext);
        $widgetContext->method('getViewHelperChildNodeRenderingContext')->willReturn($renderingContext);
        $widgetContext->method('getViewHelperChildNodes')->willReturn($rootNode);

        $rootNode->method('evaluate')->with($renderingContext)->willReturn('Rendered Results');

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['k1' => 'v1', 'k2' => 'v2']);
        $output = $this->viewHelper->render();
        self::assertEquals('Rendered Results', $output);
    }

    #[Test]
    public function renderThrowsExceptionIfTheRequestIsNotAWidgetRequest(): void
    {
        $this->expectException(WidgetContextNotFoundException::class);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();

        $this->viewHelper->render();
    }

    #[Test]
    public function renderThrowsExceptionIfTheChildNodeRenderingContextIsNotThere(): void
    {
        $this->expectException(RenderingContextNotFoundException::class);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();

        $widgetContext = $this->createMock(WidgetContext::class);
        $this->request->method('getInternalArgument')->with('__widgetContext')->willReturn($widgetContext);
        $widgetContext->method('getViewHelperChildNodeRenderingContext')->willReturn(null);
        $widgetContext->method('getViewHelperChildNodes')->willReturn(null);

        $this->viewHelper->render();
    }
}
