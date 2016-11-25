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
 * View Helper which creates a simple radio button (<input type="radio">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.radio name="myRadioButton" value="someValue" />
 * </code>
 * <output>
 * <input type="radio" name="myRadioButton" value="someValue" />
 * </output>
 *
 * <code title="Preselect">
 * <f:form.radio name="myRadioButton" value="someValue" checked="{object.value} == 5" />
 * </code>
 * <output>
 * <input type="radio" name="myRadioButton" value="someValue" checked="checked" />
 * (depending on $object)
 * </output>
 *
 * <code title="Bind to object property">
 * <f:form.radio property="newsletter" value="1" /> yes
 * <f:form.radio property="newsletter" value="0" /> no
 * </code>
 * <output>
 * <input type="radio" name="user[newsletter]" value="1" checked="checked" /> yes
 * <input type="radio" name="user[newsletter]" value="0" /> no
 * (depending on property "newsletter")
 * </output>
 *
 * @api
 */
class RadioViewHelper extends AbstractFormFieldViewHelper
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
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', false, 'f3-form-error');
        $this->overrideArgument('value', 'string', 'Value of input tag. Required for radio buttons', true);
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the checkbox.
     *
     * @param boolean $checked Specifies that the input element should be preselected
     * @return string
     * @api
     */
    public function render($checked = null)
    {
        $this->tag->addAttribute('type', 'radio');

        $nameAttribute = $this->getName();
        $valueAttribute = $this->getValueAttribute(true);

        $propertyValue = null;
        if ($this->hasMappingErrorOccurred()) {
            $propertyValue = $this->getLastSubmittedFormData();
        }
        if ($checked === null && $propertyValue === null) {
            $propertyValue = $this->getPropertyValue();
        }

        if ($propertyValue !== null) {
            // no type-safe comparison by intention
            $checked = $propertyValue == $valueAttribute;
        }

        $this->registerFieldNameForFormTokenGeneration($nameAttribute);
        $this->tag->addAttribute('name', $nameAttribute);
        $this->tag->addAttribute('value', $valueAttribute);
        if ($checked === true) {
            $this->tag->addAttribute('checked', 'checked');
        }

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
