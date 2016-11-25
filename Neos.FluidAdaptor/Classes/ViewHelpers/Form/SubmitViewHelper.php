<?php
namespace Neos\FluidAdaptor\ViewHelpers\Form;

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
 * Creates a submit button.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:form.submit value="Send Mail" />
 * </code>
 * <output>
 * <input type="submit" />
 * </output>
 *
 * <code title="Dummy content for template preview">
 * <f:form.submit name="mySubmit" value="Send Mail"><button>dummy button</button></f:form.submit>
 * </code>
 * <output>
 * <input type="submit" name="mySubmit" value="Send Mail" />
 * </output>
 *
 * @api
 */
class SubmitViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    /**
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the submit button.
     *
     * @return string
     * @api
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->addAdditionalIdentityPropertiesIfNeeded();

        $this->tag->addAttribute('type', 'submit');
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValueAttribute(true));

        return $this->tag->render();
    }
}
