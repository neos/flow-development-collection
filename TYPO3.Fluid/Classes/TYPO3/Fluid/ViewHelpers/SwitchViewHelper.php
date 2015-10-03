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

use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface;

/**
 * Switch view helper which can be used to render content depending on a value or expression.
 * Implements what a basic switch()-PHP-method does.
 *
 * An optional default case can be specified which is rendered if none of the "f:case" conditions matches.
 *
 * = Examples =
 *
 * <code title="Simple Switch statement">
 * <f:switch expression="{person.gender}">
 *   <f:case value="male">Mr.</f:case>
 *   <f:case value="female">Mrs.</f:case>
 *   <f:defaultCase>Mr. / Mrs.</f:defaultCase>
 * </f:switch>
 * </code>
 * <output>
 * "Mr.", "Mrs." or "Mr. / Mrs." (depending on the value of {person.gender})
 * </output>
 *
 * Note: Using this view helper can be a sign of weak architecture. If you end up using it extensively
 * you might want to consider restructuring your controllers/actions and/or use partials and sections.
 * E.g. the above example could be achieved with <f:render partial="title.{person.gender}" /> and the partials
 * "title.male.html", "title.female.html", ...
 * Depending on the scenario this can be easier to extend and possibly contains less duplication.
 *
 * @api
 */
class SwitchViewHelper extends AbstractViewHelper implements ChildNodeAccessInterface
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * An array of \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode
     * @var array
     */
    private $childNodes = array();

    /**
     * @var mixed
     */
    protected $backupSwitchExpression = null;

    /**
     * @var boolean
     */
    protected $backupBreakState = false;

    /**
     * Setter for ChildNodes - as defined in ChildNodeAccessInterface
     *
     * @param array $childNodes Child nodes of this syntax tree node
     * @return void
     */
    public function setChildNodes(array $childNodes)
    {
        $this->childNodes = $childNodes;
    }

    /**
     * @param mixed $expression
     * @return string the rendered string
     * @api
     */
    public function render($expression)
    {
        $content = '';
        $this->backupSwitchState();
        $templateVariableContainer = $this->renderingContext->getViewHelperVariableContainer();

        $templateVariableContainer->addOrUpdate('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression', $expression);
        $templateVariableContainer->addOrUpdate('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break', false);

        $defaultCaseViewHelperNode = null;
        foreach ($this->childNodes as $childNode) {
            if ($childNode instanceof ViewHelperNode && $childNode->getViewHelperClassName() === 'TYPO3\Fluid\ViewHelpers\DefaultCaseViewHelper') {
                $defaultCaseViewHelperNode = $childNode;
            }
            if (!$childNode instanceof ViewHelperNode || $childNode->getViewHelperClassName() !== 'TYPO3\Fluid\ViewHelpers\CaseViewHelper') {
                continue;
            }
            $content = $childNode->evaluate($this->renderingContext);
            if ($templateVariableContainer->get('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break') === true) {
                $defaultCaseViewHelperNode = null;
                break;
            }
        }

        if ($defaultCaseViewHelperNode !== null) {
            $content = $defaultCaseViewHelperNode->evaluate($this->renderingContext);
        }

        $templateVariableContainer->remove('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression');
        $templateVariableContainer->remove('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break');

        $this->restoreSwitchState();
        return $content;
    }

    /**
     * Backups "switch expression" and "break" state of a possible parent switch ViewHelper to support nesting
     *
     * @return void
     */
    protected function backupSwitchState()
    {
        if ($this->renderingContext->getViewHelperVariableContainer()->exists('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')) {
            $this->backupSwitchExpression = $this->renderingContext->getViewHelperVariableContainer()->get('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression');
        }
        if ($this->renderingContext->getViewHelperVariableContainer()->exists('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break')) {
            $this->backupBreakState = $this->renderingContext->getViewHelperVariableContainer()->get('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break');
        }
    }

    /**
     * Restores "switch expression" and "break" states that might have been backed up in backupSwitchState() before
     *
     * @return void
     */
    protected function restoreSwitchState()
    {
        if ($this->backupSwitchExpression !== null) {
            $this->renderingContext->getViewHelperVariableContainer()->addOrUpdate('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression', $this->backupSwitchExpression);
        }
        if ($this->backupBreakState !== false) {
            $this->renderingContext->getViewHelperVariableContainer()->addOrUpdate('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break', true);
        }
    }
}
