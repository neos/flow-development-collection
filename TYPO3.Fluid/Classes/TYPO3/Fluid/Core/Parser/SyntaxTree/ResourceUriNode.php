<?php
namespace TYPO3\Fluid\Core\Parser\SyntaxTree;

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
use TYPO3\Fluid\Core\Parser\Interceptor\ResourceInterceptor;
use TYPO3\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

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
