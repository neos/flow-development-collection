<?php
namespace TYPO3\Fluid\Core\Parser\Fixtures;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Enter description here...
 */
class PostParseFacetViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\Fluid\Core\ViewHelper\Facets\PostParseInterface
{
    public static $wasCalled = false;

    public function __construct()
    {
    }

    public static function postParseEvent(\TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $viewHelperNode, array $arguments, \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer)
    {
        self::$wasCalled = true;
    }

    public function initializeArguments()
    {
    }

    public function render()
    {
    }
}
