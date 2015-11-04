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

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A ViewHelper to render a section or a specified partial in a template.
 *
 * = Examples =
 *
 * <code title="Rendering partials">
 * <f:render partial="SomePartial" arguments="{foo: someVariable}" />
 * </code>
 * <output>
 * the content of the partial "SomePartial". The content of the variable {someVariable} will be available in the partial as {foo}
 * </output>
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
 *
 * <code title="Passing all variables to a partial">
 * <f:render partial="somePartial" arguments="{_all}" />
 * </code>
 * <output>
 * the content of the partial "somePartial".
 * Using the reserved keyword "_all", all available variables will be passed along to the partial
 * </output>
 *
 * @api
 */
class RenderViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Renders the content.
     *
     * @param string $section Name of section to render. If used in a layout, renders a section of the main content file. If used inside a standard template, renders a section of the same file.
     * @param string $partial Reference to a partial.
     * @param array $arguments Arguments to pass to the partial.
     * @param boolean $optional Set to TRUE, to ignore unknown sections, so the definition of a section inside a template can be optional for a layout
     * @return string
     * @api
     */
    public function render($section = null, $partial = null, $arguments = array(), $optional = false)
    {
        $arguments = $this->loadSettingsIntoArguments($arguments);

        if ($partial !== null) {
            return $this->viewHelperVariableContainer->getView()->renderPartial($partial, $section, $arguments);
        } elseif ($section !== null) {
            return $this->viewHelperVariableContainer->getView()->renderSection($section, $arguments, $optional);
        }
        return '';
    }

    /**
     * If $arguments['settings'] is not set, it is loaded from the TemplateVariableContainer (if it is available there).
     *
     * @param array $arguments
     * @return array
     */
    protected function loadSettingsIntoArguments($arguments)
    {
        if (!isset($arguments['settings']) && $this->templateVariableContainer->exists('settings')) {
            $arguments['settings'] = $this->templateVariableContainer->get('settings');
        }
        return $arguments;
    }
}
