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

use Neos\FluidAdaptor\Core\Widget\AbstractWidgetController;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper;
use Neos\FluidAdaptor\Core\Widget\Exception\MissingControllerException;
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
     * @var AbstractWidgetController|__anonymous@2351
     */
    protected $testWidgetControllerClass;

    /**
     */
    protected function setUp(): void
    {
        $this->ajaxWidgetContextHolder = $this->createMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class);
        $this->widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class);
        $this->objectManager = $this->createMock(\Neos\Flow\ObjectManagement\ObjectManagerInterface::class);
        $this->objectManager->expects(self::any())->method('get')->with(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class)->willReturn($this->widgetContext);
        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->testWidgetControllerClass = new class extends AbstractWidgetController {
        };
        $testWidgetViewHelperClass = new class extends AbstractWidgetViewHelper {
            public function setAjax(bool $ajax): void
            {
                $this->ajaxWidget = $ajax;
            }
            public function injectRenderingContext(RenderingContextInterface $renderingContext): void
            {
                $this->renderingContext = $renderingContext;
            }
            public function injectController($controller): void
            {
                $this->controller = $controller;
            }
            public function render(): string
            {
                return 'renderedResult';
            }

            public function initiateSubRequest(): void
            {
                parent::initiateSubRequest();
            }
        };

        $this->viewHelper = $testWidgetViewHelperClass;
        $this->viewHelper->injectWidgetContext($this->widgetContext);
        $this->viewHelper->injectController($this->testWidgetControllerClass);
        $this->viewHelper->injectObjectManager($this->objectManager);
        $this->viewHelper->injectAjaxWidgetContextHolder($this->ajaxWidgetContextHolder);
        $this->request = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderCallsTheRightSequenceOfMethods()
    {
        $this->widgetContext->expects(self::once())->method('setControllerObjectName')->with(get_class($this->testWidgetControllerClass));
        $this->callViewHelper();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderDoesNotStoreTheWidgetContextForStatelessWidgets()
    {
        $this->ajaxWidgetContextHolder->expects(self::never())->method('store');
        $this->widgetContext->expects(self::once())->method('setControllerObjectName')->with(get_class($this->testWidgetControllerClass));
        $this->callViewHelper();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderStoresTheWidgetContextIfInAjaxMode()
    {
        $this->viewHelper->setAjax(true);
        $this->ajaxWidgetContextHolder->expects(self::once())->method('store')->with($this->widgetContext);
        $this->widgetContext->expects(self::once())->method('setControllerObjectName')->with(get_class($this->testWidgetControllerClass));
        $this->callViewHelper();
    }

    /**
     * Calls the ViewHelper, and emulates a rendering.
     *
     * @return void
     */
    public function callViewHelper()
    {
        $this->widgetContext->expects(self::once())->method('setNonAjaxWidgetConfiguration')->with([]);
        $this->widgetContext->expects(self::once())->method('setWidgetIdentifier')->with(strtolower(str_replace('\\', '-', get_class($this->viewHelper))));
        $output = $this->viewHelper->initializeArgumentsAndRender(['arg1' => 'val1']);
        self::assertEquals('renderedResult', $output);
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
        $this->viewHelper->injectRenderingContext($renderingContext);
        $this->viewHelper->setChildNodes([$node1, $node2, $node3]);

        self::assertEquals($rootNode, $this->widgetContext->getViewHelperChildNodes());
    }

    /**
     * @test
     */
    public function initiateSubRequestThrowsExceptionIfControllerIsNoWidgetController()
    {
        $this->expectException(MissingControllerException::class);
        $controller = $this->createMock(\Neos\Flow\Mvc\Controller\ControllerInterface::class);
        $this->viewHelper->injectController($controller);

        $this->viewHelper->initiateSubRequest();
    }
}
