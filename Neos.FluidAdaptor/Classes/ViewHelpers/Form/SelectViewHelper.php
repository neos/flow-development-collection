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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\ObjectAccess;
use Neos\FluidAdaptor;
use Neos\FluidAdaptor\Core\ViewHelper;

/**
 * This ViewHelper generates a <select> dropdown list for the use with a form.
 *
 * **Basic usage**
 *
 * The most straightforward way is to supply an associative array as the "options" parameter.
 * The array key is used as option key, and the array value is used as human-readable name.
 *
 * To pre-select a value, set "value" to the option key which should be selected. If the select box is a multi-select
 * box (multiple="true"), then "value" can be an array as well.
 *
 * **Usage on domain objects**
 *
 * If you want to output domain objects, you can just pass them as array into the "options" parameter.
 * To define what domain object value should be used as option key, use the "optionValueField" variable. Same goes for optionLabelField.
 * If neither is given, the Identifier (UUID/uid) and the __toString() method are tried as fallbacks.
 *
 * If the optionValueField variable is set, the getter named after that value is used to retrieve the option key.
 * If the optionLabelField variable is set, the getter named after that value is used to retrieve the option value.
 *
 * If the prependOptionLabel variable is set, an option item is added in first position, bearing an empty string
 * or - if specified - the value of the prependOptionValue variable as value.
 *
 * In the example below, the userArray is an array of "User" domain objects, with no array key specified. Thus the
 * method $user->getId() is called to retrieve the key, and $user->getFirstName() to retrieve the displayed value of
 * each entry. The "value" property now expects a domain object, and tests for object equivalence.
 *
 * **Translation of select content**
 *
 * The ViewHelper can be given a "translate" argument with configuration on how to translate option labels.
 * The array can have the following keys:
 * - "by" defines if translation by message id or original label is to be used ("id" or "label")
 * - "using" defines if the option tag's "value" or "label" should be used as translation input, defaults to "value"
 * - "locale" defines the locale identifier to use, optional, defaults to current locale
 * - "source" defines the translation source name, optional, defaults to "Main"
 * - "package" defines the package key of the translation source, optional, defaults to current package
 * - "prefix" defines a prefix to use for the message id – only works in combination with "by id"
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" />
 * </code>
 * <output>
 * <select name="paymentOptions">
 *   <option value="payPal">PayPal International Services</option>
 *   <option value="visa">VISA Card</option>
 * </select>
 * </output>
 *
 * <code title="Preselect a default value">
 * <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" value="visa" />
 * </code>
 * <output>
 * (Generates a dropdown box like above, except that "VISA Card" is selected.)
 * </output>
 *
 * <code title="Use with domain objects">
 * <f:form.select name="users" options="{userArray}" optionValueField="id" optionLabelField="firstName" />
 * </code>
 * <output>
 * (Generates a dropdown box, using ids and first names of the User instances.)
 * </output>
 *
 * <code title="Prepend a fixed option">
 * <f:form.select property="salutation" options="{salutations}" prependOptionLabel="- select one -" />
 * </code>
 * <output>
 * <select name="salutation">
 *   <option value="">- select one -</option>
 *   <option value="Mr">Mr</option>
 *   <option value="Mrs">Mrs</option>
 *   <option value="Ms">Ms</option>
 * </select>
 * (depending on variable "salutations")
 * </output>
 *
 * <code title="Label translation">
 * <f:form.select name="paymentOption" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" translate="{by: 'id'}" />
 * </code>
 * <output>
 * (Generates a dropdown box and uses the values "payPal" and "visa" to look up
 * translations for those ids in the current package's "Main" XLIFF file.)
 * </output>
 *
 * <code title="Label translation usign a prefix">
 * <f:form.select name="paymentOption" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" translate="{by: 'id', prefix: 'shop.paymentOptions.'}" />
 * </code>
 * <output>
 * (Generates a dropdown box and uses the values "shop.paymentOptions.payPal"
 * and "shop.paymentOptions.visa" to look up translations for those ids in the
 * current package's "Main" XLIFF file.)
 * </output>
 *
 * @api
 */
class SelectViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var mixed
     */
    protected $selectedValue = null;

    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('multiple', 'string', 'if set, multiple select field');
        $this->registerTagAttribute('size', 'string', 'Size of input field');
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
        $this->registerArgument('options', 'array', 'Associative array with internal IDs as key, and the values are displayed in the select box', true);
        $this->registerArgument('optionValueField', 'string', 'If specified, will call the appropriate getter on each object to determine the value.');
        $this->registerArgument('optionLabelField', 'string', 'If specified, will call the appropriate getter on each object to determine the label.');
        $this->registerArgument('sortByOptionLabel', 'boolean', 'If true, List will be sorted by label.', false, false);
        $this->registerArgument('selectAllByDefault', 'boolean', 'If specified options are selected if none was set before.', false, false);
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument('translate', 'array', 'Configures translation of ViewHelper output.');
        $this->registerArgument('prependOptionLabel', 'string', 'If specified, will provide an option at first position with the specified label.');
        $this->registerArgument('prependOptionValue', 'string', 'If specified, will provide an option at first position with the specified value. This argument is only respected if prependOptionLabel is set.');
    }

    /**
     * Render the tag.
     *
     * @return string rendered tag.
     * @api
     */
    public function render()
    {
        $name = $this->getName();
        if ($this->hasArgument('multiple')) {
            $name .= '[]';
        }

        $this->tag->addAttribute('name', $name);

        $options = $this->getOptions();
        $this->tag->setContent($this->renderOptionTags($options));

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        // register field name for token generation.
        // in case it is a multi-select, we need to register the field name
        // as often as there are elements in the box
        if ($this->hasArgument('multiple') && $this->arguments['multiple'] !== '') {
            $this->renderHiddenFieldForEmptyValue();
            for ($i = 0; $i < count($options); $i++) {
                $this->registerFieldNameForFormTokenGeneration($name);
            }
        } else {
            $this->registerFieldNameForFormTokenGeneration($name);
        }

        return $this->tag->render();
    }

    /**
     * Render the option tags.
     *
     * @param array $options the options for the form.
     * @return string rendered tags.
     */
    protected function renderOptionTags($options)
    {
        $output = '';
        if ($this->hasArgument('prependOptionLabel')) {
            $value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
            if ($this->hasArgument('translate')) {
                $label = $this->getTranslatedLabel($value, $this->arguments['prependOptionLabel']);
            } else {
                $label = $this->arguments['prependOptionLabel'];
            }

            $output .= $this->renderOptionTag($value, $label) . chr(10);
        } elseif (empty($options)) {
            $options = array('' => '');
        }
        foreach ($options as $value => $label) {
            $output .= $this->renderOptionTag($value, $label) . chr(10);
        }
        return $output;
    }

    /**
     * Render the option tags.
     *
     * @return array an associative array of options, key will be the value of the option tag
     * @throws ViewHelper\Exception
     */
    protected function getOptions()
    {
        if (!is_array($this->arguments['options']) && !($this->arguments['options'] instanceof \Traversable)) {
            return array();
        }
        $options = array();
        foreach ($this->arguments['options'] as $key => $value) {
            if (is_object($value)) {
                if ($this->hasArgument('optionValueField')) {
                    $key = ObjectAccess::getPropertyPath($value, $this->arguments['optionValueField']);
                    if (is_object($key)) {
                        if (method_exists($key, '__toString')) {
                            $key = (string)$key;
                        } else {
                            throw new ViewHelper\Exception('Identifying value for object of class "' . get_class($value) . '" was an object.', 1247827428);
                        }
                    }
                } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                    $key = $this->persistenceManager->getIdentifierByObject($value);
                } elseif (method_exists($value, '__toString')) {
                    $key = (string)$value;
                } else {
                    throw new ViewHelper\Exception('No identifying value for object of class "' . get_class($value) . '" found.', 1247826696);
                }

                if ($this->hasArgument('optionLabelField')) {
                    $value = ObjectAccess::getPropertyPath($value, $this->arguments['optionLabelField']);
                    if (is_object($value)) {
                        if (method_exists($value, '__toString')) {
                            $value = (string)$value;
                        } else {
                            throw new ViewHelper\Exception('Label value for object of class "' . get_class($value) . '" was an object without a __toString() method.', 1247827553);
                        }
                    }
                } elseif (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                    $value = $this->persistenceManager->getIdentifierByObject($value);
                }
            }

            if ($this->hasArgument('translate')) {
                $value = $this->getTranslatedLabel($key, $value);
            }

            $options[$key] = $value;
        }
        if ($this->arguments['sortByOptionLabel']) {
            asort($options);
        }
        return $options;
    }

    /**
     * Render the option tags.
     *
     * @param mixed $value Value to check for
     * @return boolean TRUE if the value should be marked a s selected; FALSE otherwise
     */
    protected function isSelected($value)
    {
        $selectedValue = $this->getSelectedValue();
        if ($value === $selectedValue || (string)$value === $selectedValue) {
            return true;
        }
        if ($this->hasArgument('multiple')) {
            if ($selectedValue === null && $this->arguments['selectAllByDefault'] === true) {
                return true;
            } elseif (is_array($selectedValue) && in_array($value, $selectedValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieves the selected value(s)
     *
     * @return mixed value string or an array of strings
     */
    protected function getSelectedValue()
    {
        $value = $this->getValueAttribute();
        if (!is_array($value) && !($value instanceof \Traversable)) {
            return $this->getOptionValueScalar($value);
        }
        $selectedValues = array();
        foreach ($value as $selectedValueElement) {
            $selectedValues[] = $this->getOptionValueScalar($selectedValueElement);
        }
        return $selectedValues;
    }

    /**
     * Get the option value for an object
     *
     * @param mixed $valueElement
     * @return string
     */
    protected function getOptionValueScalar($valueElement)
    {
        if (is_object($valueElement)) {
            if ($this->hasArgument('optionValueField')) {
                return ObjectAccess::getPropertyPath($valueElement, $this->arguments['optionValueField']);
            } elseif ($this->persistenceManager->getIdentifierByObject($valueElement) !== null) {
                return $this->persistenceManager->getIdentifierByObject($valueElement);
            } else {
                return (string)$valueElement;
            }
        } else {
            return $valueElement;
        }
    }

    /**
     * Render one option tag
     *
     * @param string $value value attribute of the option tag (will be escaped)
     * @param string $label content of the option tag (will be escaped)
     * @return string the rendered option tag
     */
    protected function renderOptionTag($value, $label)
    {
        $output = '<option value="' . htmlspecialchars($value) . '"';
        if ($this->isSelected($value)) {
            $output .= ' selected="selected"';
        }

        $output .= '>' . htmlspecialchars($label) . '</option>';

        return $output;
    }

    /**
     * Returns a translated version of the given label
     *
     * @param string $value option tag value
     * @param string $label option tag label
     * @return string
     * @throws ViewHelper\Exception
     * @throws FluidAdaptor\Exception
     */
    protected function getTranslatedLabel($value, $label)
    {
        $translationConfiguration = $this->arguments['translate'];

        $translateBy = isset($translationConfiguration['by']) ? $translationConfiguration['by'] : 'id';
        $sourceName = isset($translationConfiguration['source']) ? $translationConfiguration['source'] : 'Main';
        $request = $this->controllerContext->getRequest();
        $packageKey = null;
        if (isset($translationConfiguration['package'])) {
            $packageKey = $translationConfiguration['package'];
        } elseif ($request instanceof ActionRequest) {
            $packageKey = $request->getControllerPackageKey();
        }
        $prefix = isset($translationConfiguration['prefix']) ? $translationConfiguration['prefix'] : '';

        if (isset($translationConfiguration['locale'])) {
            try {
                $localeObject = new Locale($translationConfiguration['locale']);
            } catch (InvalidLocaleIdentifierException $e) {
                throw new ViewHelper\Exception('"' . $translationConfiguration['locale'] . '" is not a valid locale identifier.', 1330013193);
            }
        } else {
            $localeObject = null;
        }

        switch ($translateBy) {
            case 'label':
                $label =  isset($translationConfiguration['using']) && $translationConfiguration['using'] === 'value' ? $value : $label;
                return $this->translator->translateByOriginalLabel($label, array(), null, $localeObject, $sourceName, $packageKey);
            case 'id':
                $id =  $prefix . (isset($translationConfiguration['using']) && $translationConfiguration['using'] === 'label' ? $label : $value);
                $translation = $this->translator->translateById($id, array(), null, $localeObject, $sourceName, $packageKey);
                return ($translation !== null) ? $translation : $label;
            default:
                throw new ViewHelper\Exception('You can only request to translate by "label" or by "id", but asked for "' . $translateBy . '" in your SelectViewHelper tag.', 1340050647);
        }
    }
}
