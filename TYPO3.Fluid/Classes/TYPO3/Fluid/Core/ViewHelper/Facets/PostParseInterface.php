<?php
namespace TYPO3\Fluid\Core\ViewHelper\Facets;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;

/**
 * Post Parse Facet. Your view helper should implement this if you want a callback
 * to be called directly after the syntax tree node corresponding to this view
 * helper has been built.
 *
 * In the callback, it is possible to store some variables inside the
 * parseVariableContainer (which is different from the runtime variable container!).
 * This implicates that you usually have to adjust the \TYPO3\Fluid\View\TemplateView
 * in case you implement this facet.
 *
 * Normally, this facet is not needed, except in really really rare cases.
 */
interface PostParseInterface
{
    /**
     * Callback which is called directly after the corresponding syntax tree
     * node to this view helper has been built.
     * This is a parse-time callback, which does not change the rendering of a
     * view helper.
     *
     * You can store some data inside the variableContainer given here, which
     * can be used f.e. inside the TemplateView.
     *
     * @param ViewHelperNode $syntaxTreeNode The current node in the syntax tree corresponding to this view helper.
     * @param array $viewHelperArguments View helper arguments as an array of SyntaxTrees. If you really need an argument, make sure to call $viewHelperArguments[$argName]->render(...)!
     * @param TemplateVariableContainer $variableContainer Variable container you can use to pass on some variables to the view.
     * @return void
     */
    public static function postParseEvent(ViewHelperNode $syntaxTreeNode, array $viewHelperArguments, TemplateVariableContainer $variableContainer);
}
