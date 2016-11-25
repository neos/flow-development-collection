<?php
namespace Neos\FluidAdaptor\Core\ViewHelper\Facets;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Child Node Access Facet. View Helpers should implement this interface if they
 * need access to the direct children in the Syntax Tree at rendering-time.
 * This might happen if you only want to selectively render a part of the syntax
 * tree depending on some conditions.
 * To render sub nodes, you can fetch the RenderingContext via $this->renderingContext.
 *
 * In most cases, you will not need this facet, and it is NO PUBLIC API!
 * Right now it is only used internally for conditions, so by subclassing Neos\FluidAdaptor\Core\ViewHelpers\AbstractConditionViewHelper, this should be all you need.
 *
 * See \Neos\FluidAdaptor\ViewHelpers\IfViewHelper for an example how it is used.
 */
interface ChildNodeAccessInterface
{
    /**
     * Sets the direct child nodes of the current syntax tree node.
     *
     * @param array<\Neos\FluidAdaptor\Core\Parser\SyntaxTree\AbstractNode> $childNodes
     * @return void
     */
    public function setChildNodes(array $childNodes);
}
