<?php

namespace Neos\FluidAdaptor\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\ActionRequest;
use Neos\FluidAdaptor\Core\ViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;

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
class TranslateViewHelper extends ViewHelper\AbstractViewHelper
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('id', 'string', 'Id to use for finding translation (trans-unit id in XLIFF)', false, null);
        $this->registerArgument('value', 'string', 'If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default', false, null);
        $this->registerArgument('arguments', 'array', 'Numerically indexed array of values to be inserted into placeholders', false, []);
        $this->registerArgument('source', 'string', 'Name of file with translations (use / as a directory separator)', false, 'Main');
        $this->registerArgument('package', 'string', 'Target package key. If not set, the current package key will be used', false, null);
        $this->registerArgument('quantity', 'mixed', 'A number to find plural form for (float or int), NULL to not use plural forms', false, null);
        $this->registerArgument('locale', 'string', 'An identifier of locale to use (NULL for use the default locale)', false, null);
    }


    /**
     * Renders the translated label.
     * Replaces all placeholders with corresponding values if they exist in the
     * translated label.
     *
     * @return string Translated label or source label / ID key
     * @throws ViewHelperException
     */
    public function render()
    {
        $id = $this->arguments['id'];
        $value = $this->arguments['value'];
        $arguments = $this->arguments['arguments'];
        $source = $this->arguments['source'];
        $package = $this->arguments['package'];
        $quantity  = $this->arguments['quantity'];
        $locale = $this->arguments['locale'];

        $localeObject = null;
        if ($locale !== null) {
            try {
                $localeObject = new Locale($locale);
            } catch (InvalidLocaleIdentifierException $e) {
                throw new ViewHelperException(sprintf('"%s" is not a valid locale identifier.', $locale), 1279815885);
            }
        }
        if ($package === null) {
            $request = $this->renderingContext->getControllerContext()->getRequest();
            if ($request instanceof ActionRequest) {
                $package = $request->getControllerPackageKey();
            }
            if (empty($package)) {
                throw new ViewHelperException(
                    'The current package key can\'t be resolved. Make sure to initialize the Fluid view with a proper ActionRequest and/or specify the "package" argument when using the f:translate ViewHelper',
                    1416832309
                );
            }
        }
        $originalLabel = $value ?? $this->renderChildren();

        if ($id === null) {
            return (string)$this->translator->translateByOriginalLabel($originalLabel, $arguments, $quantity, $localeObject, $source, $package);
        }

        $translation = $this->translator->translateById($id, $arguments, $quantity, $localeObject, $source, $package);
        if ($translation !== null) {
            return $translation;
        }
        if ($originalLabel !== null) {
            return $originalLabel;
        }
        return (string)$id;
    }

    /**
     * @param Translator $translator
     */
    public function injectTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }
}
