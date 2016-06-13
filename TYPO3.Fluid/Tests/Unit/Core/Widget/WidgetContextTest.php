<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Widget;

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
 * Testcase for WidgetContext
 *
 */
class WidgetContextTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Fluid\Core\Widget\WidgetContext
     */
    protected $widgetContext;

    /**
     */
    public function setUp()
    {
        $this->widgetContext = new \TYPO3\Fluid\Core\Widget\WidgetContext();
    }

    /**
     * @test
     */
    public function widgetIdentifierCanBeReadAgain()
    {
        $this->widgetContext->setWidgetIdentifier('myWidgetIdentifier');
        $this->assertEquals('myWidgetIdentifier', $this->widgetContext->getWidgetIdentifier());
    }

    /**
     * @test
     */
    public function ajaxWidgetIdentifierCanBeReadAgain()
    {
        $this->widgetContext->setAjaxWidgetIdentifier(42);
        $this->assertEquals(42, $this->widgetContext->getAjaxWidgetIdentifier());
    }

    /**
     * @test
     */
    public function nonAjaxWidgetConfigurationIsReturnedWhenContextIsNotSerialized()
    {
        $this->widgetContext->setNonAjaxWidgetConfiguration(array('key' => 'value'));
        $this->widgetContext->setAjaxWidgetConfiguration(array('keyAjax' => 'valueAjax'));
        $this->assertEquals(array('key' => 'value'), $this->widgetContext->getWidgetConfiguration());
    }

    /**
     * @test
     */
    public function aWidgetConfigurationIsReturnedWhenContextIsSerialized()
    {
        $this->widgetContext->setNonAjaxWidgetConfiguration(array('key' => 'value'));
        $this->widgetContext->setAjaxWidgetConfiguration(array('keyAjax' => 'valueAjax'));
        $this->widgetContext = serialize($this->widgetContext);
        $this->widgetContext = unserialize($this->widgetContext);
        $this->assertEquals(array('keyAjax' => 'valueAjax'), $this->widgetContext->getWidgetConfiguration());
    }

    /**
     * @test
     */
    public function controllerObjectNameCanBeReadAgain()
    {
        $this->widgetContext->setControllerObjectName('TYPO3\My\Object\Name');
        $this->assertEquals('TYPO3\My\Object\Name', $this->widgetContext->getControllerObjectName());
    }

    /**
     * @test
     */
    public function viewHelperChildNodesCanBeReadAgain()
    {
        $viewHelperChildNodes = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode');
        $renderingContext = $this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');

        $this->widgetContext->setViewHelperChildNodes($viewHelperChildNodes, $renderingContext);
        $this->assertSame($viewHelperChildNodes, $this->widgetContext->getViewHelperChildNodes());
        $this->assertSame($renderingContext, $this->widgetContext->getViewHelperChildNodeRenderingContext());
    }
}
