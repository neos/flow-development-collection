<?php
namespace Neos\FluidAdaptor\Tests\Unit\Core\Widget;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Testcase for WidgetContext
 *
 */
class WidgetContextTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var \Neos\FluidAdaptor\Core\Widget\WidgetContext
     */
    protected $widgetContext;

    /**
     */
    protected function setUp(): void
    {
        $this->widgetContext = new \Neos\FluidAdaptor\Core\Widget\WidgetContext();
    }

    /**
     * @test
     */
    public function widgetIdentifierCanBeReadAgain()
    {
        $this->widgetContext->setWidgetIdentifier('myWidgetIdentifier');
        self::assertEquals('myWidgetIdentifier', $this->widgetContext->getWidgetIdentifier());
    }

    /**
     * @test
     */
    public function ajaxWidgetIdentifierCanBeReadAgain()
    {
        $this->widgetContext->setAjaxWidgetIdentifier(42);
        self::assertEquals(42, $this->widgetContext->getAjaxWidgetIdentifier());
    }

    /**
     * @test
     */
    public function nonAjaxWidgetConfigurationIsReturnedWhenContextIsNotSerialized()
    {
        $this->widgetContext->setNonAjaxWidgetConfiguration(['key' => 'value']);
        $this->widgetContext->setAjaxWidgetConfiguration(['keyAjax' => 'valueAjax']);
        self::assertEquals(['key' => 'value'], $this->widgetContext->getWidgetConfiguration());
    }

    /**
     * @test
     */
    public function aWidgetConfigurationIsReturnedWhenContextIsSerialized()
    {
        $this->widgetContext->setNonAjaxWidgetConfiguration(['key' => 'value']);
        $this->widgetContext->setAjaxWidgetConfiguration(['keyAjax' => 'valueAjax']);
        $this->widgetContext = serialize($this->widgetContext);
        $this->widgetContext = unserialize($this->widgetContext);
        self::assertEquals(['keyAjax' => 'valueAjax'], $this->widgetContext->getWidgetConfiguration());
    }

    /**
     * @test
     */
    public function controllerObjectNameCanBeReadAgain()
    {
        $this->widgetContext->setControllerObjectName('TYPO3\My\Object\Name');
        self::assertEquals('TYPO3\My\Object\Name', $this->widgetContext->getControllerObjectName());
    }

    /**
     * @test
     */
    public function viewHelperChildNodesCanBeReadAgain()
    {
        $viewHelperChildNodes = $this->createMock(RootNode::class);
        $renderingContext = $this->createMock(RenderingContextInterface::class);

        $this->widgetContext->setViewHelperChildNodes($viewHelperChildNodes, $renderingContext);
        self::assertSame($viewHelperChildNodes, $this->widgetContext->getViewHelperChildNodes());
        self::assertSame($renderingContext, $this->widgetContext->getViewHelperChildNodeRenderingContext());
    }
}
