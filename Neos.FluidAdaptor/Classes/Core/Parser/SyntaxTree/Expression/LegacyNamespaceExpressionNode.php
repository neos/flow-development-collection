<?php
namespace Neos\FluidAdaptor\Core\Parser\SyntaxTree\Expression;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\AbstractExpressionNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * A node representing an additional namespace declaration inside the template.
 */
class LegacyNamespaceExpressionNode extends AbstractExpressionNode implements ExpressionNodeInterface
{
    /**
     * Pattern which detects namespace declarations made inline.
     * syntax, e.g. {namespace neos=TYPO3\Neos\ViewHelpers}.
     */
    public static $detectionExpression = '/{namespace\\s*([a-z0-9]+)\\s*=\\s*([a-z0-9_\\\\]+)\\s*}/i';

    /**
     * Evaluates the expression stored in this node, in the context of $renderingcontext.
     *
     * @param RenderingContextInterface $renderingContext
     * @param string $expression
     * @param array $matches
     * @return mixed
     */
    public static function evaluateExpression(RenderingContextInterface $renderingContext, $expression, array $matches)
    {
        $renderingContext->getViewHelperResolver()->addNamespace($matches[1], $matches[2]);
    }
}
