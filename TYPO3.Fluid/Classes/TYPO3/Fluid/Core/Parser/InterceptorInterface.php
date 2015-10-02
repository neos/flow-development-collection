<?php
namespace TYPO3\Fluid\Core\Parser;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface;

/**
 * An interceptor interface. Interceptors are used in the parsing stage to change
 * the syntax tree of a template, e.g. by adding viewhelper nodes.
 */
interface InterceptorInterface
{
    const INTERCEPT_OPENING_VIEWHELPER = 1;
    const INTERCEPT_CLOSING_VIEWHELPER = 2;
    const INTERCEPT_TEXT = 3;
    const INTERCEPT_OBJECTACCESSOR = 4;

    /**
     * The interceptor can process the given node at will and must return a node
     * that will be used in place of the given node.
     *
     * @param NodeInterface $node
     * @param integer $interceptorPosition One of the INTERCEPT_* constants for the current interception point
     * @param ParsingState $parsingState the parsing state
     * @return NodeInterface
     */
    public function process(NodeInterface $node, $interceptorPosition, ParsingState $parsingState);

    /**
     * The interceptor should define at which interception positions it wants to be called.
     *
     * @return array Array of INTERCEPT_* constants
     */
    public function getInterceptionPoints();
}
