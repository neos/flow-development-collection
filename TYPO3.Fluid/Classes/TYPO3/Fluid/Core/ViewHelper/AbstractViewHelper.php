<?php
namespace TYPO3\Fluid\Core\ViewHelper;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3\Fluid\Core\Parser;
use TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Fluid;

/**
 * The abstract base class for all view helpers.
 *
 * @api
 */
abstract class AbstractViewHelper
{
    /**
     * TRUE if arguments have already been initialized
     * @var boolean
     */
    private $argumentsInitialized = false;

    /**
     * Stores all \TYPO3\Fluid\ArgumentDefinition instances
     * @var array
     */
    private $argumentDefinitions = array();

    /**
     * Cache of argument definitions; the key is the ViewHelper class name, and the
     * value is the array of argument definitions.
     *
     * In our benchmarks, this cache leads to a 40% improvement when using a certain
     * ViewHelper class many times throughout the rendering process.
     * @var array
     */
    private static $argumentDefinitionCache = array();

    /**
     * Current view helper node
     * @var ViewHelperNode
     */
    private $viewHelperNode;

    /**
     * Arguments array.
     * @var array
     * @api
     */
    protected $arguments;

    /**
     * Current variable container reference.
     * @var \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer
     * @api
     */
    protected $templateVariableContainer;

    /**
     * Controller Context to use
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     * @api
     */
    protected $controllerContext;

    /**
     * @var RenderingContextInterface
     */
    protected $renderingContext;

    /**
     * @var \Closure
     */
    protected $renderChildrenClosure = null;

    /**
     * ViewHelper Variable Container
     * @var \TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer
     * @api
     */
    protected $viewHelperVariableContainer;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * With this flag, you can disable the escaping interceptor inside this ViewHelper.
     *
     * @var boolean
     * @deprecated since 3.0 Use $escapeChildren instead!
     */
    protected $escapingInterceptorEnabled = null;

    /**
     * Specifies whether the escaping interceptors should be disabled or enabled for the result of renderChildren() calls within this ViewHelper
     * @see isChildrenEscapingEnabled()
     *
     * Note: If this is NULL the value of $this->escapingInterceptorEnabled is considered for backwards compatibility
     *
     * @var boolean
     * @api
     */
    protected $escapeChildren = null;

    /**
     * Specifies whether the escaping interceptors should be disabled or enabled for the render-result of this ViewHelper
     * @see isOutputEscapingEnabled()
     *
     * @var boolean
     * @api
     */
    protected $escapeOutput = null;

    /**
     * @param ObjectManagerInterface $objectManager
     * @return void
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
     * @param array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param RenderingContextInterface $renderingContext
     * @return void
     */
    public function setRenderingContext(RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
        $this->templateVariableContainer = $renderingContext->getTemplateVariableContainer();
        if ($renderingContext->getControllerContext() !== null) {
            $this->controllerContext = $renderingContext->getControllerContext();
        }
        $this->viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
    }

    /**
     * Returns whether the escaping interceptors should be disabled or enabled for the result of renderChildren() calls within this ViewHelper
     *
     * Note: This method is no public API, use $this->escapeChildren instead!
     *
     * @return boolean
     */
    public function isChildrenEscapingEnabled()
    {
        if ($this->escapeChildren !== null) {
            return $this->escapeChildren !== false;
        }
        return $this->escapingInterceptorEnabled !== false;
    }

    /**
     * Returns whether the escaping interceptors should be disabled or enabled inside the tags contents.
     *
     * THIS METHOD MIGHT CHANGE WITHOUT NOTICE; NO PUBLIC API!
     *
     * @deprecated since 3.0 use isChildrenEscapingEnabled() instead
     * @return boolean
     */
    public function isEscapingInterceptorEnabled()
    {
        return $this->isChildrenEscapingEnabled();
    }

    /**
     * Returns whether the escaping interceptors should be disabled or enabled for the render-result of this ViewHelper
     *
     * Note: This method is no public API, use $this->escapeChildren instead!
     *
     * @return boolean
     */
    public function isOutputEscapingEnabled()
    {
        return $this->escapeOutput !== false;
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
     * @return \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper $this, to allow chaining.
     * @throws Exception
     * @api
     */
    protected function registerArgument($name, $type, $description, $required = false, $defaultValue = null)
    {
        if (array_key_exists($name, $this->argumentDefinitions)) {
            throw new Exception('Argument "' . $name . '" has already been defined, thus it should not be defined again.', 1253036401);
        }
        $this->argumentDefinitions[$name] = new ArgumentDefinition($name, $type, $description, $required, $defaultValue);
        return $this;
    }

    /**
     * Overrides a registered argument. Call this method from your ViewHelper subclass
     * inside the initializeArguments() method if you want to override a previously registered argument.
     * @see registerArgument()
     *
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param boolean $required If TRUE, argument is required. Defaults to FALSE.
     * @param mixed $defaultValue Default value of argument
     * @return \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper $this, to allow chaining.
     * @throws Exception
     * @api
     */
    protected function overrideArgument($name, $type, $description, $required = false, $defaultValue = null)
    {
        if (!array_key_exists($name, $this->argumentDefinitions)) {
            throw new Exception('Argument "' . $name . '" has not been defined, thus it can\'t be overridden.', 1279212461);
        }
        $this->argumentDefinitions[$name] = new ArgumentDefinition($name, $type, $description, $required, $defaultValue);
        return $this;
    }

    /**
     * Sets all needed attributes needed for the rendering. Called by the
     * framework. Populates $this->viewHelperNode.
     * This is PURELY INTERNAL! Never override this method!!
     *
     * @param ViewHelperNode $node View Helper node to be set.
     * @return void
     */
    public function setViewHelperNode(ViewHelperNode $node)
    {
        $this->viewHelperNode = $node;
    }

    /**
     * Called when being inside a cached template.
     *
     * @param \Closure $renderChildrenClosure
     * @return void
     */
    public function setRenderChildrenClosure(\Closure $renderChildrenClosure)
    {
        $this->renderChildrenClosure = $renderChildrenClosure;
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

        return $this->callRenderMethod();
    }

    /**
     * Call the render() method and handle errors.
     *
     * @return string the rendered ViewHelper
     * @throws Exception
     */
    protected function callRenderMethod()
    {
        $renderMethodParameters = array();
        /** @var $argumentDefinition ArgumentDefinition */
        foreach ($this->argumentDefinitions as $argumentName => $argumentDefinition) {
            if ($argumentDefinition->isMethodParameter()) {
                $renderMethodParameters[$argumentName] = $this->arguments[$argumentName];
            }
        }

        try {
            return call_user_func_array(array($this, 'render'), $renderMethodParameters);
        } catch (Exception $exception) {
            if (!$this->objectManager->getContext()->isProduction()) {
                throw $exception;
            } else {
                $this->systemLogger->log('An Exception was captured: ' . $exception->getMessage() . '(' . $exception->getCode() . ')', LOG_ERR, 'TYPO3.Fluid', get_class($this));
                return '';
            }
        }
    }

    /**
     * Initializes the view helper before invoking the render method.
     *
     * Override this method to solve tasks before the view helper content is rendered.
     *
     * @return void
     * @api
     */
    public function initialize()
    {
    }

    /**
     * Helper method which triggers the rendering of everything between the
     * opening and the closing tag.
     *
     * @return mixed The finally rendered child nodes.
     * @api
     */
    public function renderChildren()
    {
        if ($this->renderChildrenClosure !== null) {
            $closure = $this->renderChildrenClosure;
            return $closure();
        }
        return $this->viewHelperNode->evaluateChildNodes($this->renderingContext);
    }

    /**
     * Helper which is mostly needed when calling renderStatic() from within
     * render().
     *
     * No public API yet.
     *
     * @return \Closure
     */
    protected function buildRenderChildrenClosure()
    {
        $self = $this;
        return function () use ($self) {
            return $self->renderChildren();
        };
    }

    /**
     * Initialize all arguments and return them
     *
     * @return array Array of \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition instances.
     */
    public function prepareArguments()
    {
        if (!$this->argumentsInitialized) {
            $thisClassName = get_class($this);
            if (isset(self::$argumentDefinitionCache[$thisClassName])) {
                $this->argumentDefinitions = self::$argumentDefinitionCache[$thisClassName];
            } else {
                $this->registerRenderMethodArguments();
                $this->initializeArguments();
                self::$argumentDefinitionCache[$thisClassName] = $this->argumentDefinitions;
            }
            $this->argumentsInitialized = true;
        }
        return $this->argumentDefinitions;
    }

    /**
     * Register method arguments for "render" by analysing the doc comment above.
     *
     * @return void
     * @throws Parser\Exception
     */
    private function registerRenderMethodArguments()
    {
        $methodParameters = static::getRenderMethodParameters($this->objectManager);
        if (count($methodParameters) === 0) {
            return;
        }

        if (Fluid::$debugMode) {
            $methodTags = static::getRenderMethodTagsValues($this->objectManager);

            $paramAnnotations = array();
            if (isset($methodTags['param'])) {
                $paramAnnotations = $methodTags['param'];
            }
        }

        $i = 0;
        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = $parameterInfo['type'];
            } elseif ($parameterInfo['array']) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new Parser\Exception('could not determine type of argument "' . $parameterName . '" of the render-method in ViewHelper "' . get_class($this) . '". Either the methods docComment is invalid or some PHP optimizer strips off comments.', 1242292003);
            }

            $description = '';
            if (Fluid::$debugMode && isset($paramAnnotations[$i])) {
                $explodedAnnotation = explode(' ', $paramAnnotations[$i]);
                array_shift($explodedAnnotation);
                array_shift($explodedAnnotation);
                $description = implode(' ', $explodedAnnotation);
            }
            $defaultValue = null;
            if (isset($parameterInfo['defaultValue'])) {
                $defaultValue = $parameterInfo['defaultValue'];
            }
            $this->argumentDefinitions[$parameterName] = new ArgumentDefinition($parameterName, $dataType, $description, ($parameterInfo['optional'] === false), $defaultValue, true);
            $i++;
        }
    }

    /**
     * Returns a map of render method parameters.
     *
     * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
     * @return array Array of render method parameters
     * @Flow\CompileStatic
     */
    public static function getRenderMethodParameters($objectManager)
    {
        $className = get_called_class();
        if (!is_callable(array($className, 'render'))) {
            return array();
        }

        $reflectionService = $objectManager->get(\TYPO3\Flow\Reflection\ReflectionService::class);
        return $reflectionService->getMethodParameters($className, 'render');
    }

    /**
     * Returns a map of render method tag values.
     *
     * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
     * @return array An array of tags and their values or an empty array if no tags were found
     * @Flow\CompileStatic
     */
    public static function getRenderMethodTagsValues($objectManager)
    {
        $className = get_called_class();
        if (!is_callable(array($className, 'render'))) {
            return array();
        }

        $reflectionService = $objectManager->get(\TYPO3\Flow\Reflection\ReflectionService::class);
        return $reflectionService->getMethodTagsValues($className, 'render');
    }

    /**
     * Validate arguments, and throw exception if arguments do not validate.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validateArguments()
    {
        $argumentDefinitions = $this->prepareArguments();
        if (!count($argumentDefinitions)) {
            return;
        }

        /** @var $registeredArgument ArgumentDefinition */
        foreach ($argumentDefinitions as $argumentName => $registeredArgument) {
            if ($this->hasArgument($argumentName)) {
                if ($this->arguments[$argumentName] === $registeredArgument->getDefaultValue()) {
                    continue;
                }

                $type = $registeredArgument->getType();
                if ($type === 'array') {
                    if (!is_array($this->arguments[$argumentName]) && !$this->arguments[$argumentName] instanceof \ArrayAccess && !$this->arguments[$argumentName] instanceof \Traversable) {
                        throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "array", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '"', 1237900529);
                    }
                } elseif ($type === 'boolean') {
                    if (!is_bool($this->arguments[$argumentName])) {
                        throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "boolean", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1240227732);
                    }
                } elseif (class_exists($type, false)) {
                    if (!($this->arguments[$argumentName] instanceof $type)) {
                        if (is_object($this->arguments[$argumentName])) {
                            throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "' . $type . '", but is of type "' . get_class($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1256475114);
                        } else {
                            throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "' . $type . '", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1256475113);
                        }
                    }
                }
            }
        }
    }

    /**
     * Initialize all arguments. You need to override this method and call
     * $this->registerArgument(...) inside this method, to register all your arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
    }

    /**
     * Render method you need to implement for your custom view helper.
     * Available objects at this point are $this->arguments, and $this->templateVariableContainer.
     *
     * Besides, you often need $this->renderChildren().
     *
     * @return string rendered string, view helper specific
     * @api
     */
    // abstract public function render();

    /**
     * Tests if the given $argumentName is set, and not NULL.
     *
     * @param string $argumentName
     * @return boolean TRUE if $argumentName is found, FALSE otherwise
     * @api
     */
    protected function hasArgument($argumentName)
    {
        return isset($this->arguments[$argumentName]) && $this->arguments[$argumentName] !== null;
    }

    /**
     * Default implementation for CompilableInterface. By default,
     * inserts a renderStatic() call to itself.
     *
     * You only should override this method *when you absolutely know what you
     * are doing*, and really want to influence the generated PHP code during
     * template compilation directly.
     *
     * @param string $argumentsVariableName
     * @param string $renderChildrenClosureVariableName
     * @param string $initializationPhpCode
     * @param AbstractNode $syntaxTreeNode
     * @param TemplateCompiler $templateCompiler
     * @return string
     * @see \TYPO3\Fluid\Core\ViewHelper\Facets\CompilableInterface
     */
    public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, AbstractNode $syntaxTreeNode, TemplateCompiler $templateCompiler)
    {
        return sprintf('%s::renderStatic(%s, %s, $renderingContext)',
            get_class($this), $argumentsVariableName, $renderChildrenClosureVariableName);
    }

    /**
     * Default implementation for CompilableInterface. See CompilableInterface
     * for a detailed description of this method.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     * @see \TYPO3\Fluid\Core\ViewHelper\Facets\CompilableInterface
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return null;
    }

    /**
     * Resets the ViewHelper state.
     *
     * Overwrite this method if you need to get a clean state of your ViewHelper.
     *
     * @return void
     */
    public function resetState()
    {
    }
}
