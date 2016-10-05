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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Object\ObjectManagerInterface;
use Neos\FluidAdaptor\Core\Parser\Interceptor\EscapeInterceptor;
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
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 *
 */
class RenderingContext extends \TYPO3Fluid\Fluid\Core\Rendering\RenderingContext implements RenderingContextInterface
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
     * @var \Neos\FluidAdaptor\Core\Cache\CacheAdaptor
     */
    protected $cache;

    /**
     * RenderingContext constructor.
     *
     * @param ViewInterface $view
     * @param array $options
     */
    public function __construct(ViewInterface $view, array $options = [])
    {
        parent::__construct($view);
        $this->setViewHelperResolver(new ViewHelperResolver());
        $this->setTemplateProcessors([new NamespaceDetectionTemplateProcessor()]);
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
        $parserConfiguration = parent::buildParserConfiguration();
        $parserConfiguration->addInterceptor(new ResourceInterceptor());

        return $parserConfiguration;
    }

    /**
     * Set a specific option of this View
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception
     */
    public function setOption($optionName, $value)
    {
        if ($this->templatePaths instanceof TemplatePaths) {
            $this->templatePaths->setOption($optionName, $value);
        }
    }
}
