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
 * Array Syntax Tree Node. Handles JSON-like arrays.
 */
class ArrayNode extends AbstractNode
{
    /**
     * An associative array. Each key is a string. Each value is either a literal, or an AbstractNode.
     *
     * @var array
     */
    protected $internalArray = array();

    /**
     * Constructor.
     *
     * @param array $internalArray Array to store
     */
    public function __construct($internalArray)
    {
        $this->internalArray = $internalArray;
    }

    /**
     * Evaluate the array and return an evaluated array
     *
     * @param RenderingContextInterface $renderingContext
     * @return array An associative array with literal values
     */
    public function evaluate(RenderingContextInterface $renderingContext)
    {
        $arrayToBuild = array();
        foreach ($this->internalArray as $key => $value) {
            if ($value instanceof AbstractNode) {
                $arrayToBuild[$key] = $value->evaluate($renderingContext);
            } else {
                // TODO - this case should not happen!
                $arrayToBuild[$key] = $value;
            }
        }
        return $arrayToBuild;
    }

    /**
     * INTERNAL; DO NOT CALL DIRECTLY!
     *
     * @return array
     */
    public function getInternalArray()
    {
        return $this->internalArray;
    }
}
