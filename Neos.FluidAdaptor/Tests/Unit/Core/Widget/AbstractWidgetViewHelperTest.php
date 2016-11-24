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

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Testcase for AbstractWidgetViewHelper
 */
class AbstractWidgetViewHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var \Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper
     */
    protected $viewHelper;

    /**
     * @var \Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder
     */
    protected $ajaxWidgetContextHolder;

    /**
     * @var \Neos\FluidAdaptor\Core\Widget\WidgetContext
     */
    protected $widgetContext;

    /**
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Neos\Flow\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \Neos\Flow\Mvc\ActionRequest
     */
    protected $request;

    /**
     */
    public function setUp()
    {
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper::class, array('validateArguments', 'initialize', 'callRenderMethod', 'getWidgetConfiguration', 'getRenderingContext'));

        $this->ajaxWidgetContextHolder = $this->createMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class);
        $this->viewHelper->injectAjaxWidgetContextHolder($this->ajaxWidgetContextHolder);

        $this->widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class);
        $this->viewHelper->injectWidgetContext($this->widgetContext);

        $this->objectManager = $this->createMock(\Neos\Flow\ObjectManagement\ObjectManagerInterface::class);
        $this->viewHelper->injectObjectManager($this->objectManager);

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->viewHelper->_set('controllerContext', $this->controllerContext);

        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
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
        $this->widgetContext = new \Neos\FluidAdaptor\Core\Widget\WidgetContext();
        $this->viewHelper->injectWidgetContext($this->widgetContext);

        $node1 = $this->createMock(AbstractNode::class);
        $node2 = $this->getMockBuilder(TextNode::class)->disableOriginalConstructor()->getMock();
        $node3 = $this->createMock(AbstractNode::class);

        $rootNode = new RootNode();
        $rootNode->addChildNode($node1);
        $rootNode->addChildNode($node2);
        $rootNode->addChildNode($node3);

        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $this->viewHelper->_set('renderingContext', $renderingContext);

        $this->viewHelper->setChildNodes(array($node1, $node2, $node3));

        $this->assertEquals($rootNode, $this->widgetContext->getViewHelperChildNodes());
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\Widget\Exception\MissingControllerException
     */
    public function initiateSubRequestThrowsExceptionIfControllerIsNoWidgetController()
    {
        $controller = $this->createMock(\Neos\Flow\Mvc\Controller\ControllerInterface::class);
        $this->viewHelper->_set('controller', $controller);

        $this->viewHelper->_call('initiateSubRequest');
    }
}
