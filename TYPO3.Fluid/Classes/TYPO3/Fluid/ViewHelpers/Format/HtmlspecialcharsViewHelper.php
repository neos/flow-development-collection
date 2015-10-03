<?php
namespace TYPO3\Fluid\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Applies htmlspecialchars() escaping to a value
 *
 * @see http://www.php.net/manual/function.htmlspecialchars.php
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.htmlspecialchars>{text}</f:format.htmlspecialchars>
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.htmlspecialchars(encoding: 'ISO-8859-1')}
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).
 * </output>
 *
 * @api
 */
class HtmlspecialcharsViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * Disable the output escaping interceptor so that the value is not htmlspecialchar'd twice
     *
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs htmlspecialchars() function.
     *
     * @param string $value string to format
     * @param boolean $keepQuotes if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)
     * @param string $encoding
     * @param boolean $doubleEncode If FALSE existing html entities won't be encoded, the default is to convert everything.
     * @return string the altered string
     * @see http://www.php.net/manual/function.htmlspecialchars.php
     * @api
     */
    public function render($value = null, $keepQuotes = false, $encoding = 'UTF-8', $doubleEncode = true)
    {
        if ($value === null) {
            $value = $this->renderChildren();
        }

        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return $value;
        }
        $flags = $keepQuotes ? ENT_NOQUOTES : ENT_COMPAT;

        return htmlspecialchars($value, $flags, $encoding, $doubleEncode);
    }

    /**
     * This ViewHelper is used a *lot* because it is used by the escape interceptor.
     * Therefore we render it to raw PHP code during compilation
     *
     * @param string $argumentsVariableName
     * @param string $renderChildrenClosureVariableName
     * @param string $initializationPhpCode
     * @param AbstractNode $syntaxTreeNode
     * @param TemplateCompiler $templateCompiler
     * @return string
     */
    public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, AbstractNode $syntaxTreeNode, TemplateCompiler $templateCompiler)
    {
        $valueVariableName = $templateCompiler->variableName('value');
        $initializationPhpCode .= sprintf('%1$s = (%2$s[\'value\'] !== NULL ? %2$s[\'value\'] : %3$s());', $valueVariableName, $argumentsVariableName, $renderChildrenClosureVariableName) . chr(10);

        return sprintf('!is_string(%1$s) && !(is_object(%1$s) && method_exists(%1$s, \'__toString\')) ? %1$s : htmlspecialchars(%1$s, (%2$s[\'keepQuotes\'] ? ENT_NOQUOTES : ENT_COMPAT), %2$s[\'encoding\'], %2$s[\'doubleEncode\'])',
            $valueVariableName, $argumentsVariableName);
    }
}
