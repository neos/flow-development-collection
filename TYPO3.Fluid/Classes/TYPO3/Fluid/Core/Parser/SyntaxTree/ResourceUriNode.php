<?php
namespace TYPO3\Fluid\Core\Parser\SyntaxTree;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 *
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
