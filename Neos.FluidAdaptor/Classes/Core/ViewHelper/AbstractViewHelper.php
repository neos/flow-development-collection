<?php
declare(strict_types=1);

namespace Neos\FluidAdaptor\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Rendering\FlowAwareRenderingContextInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper as FluidAbstractViewHelper;

/**
 * The abstract base class for all view helpers.
 *
 * @api
 */
abstract class AbstractViewHelper extends FluidAbstractViewHelper
{
    /**
     * Controller Context to use
     *
     * @var ControllerContext
     * @api
     */
    protected $controllerContext;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RenderingContextInterface $renderingContext
     * @return void
     */
    public function setRenderingContext(RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
        $this->templateVariableContainer = $renderingContext->getVariableProvider();
        $this->viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        if ($renderingContext instanceof FlowAwareRenderingContextInterface) {
            $this->controllerContext = $renderingContext->getControllerContext();
        }
    }

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Register a new argument. Call this method from your ViewHelper subclass
     * inside the initializeArguments() method.
     *
     * This exists only to throw our own exception!
     *
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param boolean $required If true, argument is required. Defaults to false.
     * @param mixed $defaultValue Default value of argument
     * @return FluidAbstractViewHelper $this, to allow chaining.
     * @throws Exception
     * @api
     */
    protected function registerArgument($name, $type, $description, $required = false, $defaultValue = null)
    {
        if (array_key_exists($name, $this->argumentDefinitions)) {
            throw new Exception('Argument "' . $name . '" has already been defined, thus it should not be defined again.', 1253036401);
        }
        return parent::registerArgument($name, $type, $description, $required, $defaultValue);
    }

    /**
     * Overrides a registered argument. Call this method from your ViewHelper subclass
     * inside the initializeArguments() method if you want to override a previously registered argument.
     *
     * This exists only to throw our own exception!
     *
     * @see registerArgument()
     *
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param boolean $required If true, argument is required. Defaults to false.
     * @param mixed $defaultValue Default value of argument
     * @return FluidAbstractViewHelper $this, to allow chaining.
     * @throws Exception
     * @api
     */
    protected function overrideArgument($name, $type, $description, $required = false, $defaultValue = null)
    {
        if (!array_key_exists($name, $this->argumentDefinitions)) {
            throw new Exception('Argument "' . $name . '" has not been defined, thus it can\'t be overridden.', 1279212461);
        }
        return parent::overrideArgument($name, $type, $description, $required, $defaultValue);
    }

    /**
     * @return boolean
     */
    public function isEscapingInterceptorEnabled(): bool
    {
        return $this->isChildrenEscapingEnabled();
    }
}
