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
 * Text Syntax Tree Node - is a container for strings.
 */
class TextNode extends AbstractNode
{
    /**
     * Contents of the text node
     *
     * @var string
     */
    protected $text;

    /**
     * Constructor.
     *
     * @param string $text text to store in this textNode
     * @throws Parser\Exception
     */
    public function __construct($text)
    {
        if (!is_string($text)) {
            throw new Parser\Exception('Text node requires an argument of type string, "' . gettype($text) . '" given.');
        }
        $this->text = $text;
    }

    /**
     * Return the text associated to the syntax tree. Text from child nodes is
     * appended to the text in the node's own text.
     *
     * @param RenderingContextInterface $renderingContext
     * @return string the text stored in this node/subtree.
     */
    public function evaluate(RenderingContextInterface $renderingContext)
    {
        return $this->text . $this->evaluateChildNodes($renderingContext);
    }

    /**
     * Getter for text
     *
     * @return string The text of this node
     */
    public function getText()
    {
        return $this->text;
    }
}
