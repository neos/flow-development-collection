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

use TYPO3\Fluid\Core\Parser;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Abstract node in the syntax tree which has been built.
 */
abstract class AbstractNode implements NodeInterface
{
    /**
     * List of Child Nodes.
     *
     * @var array<\TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface>
     */
    protected $childNodes = array();

    /**
     * Evaluate all child nodes and return the evaluated results.
     *
     * @param RenderingContextInterface $renderingContext
     * @return mixed Normally, an object is returned - in case it is concatenated with a string, a string is returned.
     * @throws Parser\Exception
     */
    public function evaluateChildNodes(RenderingContextInterface $renderingContext)
    {
        $output = null;
        /** @var $subNode NodeInterface */
        foreach ($this->childNodes as $subNode) {
            if ($output === null) {
                $output = $subNode->evaluate($renderingContext);
            } else {
                if (is_object($output)) {
                    if (!method_exists($output, '__toString')) {
                        throw new Parser\Exception('Cannot cast object of type "' . get_class($output) . '" to string.', 1248356140);
                    }
                    $output = $output->__toString();
                } else {
                    $output = (string) $output;
                }
                $subNodeOutput = $subNode->evaluate($renderingContext);

                if (is_object($subNodeOutput)) {
                    if (!method_exists($subNodeOutput, '__toString')) {
                        throw new Parser\Exception('Cannot cast object of type "' . get_class($subNodeOutput) . '" to string.', 1273753083);
                    }
                    $output .= $subNodeOutput->__toString();
                } else {
                    $output .= (string) $subNodeOutput;
                }
            }
        }
        return $output;
    }

    /**
     * Returns all child nodes for a given node.
     * This is especially needed to implement the boolean expression language.
     *
     * @return array<\TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface> A list of nodes
     */
    public function getChildNodes()
    {
        return $this->childNodes;
    }

    /**
     * Appends a sub node to this node. Is used inside the parser to append children
     *
     * @param NodeInterface $childNode The sub node to add
     * @return void
     */
    public function addChildNode(NodeInterface $childNode)
    {
        $this->childNodes[] = $childNode;
    }
}
