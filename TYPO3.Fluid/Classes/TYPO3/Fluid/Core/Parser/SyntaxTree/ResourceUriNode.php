<?php
namespace TYPO3\Fluid\Core\Parser\SyntaxTree;

use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 *
 */
class ResourceUriNode extends AbstractNode
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * ResourceUriNode constructor.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public function evaluate(RenderingContextInterface $renderingContext)
    {
        $dummyState = new ParsingState();
        $viewHelperNode = new ViewHelperNode($renderingContext, 'f', 'uri.resource', $this->arguments, $dummyState);
        return $viewHelperNode->evaluate($renderingContext);
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
