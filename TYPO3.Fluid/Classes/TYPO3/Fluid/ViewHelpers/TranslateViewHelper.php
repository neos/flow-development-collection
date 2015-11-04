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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;

/**
 * Returns translated message using source message or key ID.
 *
 * Also replaces all placeholders with formatted versions of provided values.
 *
 * = Examples =
 *
 * <code title="Translation by id">
 * <f:translate id="user.unregistered">Unregistered User</f:translate>
 * </code>
 * <output>
 * translation of label with the id "user.unregistered" and a fallback to "Unregistered User"
 * </output>
 *
 * <code title="Inline notation">
 * {f:translate(id: 'some.label.id', value: 'fallback result')}
 * </code>
 * <output>
 * translation of label with the id "some.label.id" and a fallback to "fallback result"
 * </output>
 *
 * <code title="Custom source and locale">
 * <f:translate id="some.label.id" source="LabelsCatalog" locale="de_DE"/>
 * </code>
 * <output>
 * translation from custom source "SomeLabelsCatalog" for locale "de_DE"
 * </output>
 *
 * <code title="Custom source from other package">
 * <f:translate id="some.label.id" source="LabelsCatalog" package="OtherPackage"/>
 * </code>
 * <output>
 * translation from custom source "LabelsCatalog" in "OtherPackage"
 * </output>
 *
 * <code title="Arguments">
 * <f:translate arguments="{0: 'foo', 1: '99.9'}"><![CDATA[Untranslated {0} and {1,number}]]></f:translate>
 * </code>
 * <output>
 * translation of the label "Untranslated foo and 99.9"
 * </output>
 *
 * <code title="Translation by label">
 * <f:translate>Untranslated label</f:translate>
 * </code>
 * <output>
 * translation of the label "Untranslated label"
 * </output>
 *
 */
class TranslateViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * Renders the translated label.

     * Replaces all placeholders with corresponding values if they exist in the
     * translated label.
     *
     * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
     * @param string $value If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default
     * @param array $arguments Numerically indexed array of values to be inserted into placeholders
     * @param string $source Name of file with translations
     * @param string $package Target package key. If not set, the current package key will be used
     * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param string $locale An identifier of locale to use (NULL for use the default locale)
     * @return string Translated label or source label / ID key
     * @throws ViewHelperException
     */
    public function render($id = null, $value = null, array $arguments = array(), $source = 'Main', $package = null, $quantity = null, $locale = null)
    {
        $localeObject = null;
        if ($locale !== null) {
            try {
                $localeObject = new Locale($locale);
            } catch (InvalidLocaleIdentifierException $e) {
                throw new ViewHelperException(sprintf('"%s" is not a valid locale identifier.', $locale), 1279815885);
            }
        }
        if ($package === null) {
            $package = $this->controllerContext->getRequest()->getControllerPackageKey();
            if ($package === null) {
                throw new ViewHelperException('The current package key can\'t be resolved. Make sure to initialize the Fluid view with a proper ActionRequest and/or specify the "package" argument when using the f:translate ViewHelper', 1416832309);
            }
        }
        $originalLabel = $value === null ? $this->renderChildren() : $value;

        if ($id === null) {
            return $this->translator->translateByOriginalLabel($originalLabel, $arguments, $quantity, $localeObject, $source, $package);
        } else {
            $translation = $this->translator->translateById($id, $arguments, $quantity, $localeObject, $source, $package);
            if ($translation === $id) {
                if ($originalLabel) {
                    return $this->translator->translateByOriginalLabel($originalLabel, $arguments, $quantity, $localeObject, $source, $package);
                } else {
                    return $id;
                }
            } else {
                return $translation;
            }
        }
    }
}
