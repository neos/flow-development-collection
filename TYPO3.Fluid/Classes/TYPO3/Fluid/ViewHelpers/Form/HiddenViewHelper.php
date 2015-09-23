<?php
namespace TYPO3\Fluid\ViewHelpers\Form;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Renders an <input type="hidden" ...> tag.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.hidden name="myHiddenValue" value="42" />
 * </code>
 * <output>
 * <input type="hidden" name="myHiddenValue" value="42" />
 * </output>
 *
 * You can also use the "property" attribute if you have bound an object to the form.
 * See <f:form> for more documentation.
 *
 * @api
 */
class HiddenViewHelper extends AbstractFormFieldViewHelper
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
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the hidden field.
     *
     * @return string
     * @api
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $this->tag->addAttribute('type', 'hidden');
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValue());

        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
