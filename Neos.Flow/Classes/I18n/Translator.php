<?php
namespace Neos\Flow\I18n;

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
use Neos\Flow\I18n;

/**
 * A class for translating messages
 *
 * Messages (labels) can be translated in two modes:
 * - by original label: untranslated label is used as a key
 * - by ID: string identifier is used as a key (eg. user.noaccess)
 *
 * Correct plural form of translated message is returned when $quantity
 * parameter is provided to a method. Otherwise, or on failure just translated
 * version is returned (eg. when string is translated only to one form).
 *
 * When all fails, untranslated (original) string or ID is returned (depends on
 * translation method).
 *
 * Placeholders' resolving is done when needed (see FormatResolver class).
 *
 * Actual translating is done by injected TranslationProvider instance, so
 * storage format depends on concrete implementation.
 *
 * @Flow\Scope("singleton")
 * @api
 * @see FormatResolver
 * @see TranslationProvider\TranslationProviderInterface
 * @see Cldr\Reader\PluralsReader
 */
class Translator
{
    /**
     * @var I18n\Service
     */
    protected $localizationService;

    /**
     * @var TranslationProvider\TranslationProviderInterface
     */
    protected $translationProvider;

    /**
     * @var FormatResolver
     */
    protected $formatResolver;

    /**
     * @var Cldr\Reader\PluralsReader
     */
    protected $pluralsReader;

    /**
     * @param I18n\Service $localizationService
     * @return void
     */
    public function injectLocalizationService(I18n\Service $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * @param TranslationProvider\TranslationProviderInterface $translationProvider
     * @return void
     */
    public function injectTranslationProvider(TranslationProvider\TranslationProviderInterface $translationProvider)
    {
        $this->translationProvider = $translationProvider;
    }

    /**
     * @param FormatResolver $formatResolver
     * @return void
     */
    public function injectFormatResolver(FormatResolver $formatResolver)
    {
        $this->formatResolver = $formatResolver;
    }

    /**
     * @param Cldr\Reader\PluralsReader $pluralsReader
     * @return void
     */
    public function injectPluralsReader(Cldr\Reader\PluralsReader $pluralsReader)
    {
        $this->pluralsReader = $pluralsReader;
    }

    /**
     * Translates the message given as $originalLabel.
     *
     * Searches for a translation in the source as defined by $sourceName
     * (interpretation depends on concrete translation provider used).
     *
     * If any arguments are provided in the $arguments array, they will be inserted
     * to the translated string (in place of corresponding placeholders, with
     * format defined by these placeholders).
     *
     * If $quantity is provided, correct plural form for provided $locale will
     * be chosen and used to choose correct translation variant.
     *
     * If no $locale is provided, default system locale will be used.
     *
     * @param string $originalLabel Untranslated message
     * @param array $arguments An array of values to replace placeholders with
     * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param Locale $locale Locale to use (NULL for default one)
     * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
     * @param string $packageKey Key of the package containing the source file
     * @return string Translated $originalLabel or $originalLabel itself on failure
     * @api
     */
    public function translateByOriginalLabel($originalLabel, array $arguments = [], $quantity = null, Locale $locale = null, $sourceName = 'Main', $packageKey = 'Neos.Flow')
    {
        if ($locale === null) {
            $locale = $this->localizationService->getConfiguration()->getCurrentLocale();
        }
        $pluralForm = $this->getPluralForm($quantity, $locale);

        $translatedMessage = $this->translationProvider->getTranslationByOriginalLabel($originalLabel, $locale, $pluralForm, $sourceName, $packageKey);

        if ($translatedMessage === false) {
            $translatedMessage = $originalLabel;
        }

        if (!empty($arguments)) {
            $translatedMessage = $this->formatResolver->resolvePlaceholders($translatedMessage, $arguments, $locale);
        }

        return $translatedMessage;
    }

    /**
     * Returns translated string found under the $labelId.
     *
     * Searches for a translation in the source as defined by $sourceName
     * (interpretation depends on concrete translation provider used).
     *
     * If any arguments are provided in the $arguments array, they will be inserted
     * to the translated string (in place of corresponding placeholders, with
     * format defined by these placeholders).
     *
     * If $quantity is provided, correct plural form for provided $locale will
     * be chosen and used to choose correct translation variant.
     *
     * @param string $labelId Key to use for finding translation
     * @param array $arguments An array of values to replace placeholders with
     * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param Locale $locale Locale to use (NULL for default one)
     * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
     * @param string $packageKey Key of the package containing the source file
     * @return string Translated message or NULL on failure
     * @api
     * @see Translator::translateByOriginalLabel()
     */
    public function translateById($labelId, array $arguments = [], $quantity = null, Locale $locale = null, $sourceName = 'Main', $packageKey = 'Neos.Flow')
    {
        if ($locale === null) {
            $locale = $this->localizationService->getConfiguration()->getCurrentLocale();
        }
        $pluralForm = $this->getPluralForm($quantity, $locale);

        $translatedMessage = $this->translationProvider->getTranslationById($labelId, $locale, $pluralForm, $sourceName, $packageKey);
        if ($translatedMessage === false) {
            return null;
        }

        if (!empty($arguments)) {
            return $this->formatResolver->resolvePlaceholders($translatedMessage, $arguments, $locale);
        }
        return $translatedMessage;
    }

    /**
     * Get the plural form to be used.
     *
     * If $quantity is numeric and non-NULL, the plural form for provided $locale will be
     * chosen according to it.
     *
     * In all other cases, NULL is returned.
     *
     * @param mixed $quantity
     * @param Locale $locale
     * @return string
     */
    protected function getPluralForm($quantity, Locale $locale)
    {
        if (!is_numeric($quantity)) {
            return null;
        } else {
            return $this->pluralsReader->getPluralForm($quantity, $locale);
        }
    }
}
