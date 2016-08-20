<?php
namespace TYPO3\Fluid\ViewHelpers;

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
use TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\Fluid\Core\ViewHelper\Facets\PostParseInterface;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;

/**
 * A ViewHelper to declare sections in templates for later use with e.g. the RenderViewHelper.
 *
 * = Examples =
 *
 * <code title="Rendering sections">
 * <f:section name="someSection">This is a section. {foo}</f:section>
 * <f:render section="someSection" arguments="{foo: someVariable}" />
 * </code>
 * <output>
 * the content of the section "someSection". The content of the variable {someVariable} will be available in the partial as {foo}
 * </output>
 *
 * <code title="Rendering recursive sections">
 * <f:section name="mySection">
 *  <ul>
 *    <f:for each="{myMenu}" as="menuItem">
 *      <li>
 *        {menuItem.text}
 *        <f:if condition="{menuItem.subItems}">
 *          <f:render section="mySection" arguments="{myMenu: menuItem.subItems}" />
 *        </f:if>
 *      </li>
 *    </f:for>
 *  </ul>
 * </f:section>
 * <f:render section="mySection" arguments="{myMenu: menu}" />
 * </code>
 * <output>
 * <ul>
 *   <li>menu1
 *     <ul>
 *       <li>menu1a</li>
 *       <li>menu1b</li>
 *     </ul>
 *   </li>
 * [...]
 * (depending on the value of {menu})
 * </output>
 *
 * @api
 */
class SectionViewHelper extends AbstractViewHelper implements PostParseInterface, CompilableInterface
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('name', 'string', 'Name of the section', true);
    }

    /**
     * Save the associated ViewHelper node in a static public class variable.
     * called directly after the ViewHelper was built.
     *
     * @param ViewHelperNode $syntaxTreeNode
     * @param array $viewHelperArguments<TextNode>
     * @param TemplateVariableContainer $variableContainer
     * @return void
     */
    public static function postParseEvent(ViewHelperNode $syntaxTreeNode, array $viewHelperArguments, TemplateVariableContainer $variableContainer)
    {
        /** @var $nameArgument TextNode */
        $nameArgument = $viewHelperArguments['name'];
        $sectionName = $nameArgument->getText();
        if (!$variableContainer->exists('sections')) {
            $variableContainer->add('sections', array());
        }
        $sections = $variableContainer->get('sections');
        $sections[$sectionName] = $syntaxTreeNode;
        $variableContainer->remove('sections');
        $variableContainer->add('sections', $sections);
    }

    /**
     * Rendering directly returns all child nodes.
     *
     * @return string HTML String of all child nodes.
     * @api
     */
    public function render()
    {
        if ($this->viewHelperVariableContainer->exists(\TYPO3\Fluid\ViewHelpers\SectionViewHelper::class, 'isCurrentlyRenderingSection')) {
            $this->viewHelperVariableContainer->remove(\TYPO3\Fluid\ViewHelpers\SectionViewHelper::class, 'isCurrentlyRenderingSection');
            return $this->renderChildren();
        }
        return '';
    }

    /**
     * The inner contents of a section should not be rendered.
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
        return '\'\'';
    }
}
