<?php
namespace TYPO3\Fluid\ViewHelpers\Form;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * View Helper which creates a text field (<input type="text">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.textfield name="myTextBox" value="default value" />
 * </code>
 * <output>
 * <input type="text" name="myTextBox" value="default value" />
 * </output>
 *
 * @api
 */
class TextfieldViewHelper extends AbstractFormFieldViewHelper
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
        $this->registerTagAttribute('maxlength', 'int', 'The maxlength attribute of the input field (will not be validated)');
        $this->registerTagAttribute('readonly', 'string', 'The readonly attribute of the input field');
        $this->registerTagAttribute('size', 'int', 'The size of the input field');
        $this->registerTagAttribute('placeholder', 'string', 'The placeholder of the input field');
        $this->registerTagAttribute('autofocus', 'string', 'Specifies that a input field should automatically get focus when the page loads');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', false, 'f3-form-error');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the textfield.
     *
     * @param boolean $required If the field is required or not
     * @param string $type The field type, e.g. "text", "email", "url" etc.
     * @return string
     * @api
     */
    public function render($required = false, $type = 'text')
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $this->tag->addAttribute('type', $type);
        $this->tag->addAttribute('name', $name);

        $value = $this->getValue();

        if ($value !== null) {
            $this->tag->addAttribute('value', $value);
        }

        if ($required === true) {
            $this->tag->addAttribute('required', 'required');
        }

        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
