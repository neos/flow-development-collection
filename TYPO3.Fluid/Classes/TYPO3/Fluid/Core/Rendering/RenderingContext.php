<?php
namespace TYPO3\Fluid\Core\Rendering;

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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Fluid\Core\Parser\Interceptor\ResourceInterceptor;
use TYPO3\Fluid\Core\Parser\SyntaxTree\Expression\LegacyNamespaceExpressionNode;
use TYPO3\Fluid\Core\Variables\VariableProvider;
use TYPO3\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\Fluid\View\TemplatePaths;
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
     * @Flow\Inject
     * @var ViewHelperResolver
     */
    protected $viewHelperResolver;

    /**
     * @Flow\Inject
     * @var \TYPO3\Fluid\Core\Cache\CacheAdaptor
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
        $this->setTemplatePaths(new TemplatePaths($options));
        $this->setVariableProvider(new VariableProvider());
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
            'format' => $request->getFormat()
        ]);
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
