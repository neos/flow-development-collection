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
use TYPO3Fluid\Fluid\Core\Parser\TemplateParser;
use TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\PassthroughSourceModifierTemplateProcessor;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext as FluidRenderingContext;

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
     * @var ControllerContext|null
     */
    protected ControllerContext|null $controllerContext = null;

    /**
     * @var ObjectManagerInterface|null
     */
    protected ObjectManagerInterface|null $objectManager = null;

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
     * @var Configuration|null
     */
    protected Configuration|null $parserConfiguration = null;

    /**
     * RenderingContext constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct();
        $this->setTemplateParser(new TemplateParser());
        $this->setViewHelperResolver(new ViewHelperResolver());
        $this->setTemplateProcessors([
            new EscapingFlagProcessor(),
            new PassthroughSourceModifierTemplateProcessor(),
            new NamespaceDetectionTemplateProcessor()
        ]);
        $templatePaths = new TemplatePaths();
        $templatePaths->setOptions($options);
        $this->setTemplatePaths($templatePaths);
        $this->setVariableProvider(new TemplateVariableContainer());
    }

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return ControllerContext
     */
    public function getControllerContext(): ControllerContext
    {
        return $this->controllerContext;
    }

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
        $request = $controllerContext->getRequest();
        if (!$this->templatePaths instanceof TemplatePaths || !$request instanceof ActionRequest) {
            return;
        }

        $this->templatePaths->setPatternReplacementVariables([
            'packageKey' => (string)$request->getControllerPackageKey(),
            'subPackageKey' => (string)$request->getControllerSubpackageKey(),
            'controllerName' => $request->getControllerName(),
            'action' => $request->getControllerActionName(),
            'format' => $request->getFormat() ?: 'html'
        ]);
    }

    /**
     * @return ObjectManagerInterface
     */
    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    /**
     * Build parser configuration
     *
     * @return Configuration
     */
    public function buildParserConfiguration(): Configuration
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
    public function setOption(string $optionName, $value): void
    {
        if ($this->templatePaths instanceof TemplatePaths) {
            $this->templatePaths->setOption($optionName, $value);
        }
    }
}
