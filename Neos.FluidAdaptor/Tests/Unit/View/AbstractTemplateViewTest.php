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
    protected function setUp(): void
    {
        $this->templateVariableContainer = $this->getMockBuilder(TemplateVariableContainer::class)->setMethods(['exists', 'remove', 'add'])->getMock();
        $this->viewHelperVariableContainer = $this->getMockBuilder(ViewHelperVariableContainer::class)->setMethods(['setView'])->getMock();
        $this->renderingContext = $this->getMockBuilder(RenderingContext::class)->setMethods(['getViewHelperVariableContainer', 'getVariableProvider'])->disableOriginalConstructor()->getMock();
        $this->renderingContext->expects(self::any())->method('getViewHelperVariableContainer')->will(self::returnValue($this->viewHelperVariableContainer));
        $this->renderingContext->expects(self::any())->method('getVariableProvider')->will(self::returnValue($this->templateVariableContainer));
        $this->view = $this->getMockBuilder(AbstractTemplateView::class)->setMethods(['getTemplateSource', 'getLayoutSource', 'getPartialSource', 'canRender', 'getTemplateIdentifier', 'getLayoutIdentifier', 'getPartialIdentifier'])->getMock();
        $this->view->setRenderingContext($this->renderingContext);
    }

    /**
     * @test
     */
    public function viewIsPlacedInViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->expects(self::once())->method('setView')->with($this->view);
        $this->view->setRenderingContext($this->renderingContext);
    }

    /**
     * @test
     */
    public function assignAddsValueToTemplateVariableContainer()
    {
        $this->templateVariableContainer->expects(self::exactly(2))->method('add')->withConsecutive(['foo', 'FooValue'], ['bar', 'BarValue']);

        $this->view
            ->assign('foo', 'FooValue')
            ->assign('bar', 'BarValue');
    }

    /**
     * @test
     */
    public function assignCanOverridePreviouslyAssignedValues()
    {
        $this->templateVariableContainer->expects(self::exactly(2))->method('add')->withConsecutive(['foo', 'FooValue'], ['foo', 'FooValueOverridden']);

        $this->view->assign('foo', 'FooValue');
        $this->view->assign('foo', 'FooValueOverridden');
    }

    /**
     * @test
     */
    public function assignMultipleAddsValuesToTemplateVariableContainer()
    {
        $this->templateVariableContainer->expects(self::exactly(3))->method('add')->withConsecutive(['foo', 'FooValue'], ['bar', 'BarValue'], ['baz', 'BazValue']);

        $this->view
            ->assignMultiple(['foo' => 'FooValue', 'bar' => 'BarValue'])
            ->assignMultiple(['baz' => 'BazValue']);
    }

    /**
     * @test
     */
    public function assignMultipleCanOverridePreviouslyAssignedValues()
    {
        $this->templateVariableContainer->expects(self::exactly(3))->method('add')->withConsecutive(['foo', 'FooValue'], ['foo', 'FooValueOverridden'], ['bar', 'BarValue']);

        $this->view->assign('foo', 'FooValue');
        $this->view->assignMultiple(['foo' => 'FooValueOverridden', 'bar' => 'BarValue']);
    }
}
