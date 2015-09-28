<?php
namespace TYPO3\Fluid\Core\Parser\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
