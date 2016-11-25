<?php
namespace Neos\Flow\I18n\EelHelper;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

/**
 * Translation helpers for Eel contexts
 */
class TranslationHelper implements ProtectedContextAwareInterface
{
    const I18N_LABEL_ID_PATTERN = '/^[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+:[a-z0-9.]+:.+$/i';

    /**
     * Get the translated value for an id or original label
     *
     * If only id is set and contains a translation shorthand string, translate
     * according to that shorthand
     *
     * In all other cases:
     *
     * Replace all placeholders with corresponding values if they exist in the
     * translated label.
     *
     * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
     * @param string $originalLabel The original translation value (the untranslated source string).
     * @param array $arguments Numerically indexed array of values to be inserted into placeholders
     * @param string $source Name of file with translations
     * @param string $package Target package key. If not set, the current package key will be used
     * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param string $locale An identifier of locale to use (NULL for use the default locale)
     * @return string Translated label or source label / ID key
     */
    public function translate($id, $originalLabel = null, array $arguments = [], $source = 'Main', $package = null, $quantity = null, $locale = null)
    {
        if (
            $originalLabel === null &&
            $arguments === [] &&
            $source === 'Main' &&
            $package === null &&
            $quantity === null &&
            $locale === null
        ) {
            return preg_match(self::I18N_LABEL_ID_PATTERN, $id) === 1 ? $this->translateByShortHandString($id) : $id;
        }

        return $this->translateByExplicitlyPassedOrderedArguments($id, $originalLabel, $arguments, $source, $package, $quantity, $locale);
    }

    /**
     * Start collection of parameters for translation by id
     *
     * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
     * @return TranslationParameterToken
     */
    public function id($id)
    {
        return $this->createTranslationParameterToken($id);
    }

    /**
     * Start collection of parameters for translation by original label
     *
     * @param string $value
     * @return TranslationParameterToken
     */
    public function value($value)
    {
        return $this->createTranslationParameterToken(null, $value);
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }


    /**
     * Get the translated value for an id or original label
     *
     * Replace all placeholders with corresponding values if they exist in the
     * translated label.
     *
     * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
     * @param string $originalLabel The original translation value (the untranslated source string).
     * @param array $arguments Numerically indexed array of values to be inserted into placeholders
     * @param string $source Name of file with translations
     * @param string $package Target package key. If not set, the current package key will be used
     * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param string $locale An identifier of locale to use (NULL for use the default locale)
     * @return string Translated label or source label / ID key
     */
    protected function translateByExplicitlyPassedOrderedArguments($id, $originalLabel = null, array $arguments = [], $source = 'Main', $package = null, $quantity = null, $locale = null)
    {
        $translationParameterToken = $this->createTranslationParameterToken($id);
        $translationParameterToken
            ->value($originalLabel)
            ->arguments($arguments)
            ->source($source)
            ->package($package)
            ->quantity($quantity);

        if ($locale !== null) {
            $translationParameterToken->locale($locale);
        }

        return $translationParameterToken->translate();
    }

    /**
     * Translate by shorthand string
     *
     * @param string $shortHandString (PackageKey:Source:trans-unit-id)
     * @return string Translated label or source label / ID key
     * @throws \InvalidArgumentException
     */
    protected function translateByShortHandString($shortHandString)
    {
        $shortHandStringParts = explode(':', $shortHandString);
        if (count($shortHandStringParts) === 3) {
            list($package, $source, $id) = $shortHandStringParts;
            return $this->createTranslationParameterToken($id)
                ->package($package)
                ->source(str_replace('.', '/', $source))
                ->translate();
        }

        throw new \InvalidArgumentException(sprintf('The translation shorthand string "%s" has the wrong format', $shortHandString), 1436865829);
    }

    /**
     * Create and return a TranslationParameterToken.
     *
     * @param string $id
     * @param string $originalLabel
     * @return TranslationParameterToken
     */
    protected function createTranslationParameterToken($id = null, $originalLabel = null)
    {
        return new TranslationParameterToken($id, $originalLabel);
    }
}
