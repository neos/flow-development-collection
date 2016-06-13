<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for CycleViewHelper
 *
 */
class RenderChildrenViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * var \TYPO3\Fluid\ViewHelpers\RenderChildrenViewHelper
     */
    protected $viewHelper;

    /**
     */
    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\RenderChildrenViewHelper')->setMethods(array('renderChildren'))->getMock();
    }

    /**
     * @test
     */
    public function renderCallsEvaluateOnTheRootNodeAndRegistersTheArguments()
    {
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();

        $templateVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer');
        $templateVariableContainer->expects($this->at(0))->method('add')->with('k1', 'v1');
        $templateVariableContainer->expects($this->at(1))->method('add')->with('k2', 'v2');
        $templateVariableContainer->expects($this->at(2))->method('remove')->with('k1');
        $templateVariableContainer->expects($this->at(3))->method('remove')->with('k2');

        $renderingContext = $this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');
        $renderingContext->expects($this->any())->method('getTemplateVariableContainer')->will($this->returnValue($templateVariableContainer));

        $rootNode = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode');

        $widgetContext = $this->createMock('TYPO3\Fluid\Core\Widget\WidgetContext');
        $this->request->expects($this->any())->method('getInternalArgument')->with('__widgetContext')->will($this->returnValue($widgetContext));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodeRenderingContext')->will($this->returnValue($renderingContext));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodes')->will($this->returnValue($rootNode));

        $rootNode->expects($this->any())->method('evaluate')->with($renderingContext)->will($this->returnValue('Rendered Results'));

        $output = $this->viewHelper->render(array('k1' => 'v1', 'k2' => 'v2'));
        $this->assertEquals('Rendered Results', $output);
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Widget\Exception\WidgetContextNotFoundException
     */
    public function renderThrowsExceptionIfTheRequestIsNotAWidgetRequest()
    {
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();

        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Widget\Exception\RenderingContextNotFoundException
     */
    public function renderThrowsExceptionIfTheChildNodeRenderingContextIsNotThere()
    {
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();

        $widgetContext = $this->createMock('TYPO3\Fluid\Core\Widget\WidgetContext');
        $this->request->expects($this->any())->method('getInternalArgument')->with('__widgetContext')->will($this->returnValue($widgetContext));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodeRenderingContext')->will($this->returnValue(null));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodes')->will($this->returnValue(null));

        $this->viewHelper->render();
    }
}
