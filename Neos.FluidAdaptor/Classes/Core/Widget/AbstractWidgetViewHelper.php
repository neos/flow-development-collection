<?php
namespace Neos\FluidAdaptor\Core\Widget;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\InfiniteLoopException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Facets\ChildNodeAccessInterface;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * @api
 */
abstract class AbstractWidgetViewHelper extends AbstractViewHelper implements ChildNodeAccessInterface
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * The Controller associated to this widget.
     * This needs to be filled by the individual subclass using
     * property injection.
     *
     * @var AbstractWidgetController
     * @api
     */
    protected $controller;

    /**
     * If set to TRUE, it is an AJAX widget.
     *
     * @var boolean
     * @api
     */
    protected $ajaxWidget = false;

    /**
     * If set to FALSE, this widget won't create a session (only relevant for AJAX widgets).
     *
     * You then need to manually add the serialized configuration data to your links, by
     * setting "includeWidgetContext" to TRUE in the widget link and URI ViewHelpers.
     *
     * @var boolean
     * @api
     */
    protected $storeConfigurationInSession = true;

    /**
     * @var AjaxWidgetContextHolder
     */
    private $ajaxWidgetContextHolder;

    /**
     * @var WidgetContext
     */
    private $widgetContext;

    /**
     * @param AjaxWidgetContextHolder $ajaxWidgetContextHolder
     * @return void
     */
    public function injectAjaxWidgetContextHolder(AjaxWidgetContextHolder $ajaxWidgetContextHolder)
    {
        $this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
    }

    /**
     * @param WidgetContext $widgetContext
     * @return void
     */
    public function injectWidgetContext(WidgetContext $widgetContext)
    {
        $this->widgetContext = $widgetContext;
    }

    /**
     * Registers the widgetId viewhelper
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('widgetId', 'string', 'Unique identifier of the widget instance');
    }

    /**
     * Initialize the arguments of the ViewHelper, and call the render() method of the ViewHelper.
     *
     * @return string the rendered ViewHelper.
     */
    public function initializeArgumentsAndRender()
    {
        $this->validateArguments();
        $this->initialize();
        $this->initializeWidgetContext();

        return $this->callRenderMethod();
    }

    /**
     * Initialize the Widget Context, before the Render method is called.
     *
     * @return void
     */
    private function initializeWidgetContext()
    {
        if ($this->ajaxWidget === true) {
            if ($this->storeConfigurationInSession === true) {
                $this->ajaxWidgetContextHolder->store($this->widgetContext);
            }
            $this->widgetContext->setAjaxWidgetConfiguration($this->getAjaxWidgetConfiguration());
        }

        $this->widgetContext->setNonAjaxWidgetConfiguration($this->getNonAjaxWidgetConfiguration());
        $this->initializeWidgetIdentifier();

        $controllerObjectName = ($this->controller instanceof DependencyProxy) ? $this->controller->_getClassName() : get_class($this->controller);
        $this->widgetContext->setControllerObjectName($controllerObjectName);
    }

    /**
     * Stores the syntax tree child nodes in the Widget Context, so they can be
     * rendered with <f:widget.renderChildren> lateron.
     *
     * @param array $childNodes The SyntaxTree Child nodes of this ViewHelper.
     * @return void
     */
    public function setChildNodes(array $childNodes)
    {
        $rootNode = new RootNode();

        foreach ($childNodes as $childNode) {
            $rootNode->addChildNode($childNode);
        }
        $this->widgetContext->setViewHelperChildNodes($rootNode, $this->renderingContext);
    }

    /**
     * Generate the configuration for this widget. Override to adjust.
     *
     * @return array
     * @api
     */
    protected function getWidgetConfiguration()
    {
        return $this->arguments;
    }

    /**
     * Generate the configuration for this widget in AJAX context.
     *
     * By default, returns getWidgetConfiguration(). Should become API later.
     *
     * @return array
     */
    protected function getAjaxWidgetConfiguration()
    {
        return $this->getWidgetConfiguration();
    }

    /**
     * Generate the configuration for this widget in non-AJAX context.
     *
     * By default, returns getWidgetConfiguration(). Should become API later.
     *
     * @return array
     */
    protected function getNonAjaxWidgetConfiguration()
    {
        return $this->getWidgetConfiguration();
    }

    /**
     * Initiate a sub request to $this->controller. Make sure to fill $this->controller
     * via Dependency Injection.
     * @return Response the response of this request.
     * @throws Exception\InvalidControllerException
     * @throws Exception\MissingControllerException
     * @throws InfiniteLoopException
     * @throws StopActionException
     * @api
     */
    protected function initiateSubRequest()
    {
        if ($this->controller instanceof DependencyProxy) {
            $this->controller->_activateDependency();
        }
        if (!($this->controller instanceof AbstractWidgetController)) {
            throw new Exception\MissingControllerException('initiateSubRequest() can not be called if there is no controller inside $this->controller. Make sure to add the @Neos\Flow\Annotations\Inject annotation in your widget class.', 1284401632);
        }

        /** @var $subRequest ActionRequest */
        $subRequest = $this->objectManager->get(ActionRequest::class, $this->controllerContext->getRequest());
        /** @var $subResponse Response */
        $subResponse = $this->objectManager->get(Response::class, $this->controllerContext->getResponse());

        $this->passArgumentsToSubRequest($subRequest);
        $subRequest->setArgument('__widgetContext', $this->widgetContext);
        $subRequest->setArgumentNamespace('--' . $this->widgetContext->getWidgetIdentifier());

        $dispatchLoopCount = 0;
        while (!$subRequest->isDispatched()) {
            if ($dispatchLoopCount++ > 99) {
                throw new InfiniteLoopException('Could not ultimately dispatch the widget request after '  . $dispatchLoopCount . ' iterations.', 1380282310);
            }
            $widgetControllerObjectName = $this->widgetContext->getControllerObjectName();
            if ($subRequest->getControllerObjectName() !== '' && $subRequest->getControllerObjectName() !== $widgetControllerObjectName) {
                throw new Exception\InvalidControllerException(sprintf('You are not allowed to initiate requests to different controllers from a widget.' . chr(10) . 'widget controller: "%s", requested controller: "%s".', $widgetControllerObjectName, $subRequest->getControllerObjectName()), 1380284579);
            }
            $subRequest->setControllerObjectName($this->widgetContext->getControllerObjectName());
            try {
                $this->controller->processRequest($subRequest, $subResponse);
            } catch (StopActionException $exception) {
                if ($exception instanceof ForwardException) {
                    $subRequest = $exception->getNextRequest();
                    continue;
                }
                /** @var $parentResponse Response */
                $parentResponse = $this->controllerContext->getResponse();
                $parentResponse
                    ->setStatus($subResponse->getStatusCode())
                    ->setContent($subResponse->getContent())
                    ->setHeader('Location', $subResponse->getHeader('Location'));
                throw $exception;
            }
        }
        return $subResponse;
    }

    /**
     * Pass the arguments of the widget to the sub request.
     *
     * @param ActionRequest $subRequest
     * @return void
     */
    private function passArgumentsToSubRequest(ActionRequest $subRequest)
    {
        $arguments = $this->controllerContext->getRequest()->getPluginArguments();
        $widgetIdentifier = $this->widgetContext->getWidgetIdentifier();

        $controllerActionName = 'index';
        if (isset($arguments[$widgetIdentifier])) {
            if (isset($arguments[$widgetIdentifier]['@action'])) {
                $controllerActionName = $arguments[$widgetIdentifier]['@action'];
                unset($arguments[$widgetIdentifier]['@action']);
            }
            $subRequest->setArguments($arguments[$widgetIdentifier]);
        }
        if ($subRequest->getControllerActionName() === null) {
            $subRequest->setControllerActionName($controllerActionName);
        }
    }

    /**
     * The widget identifier is unique on the current page, and is used
     * in the URI as a namespace for the widget's arguments.
     *
     * @return string the widget identifier for this widget
     * @return void
     */
    private function initializeWidgetIdentifier()
    {
        $widgetIdentifier = ($this->hasArgument('widgetId') ? $this->arguments['widgetId'] : strtolower(str_replace('\\', '-', get_class($this))));
        $this->widgetContext->setWidgetIdentifier($widgetIdentifier);
    }

    /**
     * Resets the ViewHelper state by creating a fresh WidgetContext
     *
     * @return void
     */
    public function resetState()
    {
        if ($this->ajaxWidget) {
            $this->widgetContext = $this->objectManager->get(WidgetContext::class);
        }
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     * @return string
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        $compiler->disable();
        return "''";
    }
}
