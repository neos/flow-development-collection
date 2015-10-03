<?php
namespace TYPO3\Fluid\Core\Parser\Interceptor;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Fluid\Core\Parser\InterceptorInterface;
use TYPO3\Fluid\Core\Parser\ParsingState;
use TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * An interceptor adding the "Htmlspecialchars" viewhelper to the suitable places.
 */
class Escape implements InterceptorInterface
{
    /**
     * Is the interceptor enabled right now for child nodes?
     *
     * @var boolean
     */
    protected $childrenEscapingEnabled = true;

    /**
     * A stack of ViewHelperNodes which currently disable the interceptor.
     * Needed to enable the interceptor again.
     *
     * @var array<\TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface>
     */
    protected $viewHelperNodesWhichDisableTheInterceptor = array();

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Inject object manager
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Adds a ViewHelper node using the Format\HtmlspecialcharsViewHelper to the given node.
     * If "escapingInterceptorEnabled" in the ViewHelper is FALSE, will disable itself inside the ViewHelpers body.
     *
     * @param NodeInterface $node
     * @param integer $interceptorPosition One of the INTERCEPT_* constants for the current interception point
     * @param ParsingState $parsingState the current parsing state. Not needed in this interceptor.
     * @return NodeInterface
     */
    public function process(NodeInterface $node, $interceptorPosition, ParsingState $parsingState)
    {
        if ($interceptorPosition === InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER) {
            /** @var $node ViewHelperNode */
            if (!$node->getUninitializedViewHelper()->isChildrenEscapingEnabled()) {
                $this->childrenEscapingEnabled = false;
                $this->viewHelperNodesWhichDisableTheInterceptor[] = $node;
            }
        } elseif ($interceptorPosition === InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER) {
            if (end($this->viewHelperNodesWhichDisableTheInterceptor) === $node) {
                array_pop($this->viewHelperNodesWhichDisableTheInterceptor);
                if (count($this->viewHelperNodesWhichDisableTheInterceptor) === 0) {
                    $this->childrenEscapingEnabled = true;
                }
            }
            /** @var $node ViewHelperNode */
            if ($this->childrenEscapingEnabled && $node->getUninitializedViewHelper()->isOutputEscapingEnabled()) {
                $node = $this->wrapNode($node);
            }
        } elseif ($this->childrenEscapingEnabled && $node instanceof ObjectAccessorNode) {
            $node = $this->wrapNode($node);
        }
        return $node;
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    protected function wrapNode(NodeInterface $node)
    {
        $escapeViewHelper = $this->objectManager->get('TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper');
        return $this->objectManager->get(
            'TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode',
            $escapeViewHelper,
            array('value' => $node)
        );
    }

    /**
     * This interceptor wants to hook into object accessor creation, and opening / closing ViewHelpers.
     *
     * @return array Array of INTERCEPT_* constants
     */
    public function getInterceptionPoints()
    {
        return array(
            InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER,
            InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER,
            InterceptorInterface::INTERCEPT_OBJECTACCESSOR
        );
    }
}
