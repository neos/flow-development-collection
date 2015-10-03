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
use TYPO3\Flow\Object\DependencyInjection\DependencyProxy;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition;
use TYPO3\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface;

/**
 * Node which will call a ViewHelper associated with this node.
 */
class ViewHelperNode extends AbstractNode
{
    /**
     * Class name of view helper
     *
     * @var string
     */
    protected $viewHelperClassName;

    /**
     * Arguments of view helper - References to RootNodes.
     *
     * @var array<NodeInterface>
     */
    protected $arguments = array();

    /**
     * The ViewHelper associated with this node
     *
     * @var AbstractViewHelper
     */
    protected $uninitializedViewHelper = null;

    /**
     * A mapping RenderingContext -> ViewHelper to only re-initialize ViewHelpers
     * when a context change occurs.
     *
     * @var \SplObjectStorage
     */
    protected $viewHelpersByContext = null;

    /**
     * Constructor.
     *
     * @param AbstractViewHelper $viewHelper The view helper
     * @param array $arguments<NodeInterface> Arguments of view helper - each value is a RootNode.
     */
    public function __construct(AbstractViewHelper $viewHelper, array $arguments)
    {
        $this->uninitializedViewHelper = $viewHelper;
        $this->viewHelpersByContext = new \SplObjectStorage();
        $this->arguments = $arguments;
        $this->viewHelperClassName = ($this->uninitializedViewHelper instanceof DependencyProxy) ? $this->uninitializedViewHelper->_getClassName() : get_class($this->uninitializedViewHelper);
    }

    /**
     * Returns the attached (but still uninitialized) ViewHelper for this ViewHelperNode.
     * We need this method because sometimes Interceptors need to ask some information from the ViewHelper.
     *
     * @return AbstractViewHelper the attached ViewHelper, if it is initialized
     */
    public function getUninitializedViewHelper()
    {
        return $this->uninitializedViewHelper;
    }

    /**
     * Get class name of view helper
     *
     * @return string Class Name of associated view helper
     */
    public function getViewHelperClassName()
    {
        return $this->viewHelperClassName;
    }

    /**
     * INTERNAL - only needed for compiling templates
     *
     * @return array
     * @Flow\Internal
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Call the view helper associated with this object.
     *
     * First, it evaluates the arguments of the view helper.
     *
     * If the view helper implements \TYPO3\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface,
     * it calls setChildNodes(array childNodes) on the view helper.
     *
     * Afterwards, checks that the view helper did not leave a variable lying around.
     *
     * @param RenderingContextInterface $renderingContext
     * @return object evaluated node after the view helper has been called.
     */
    public function evaluate(RenderingContextInterface $renderingContext)
    {
        if ($this->viewHelpersByContext->contains($renderingContext)) {
            $viewHelper = $this->viewHelpersByContext->offsetGet($renderingContext);
            $viewHelper->resetState();
        } else {
            $viewHelper = clone $this->uninitializedViewHelper;
            $this->viewHelpersByContext->attach($renderingContext, $viewHelper);
        }

        $evaluatedArguments = array();
        if (count($viewHelper->prepareArguments())) {
            /** @var $argumentDefinition ArgumentDefinition */
            foreach ($viewHelper->prepareArguments() as $argumentName => $argumentDefinition) {
                if (isset($this->arguments[$argumentName])) {
                    /** @var $argumentValue NodeInterface */
                    $argumentValue = $this->arguments[$argumentName];
                    $evaluatedArguments[$argumentName] = $argumentValue->evaluate($renderingContext);
                } else {
                    $evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
                }
            }
        }

        $viewHelper->setArguments($evaluatedArguments);
        $viewHelper->setViewHelperNode($this);
        $viewHelper->setRenderingContext($renderingContext);

        if ($viewHelper instanceof ChildNodeAccessInterface) {
            $viewHelper->setChildNodes($this->childNodes);
        }

        $output = $viewHelper->initializeArgumentsAndRender();

        return $output;
    }

    /**
     * Clean up for serializing.
     *
     * @return array
     */
    public function __sleep()
    {
        return array('viewHelperClassName', 'arguments', 'childNodes');
    }
}
