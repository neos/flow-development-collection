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
 * Creates a button.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:form.button>Send Mail</f:form.button>
 * </code>
 * <output>
 * <button type="submit" name="" value="">Send Mail</button>
 * </output>
 *
 * <code title="Disabled cancel button with some HTML5 attributes">
 * <f:form.button type="reset" name="buttonName" value="buttonValue" disabled="disabled" formmethod="post" formnovalidate="formnovalidate">Cancel</f:form.button>
 * </code>
 * <output>
 * <button disabled="disabled" formmethod="post" formnovalidate="formnovalidate" type="reset" name="myForm[buttonName]" value="buttonValue">Cancel</button>
 * </output>
 *
 * @api
 */
class ButtonViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'button';

    /**
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('autofocus', 'string', 'Specifies that a button should automatically get focus when the page loads');
        $this->registerTagAttribute('disabled', 'boolean', 'Specifies that the input element should be disabled when the page loads', false, false);
        $this->registerTagAttribute('form', 'string', 'Specifies one or more forms the button belongs to');
        $this->registerTagAttribute('formaction', 'string', 'Specifies where to send the form-data when a form is submitted. Only for type="submit"');
        $this->registerTagAttribute('formenctype', 'string', 'Specifies how form-data should be encoded before sending it to a server. Only for type="submit" (e.g. "application/x-www-form-urlencoded", "multipart/form-data" or "text/plain")');
        $this->registerTagAttribute('formmethod', 'string', 'Specifies how to send the form-data (which HTTP method to use). Only for type="submit" (e.g. "get" or "post")');
        $this->registerTagAttribute('formnovalidate', 'string', 'Specifies that the form-data should not be validated on submission. Only for type="submit"');
        $this->registerTagAttribute('formtarget', 'string', 'Specifies where to display the response after submitting the form. Only for type="submit" (e.g. "_blank", "_self", "_parent", "_top", "framename")');
        $this->registerTagAttribute('type', 'string', 'Specifies the type of button (e.g. "button", "reset" or "submit")', false, 'submit');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the button.
     *
     * @return string
     * @api
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->addAdditionalIdentityPropertiesIfNeeded();

        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValueAttribute(true));
        $this->tag->setContent($this->renderChildren());

        return $this->tag->render();
    }
}
