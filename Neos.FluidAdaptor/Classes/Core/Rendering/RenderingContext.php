<?php
namespace Neos\FluidAdaptor\Core\Rendering;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Cache\CacheAdaptor;
use Neos\FluidAdaptor\Core\Parser\TemplateParser;
use Neos\FluidAdaptor\Core\Parser\TemplateProcessor\EscapingFlagProcessor;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\FluidAdaptor\Core\Parser\Interceptor\ResourceInterceptor;
use Neos\FluidAdaptor\Core\Parser\SyntaxTree\Expression\LegacyNamespaceExpressionNode;
use Neos\FluidAdaptor\Core\Parser\TemplateProcessor\NamespaceDetectionTemplateProcessor;
use Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer;
use Neos\FluidAdaptor\Core\ViewHelper\ViewHelperResolver;
use Neos\FluidAdaptor\View\TemplatePaths;
use TYPO3Fluid\Fluid\Core\Parser\Configuration;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\CastingExpressionNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\MathExpressionNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\TernaryExpressionNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext as FluidRenderingContext;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * A Fluid rendering context specifically to be used in conjunction with Flow.
 * This knows about the ControllerContext and ObjectManager.
 */
class RenderingContext extends FluidRenderingContext implements FlowAwareRenderingContextInterface
{
    /**
     * List of class names implementing ExpressionNodeInterface
     * which will be consulted when an expression does not match
     * any built-in parser expression types.
     *
     * @var array
     */
    protected $expressionNodeTypes = [
        LegacyNamespaceExpressionNode::class,
        CastingExpressionNode::class,
        MathExpressionNode::class,
        TernaryExpressionNode::class,
    ];

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ViewHelperResolver
     */
    protected $viewHelperResolver;

    /**
     * @Flow\Inject
     * @var CacheAdaptor
     */
    protected $cache;

    /**
     * @var Configuration
     */
    protected $parserConfiguration;

    /**
     * RenderingContext constructor.
     *
     * @param ViewInterface $view
     * @param array $options
     */
    public function __construct(ViewInterface $view, array $options = [])
    {
        parent::__construct($view);
        $this->setTemplateParser(new TemplateParser());
        $this->setViewHelperResolver(new ViewHelperResolver());
        $this->setTemplateProcessors([new EscapingFlagProcessor(), new NamespaceDetectionTemplateProcessor()]);
        $this->setTemplatePaths(new TemplatePaths($options));
        $this->setVariableProvider(new TemplateVariableContainer());
    }

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return ControllerContext
     */
    public function getControllerContext()
    {
        return $this->controllerContext;
    }

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext($controllerContext)
    {
        $this->controllerContext = $controllerContext;
        $request = $controllerContext->getRequest();
        if (!$this->templatePaths instanceof TemplatePaths || !$request instanceof ActionRequest) {
            return;
        }

        $this->templatePaths->setPatternReplacementVariables([
            'packageKey' => $request->getControllerPackageKey(),
            'subPackageKey' => $request->getControllerSubpackageKey(),
            'controllerName' => $request->getControllerName(),
            'action' => $request->getControllerActionName(),
            'format' => $request->getFormat() ?: 'html'
        ]);
    }

    /**
     * @return ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @return \TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface
     * @deprecated use "getVariableProvider"
     */
    public function getTemplateVariableContainer()
    {
        return $this->getVariableProvider();
    }

    /**
     * Build parser configuration
     *
     * @return Configuration
     */
    public function buildParserConfiguration()
    {
        if ($this->parserConfiguration === null) {
            $this->parserConfiguration = parent::buildParserConfiguration();
            $this->parserConfiguration->addInterceptor(new ResourceInterceptor());
        }

        return $this->parserConfiguration;
    }

    /**
     * Set a specific option of this View
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     * @throws \Neos\Flow\Mvc\Exception
     */
    public function setOption($optionName, $value)
    {
        if ($this->templatePaths instanceof TemplatePaths) {
            $this->templatePaths->setOption($optionName, $value);
        }
    }
}
