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

use Neos\Utility\TypeHandling;

/**
 * View Helper which creates a simple checkbox (<input type="checkbox">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.checkbox name="myCheckBox" value="someValue" />
 * </code>
 * <output>
 * <input type="checkbox" name="myCheckBox" value="someValue" />
 * </output>
 *
 * <code title="Preselect">
 * <f:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />
 * </code>
 * <output>
 * <input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
 * (depending on $object)
 * </output>
 *
 * <code title="Bind to object property">
 * <f:form.checkbox property="interests" value="TYPO3" />
 * </code>
 * <output>
 * <input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
 * (depending on property "interests")
 * </output>
 *
 * @api
 */
class CheckboxViewHelper extends AbstractFormFieldViewHelper
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
        $this->registerTagAttribute('disabled', 'boolean', 'Specifies that the input element should be disabled when the page loads', false, false);
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', false, 'f3-form-error');
        $this->registerArgument('checked', 'boolean', 'Specifies that the input element should be preselected', false, null);
        $this->registerArgument('multiple', 'boolean', 'Specifies whether this checkbox belongs to a multivalue (is part of a checkbox group)', false, null);
        $this->overrideArgument('value', 'mixed', 'Value of input tag. Required for checkboxes', true);
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the checkbox.
     *
     * @return string
     * @api
     */
    public function render()
    {
        $this->tag->addAttribute('type', 'checkbox');

        $checked = $this->arguments['checked'];
        $multiple = $this->arguments['multiple'];

        // if value was assigned an object, it's identifier will be returned
        $valueAttribute = $this->getValueAttribute(true);
        $propertyValue = null;
        if ($this->hasMappingErrorOccurred()) {
            $propertyValue = $this->getLastSubmittedFormData();
        }

        if ($checked === null && $propertyValue === null) {
            $propertyValue = $this->getPropertyValue();
        }

        if ($propertyValue instanceof \Traversable) {
            $propertyValue = iterator_to_array($propertyValue);
        }
        if (is_array($propertyValue)) {
            if ($checked === null) {
                $checked = false;
                foreach ($propertyValue as $value) {
                    if (TypeHandling::isSimpleType(TypeHandling::getTypeForValue($value))) {
                        $checked = $valueAttribute === $value;
                    } else {
                        // assume an entity
                        $checked = $valueAttribute === $this->persistenceManager->getIdentifierByObject($value);
                    }
                    if ($checked === true) {
                        break;
                    }
                }
            }
            $this->arguments['multiple'] = true;
        } elseif (!$multiple && $propertyValue !== null) {
            $checked = (boolean)$propertyValue === (boolean)$valueAttribute;
        }

        $nameAttribute = $this->getName();
        if (isset($this->arguments['multiple']) && $this->arguments['multiple'] === true) {
            $nameAttribute .= '[]';
        }

        $this->registerFieldNameForFormTokenGeneration($nameAttribute);
        $this->tag->addAttribute('name', $nameAttribute);
        $this->tag->addAttribute('value', $valueAttribute);
        if ($checked === true) {
            $this->tag->addAttribute('checked', '');
        }

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        $this->renderHiddenFieldForEmptyValue();
        return $this->tag->render();
    }
}
