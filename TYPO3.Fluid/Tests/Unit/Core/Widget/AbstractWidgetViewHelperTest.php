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
 * Testcase for AbstractWidgetViewHelper
 */
class AbstractWidgetViewHelperTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Fluid\Core\Widget\AbstractWidgetViewHelper
     */
    protected $viewHelper;

    /**
     * @var \TYPO3\Fluid\Core\Widget\AjaxWidgetContextHolder
     */
    protected $ajaxWidgetContextHolder;

    /**
     * @var \TYPO3\Fluid\Core\Widget\WidgetContext
     */
    protected $widgetContext;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\Flow\Mvc\ActionRequest
     */
    protected $request;

    /**
     */
    public function setUp()
    {
        $this->viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\Widget\AbstractWidgetViewHelper', array('validateArguments', 'initialize', 'callRenderMethod', 'getWidgetConfiguration', 'getRenderingContext'));

        $this->ajaxWidgetContextHolder = $this->createMock('TYPO3\Fluid\Core\Widget\AjaxWidgetContextHolder');
        $this->viewHelper->injectAjaxWidgetContextHolder($this->ajaxWidgetContextHolder);

        $this->widgetContext = $this->createMock('TYPO3\Fluid\Core\Widget\WidgetContext');
        $this->viewHelper->injectWidgetContext($this->widgetContext);

        $this->objectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->viewHelper->injectObjectManager($this->objectManager);

        $this->controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $this->viewHelper->_set('controllerContext', $this->controllerContext);

        $this->request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderCallsTheRightSequenceOfMethods()
    {
        $this->callViewHelper();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderDoesNotStoreTheWidgetContextForStatelessWidgets()
    {
        $this->viewHelper->_set('ajaxWidget', true);
        $this->viewHelper->_set('storeConfigurationInSession', false);
        $this->ajaxWidgetContextHolder->expects($this->never())->method('store');

        $this->callViewHelper();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderStoresTheWidgetContextIfInAjaxMode()
    {
        $this->viewHelper->_set('ajaxWidget', true);
        $this->ajaxWidgetContextHolder->expects($this->once())->method('store')->with($this->widgetContext);

        $this->callViewHelper();
    }

    /**
     * Calls the ViewHelper, and emulates a rendering.
     *
     * @return void
     */
    public function callViewHelper()
    {
        $viewHelperVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer');
        $renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();
        $renderingContext->injectViewHelperVariableContainer($viewHelperVariableContainer);
        $this->viewHelper->setRenderingContext($renderingContext);

        $this->viewHelper->expects($this->any())->method('getWidgetConfiguration')->will($this->returnValue(array('Some Widget Configuration')));
        $this->widgetContext->expects($this->once())->method('setNonAjaxWidgetConfiguration')->with(array('Some Widget Configuration'));

        $this->widgetContext->expects($this->once())->method('setWidgetIdentifier')->with(strtolower(str_replace('\\', '-', get_class($this->viewHelper))));

        $this->viewHelper->_set('controller', new \stdClass());
        $this->widgetContext->expects($this->once())->method('setControllerObjectName')->with('stdClass');

        $this->viewHelper->expects($this->once())->method('validateArguments');
        $this->viewHelper->expects($this->once())->method('initialize');
        $this->viewHelper->expects($this->once())->method('callRenderMethod')->will($this->returnValue('renderedResult'));
        $output = $this->viewHelper->initializeArgumentsAndRender(array('arg1' => 'val1'));
        $this->assertEquals('renderedResult', $output);
    }

    /**
     * @test
     */
    public function setChildNodesAddsChildNodesToWidgetContext()
    {
        $this->widgetContext = new \TYPO3\Fluid\Core\Widget\WidgetContext();
        $this->viewHelper->injectWidgetContext($this->widgetContext);

        $node1 = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode');
        $node2 = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode')->disableOriginalConstructor()->getMock();
        $node3 = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode');

        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode($node1);
        $rootNode->addChildNode($node2);
        $rootNode->addChildNode($node3);

        $this->objectManager->expects($this->once())->method('get')->with('TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode')->will($this->returnValue($rootNode));

        $renderingContext = $this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');
        $this->viewHelper->_set('renderingContext', $renderingContext);

        $this->viewHelper->setChildNodes(array($node1, $node2, $node3));

        $this->assertEquals($rootNode, $this->widgetContext->getViewHelperChildNodes());
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Widget\Exception\MissingControllerException
     */
    public function initiateSubRequestThrowsExceptionIfControllerIsNoWidgetController()
    {
        $controller = $this->createMock('TYPO3\Flow\Mvc\Controller\ControllerInterface');
        $this->viewHelper->_set('controller', $controller);

        $this->viewHelper->_call('initiateSubRequest');
    }
}
