<?php
namespace Neos\FluidAdaptor\Tests\Unit\View;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer;
use Neos\FluidAdaptor\View\AbstractTemplateView;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Testcase for the TemplateView
 */
class AbstractTemplateViewTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var AbstractTemplateView
     */
    protected $view;

    /**
     * @var RenderingContext
     */
    protected $renderingContext;

    /**
     * @var ViewHelperVariableContainer
     */
    protected $viewHelperVariableContainer;

    /**
     * @var TemplateVariableContainer
     */
    protected $templateVariableContainer;

    /**
     * Sets up this test case
     *
     * @return void
     */
    public function setUp()
    {
        $this->templateVariableContainer = $this->getMockBuilder(TemplateVariableContainer::class)->setMethods(array('exists', 'remove', 'add'))->getMock();
        $this->viewHelperVariableContainer = $this->getMockBuilder(ViewHelperVariableContainer::class)->setMethods(array('setView'))->getMock();
        $this->renderingContext = $this->getMockBuilder(RenderingContext::class)->setMethods(array('getViewHelperVariableContainer', 'getVariableProvider'))->disableOriginalConstructor()->getMock();
        $this->renderingContext->expects($this->any())->method('getViewHelperVariableContainer')->will($this->returnValue($this->viewHelperVariableContainer));
        $this->renderingContext->expects($this->any())->method('getVariableProvider')->will($this->returnValue($this->templateVariableContainer));
        $this->view = $this->getMockBuilder(AbstractTemplateView::class)->setMethods(array('getTemplateSource', 'getLayoutSource', 'getPartialSource', 'canRender', 'getTemplateIdentifier', 'getLayoutIdentifier', 'getPartialIdentifier'))->getMock();
        $this->view->setRenderingContext($this->renderingContext);
    }

    /**
     * @test
     */
    public function viewIsPlacedInViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->expects($this->once())->method('setView')->with($this->view);
        $this->view->setRenderingContext($this->renderingContext);
    }

    /**
     * @test
     */
    public function assignAddsValueToTemplateVariableContainer()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('foo', 'FooValue');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('bar', 'BarValue');

        $this->view
            ->assign('foo', 'FooValue')
            ->assign('bar', 'BarValue');
    }

    /**
     * @test
     */
    public function assignCanOverridePreviouslyAssignedValues()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('foo', 'FooValue');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('foo', 'FooValueOverridden');

        $this->view->assign('foo', 'FooValue');
        $this->view->assign('foo', 'FooValueOverridden');
    }

    /**
     * @test
     */
    public function assignMultipleAddsValuesToTemplateVariableContainer()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('foo', 'FooValue');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('bar', 'BarValue');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('baz', 'BazValue');

        $this->view
            ->assignMultiple(array('foo' => 'FooValue', 'bar' => 'BarValue'))
            ->assignMultiple(array('baz' => 'BazValue'));
    }

    /**
     * @test
     */
    public function assignMultipleCanOverridePreviouslyAssignedValues()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('foo', 'FooValue');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('foo', 'FooValueOverridden');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('bar', 'BarValue');

        $this->view->assign('foo', 'FooValue');
        $this->view->assignMultiple(array('foo' => 'FooValueOverridden', 'bar' => 'BarValue'));
    }
}
