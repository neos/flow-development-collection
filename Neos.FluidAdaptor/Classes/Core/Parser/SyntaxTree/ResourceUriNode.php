<?php
namespace Neos\FluidAdaptor\Core\Parser\SyntaxTree;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\Parser\Interceptor\ResourceInterceptor;
use Neos\FluidAdaptor\Core\ViewHelper\ViewHelperResolver;
use Neos\FluidAdaptor\ViewHelpers\Uri\ResourceViewHelper;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;

/**
 * A special ViewHelperNode that works via injections and is created by the ResourceInterceptor
 *
 * @see ResourceInterceptor
 */
class ResourceUriNode extends ViewHelperNode
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var ViewHelperResolver
     */
    protected $viewHelperResolver;

    /**
     * @var string
     */
    protected $viewHelperClassName = ResourceViewHelper::class;

    /**
     * @param ViewHelperResolver $viewHelperResolver
     */
    public function injectViewHelperResolver(ViewHelperResolver $viewHelperResolver)
    {
        $this->viewHelperResolver = $viewHelperResolver;
        $this->uninitializedViewHelper = $this->viewHelperResolver->createViewHelperInstanceFromClassName($this->viewHelperClassName);
        $this->uninitializedViewHelper->setViewHelperNode($this);
        $this->argumentDefinitions = $this->viewHelperResolver->getArgumentDefinitionsForViewHelper($this->uninitializedViewHelper);
        $this->rewriteBooleanNodesInArgumentsObjectTree($this->argumentDefinitions, $this->arguments);
        $this->validateArguments($this->argumentDefinitions, $this->arguments);
    }

    /**
     * Constructor.
     *
     * @param NodeInterface[] $arguments Arguments of view helper - each value is a RootNode.
     * @param ParsingState $state
     */
    public function __construct(array $arguments, ParsingState $state)
    {
        $this->arguments = $arguments;
    }
}
