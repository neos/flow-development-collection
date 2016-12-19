<?php
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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
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
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

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
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * @return boolean
     */
    public function isEscapingInterceptorEnabled()
    {
        return $this->isChildrenEscapingEnabled();
    }

    /**
     * Call the render() method and handle errors.
     *
     * @return string the rendered ViewHelper
     * @throws Exception
     */
    protected function callRenderMethod()
    {
        $renderMethodParameters = [];
        foreach ($this->argumentDefinitions as $argumentName => $argumentDefinition) {
            if ($argumentDefinition instanceof ArgumentDefinition && $argumentDefinition->isMethodParameter()) {
                $renderMethodParameters[$argumentName] = $this->arguments[$argumentName];
            }
        }

        try {
            return call_user_func_array([$this, 'render'], $renderMethodParameters);
        } catch (Exception $exception) {
            if ($this->objectManager->getContext()->isProduction()) {
                $this->systemLogger->log(
                    'A Fluid ViewHelper Exception was captured: ' . $exception->getMessage() . ' (' . $exception->getCode() . ')',
                    LOG_ERR,
                    ['exception' => $exception]
                );
                return '';
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition[]
     * @throws \TYPO3Fluid\Fluid\Core\Parser\Exception
     */
    public function prepareArguments()
    {
        if (method_exists($this, 'registerRenderMethodArguments')) {
            $this->registerRenderMethodArguments();
        }

        return parent::prepareArguments();
    }

    /**
     * Register a new argument. Call this method from your ViewHelper subclass
     * inside the initializeArguments() method.
     *
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param boolean $required If TRUE, argument is required. Defaults to FALSE.
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
     * @see registerArgument()
     *
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param boolean $required If TRUE, argument is required. Defaults to FALSE.
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
     * Registers render method arguments
     *
     * @return void
     * @deprecated Render method should no longer expect arguments, instead all arguments should be registered in "initializeArguments"
     */
    protected function registerRenderMethodArguments()
    {
        foreach (static::getRenderMethodArgumentDefinitions($this->objectManager) as $argumentName => $definition) {
            $this->argumentDefinitions[$argumentName] = new ArgumentDefinition($definition[0], $definition[1], $definition[2], $definition[3], $definition[4], $definition[5]);
        }
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return ArgumentDefinition[]
     * @throws \Neos\FluidAdaptor\Core\Exception
     * @Flow\CompileStatic
     */
    public static function getRenderMethodArgumentDefinitions(ObjectManagerInterface $objectManager)
    {
        $methodArgumentDefinitions = [];
        $reflectionService = $objectManager->get(ReflectionService::class);
        $methodParameters = $reflectionService->getMethodParameters(static::class, 'render');
        if (count($methodParameters) === 0) {
            return $methodArgumentDefinitions;
        }

        $methodTags = $reflectionService->getMethodTagsValues(static::class, 'render');

        $paramAnnotations = [];
        if (isset($methodTags['param'])) {
            $paramAnnotations = $methodTags['param'];
        }

        $i = 0;
        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            $dataType = 'mixed';
            if (isset($parameterInfo['type']) && strpos($parameterInfo['type'], '|') === false) {
                $dataType = isset($parameterInfo['array']) && (bool)$parameterInfo['array'] ? 'array' : $parameterInfo['type'];
            }

            $description = '';
            if (isset($paramAnnotations[$i])) {
                $explodedAnnotation = explode(' ', $paramAnnotations[$i]);
                array_shift($explodedAnnotation);
                array_shift($explodedAnnotation);
                $description = implode(' ', $explodedAnnotation);
            }
            $defaultValue = null;
            if (isset($parameterInfo['defaultValue'])) {
                $defaultValue = $parameterInfo['defaultValue'];
            }
            $methodArgumentDefinitions[$parameterName] = [
                $parameterName,
                $dataType,
                $description,
                ($parameterInfo['optional'] === false),
                $defaultValue,
                true
            ];
            $i++;
        }

        return $methodArgumentDefinitions;
    }
}
