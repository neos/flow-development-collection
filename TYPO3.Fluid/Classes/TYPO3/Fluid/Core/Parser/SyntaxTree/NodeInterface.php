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

use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Node in the syntax tree.
 */
interface NodeInterface
{
    /**
     * Evaluate all child nodes and return the evaluated results.
     *
     * @param RenderingContextInterface $renderingContext
     * @return mixed Normally, an object is returned - in case it is concatenated with a string, a string is returned.
     */
    public function evaluateChildNodes(RenderingContextInterface $renderingContext);

    /**
     * Returns all child nodes for a given node.
     *
     * @return array<\TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface> A list of nodes
     */
    public function getChildNodes();

    /**
     * Appends a sub node to this node. Is used inside the parser to append children
     *
     * @param NodeInterface $childNode The sub node to add
     * @return void
     */
    public function addChildNode(NodeInterface $childNode);

    /**
     * Evaluates the node - can return not only strings, but arbitary objects.
     *
     * @param RenderingContextInterface $renderingContext
     * @return mixed Evaluated node
     */
    public function evaluate(RenderingContextInterface $renderingContext);
}
