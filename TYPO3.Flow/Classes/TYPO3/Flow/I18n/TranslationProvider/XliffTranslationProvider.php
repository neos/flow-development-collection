<?php
namespace TYPO3\Flow\I18n\TranslationProvider;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 * @Flow\Scope("singleton")
 */
class XliffTranslationProvider implements \TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface
{
    /**
     * An absolute path to the directory where translation files reside.
     *
     * @var string
     */
    protected $xliffBasePath = 'Private/Translations/';

    /**
     * @var \TYPO3\Flow\I18n\Service
     */
    protected $localizationService;

    /**
     * @var \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader
     */
    protected $pluralsReader;

    /**
     * A collection of models requested at least once in current request.
     *
     * This is an associative array with pairs as follow:
     * ['filename'] => $model,
     *
     * @var array<\TYPO3\Flow\I18n\Xliff\XliffModel>
     */
    protected $models;

    /**
     * @param \TYPO3\Flow\I18n\Service $localizationService
     * @return void
     */
    public function injectLocalizationService(\TYPO3\Flow\I18n\Service $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * @param \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader $pluralsReader
     * @return void
     */
    public function injectPluralsReader(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader $pluralsReader)
    {
        $this->pluralsReader = $pluralsReader;
    }

    /**
     * Returns translated $stringToTranslate from a file defined by $sourceName using the function $functionName of the XliffModel.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $functionName The name of the function in the XliffModel to get the translation from
     * @param string $stringToTranslate String passed to function in order to find translation
     * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
     * @param float|int|null $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    protected function getTranslationByFunction($functionName, $stringToTranslate, \TYPO3\Flow\I18n\Locale $locale = null, $quantity = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        if ($locale === null) {
            $locale = $this->localizationService->getConfiguration()->getCurrentLocale();
        }

        $translation = false;

        foreach ($this->localizationService->getLocaleChain($locale) as $localeInChain) {
            $model = $this->getModel($packageKey, $sourceName, $localeInChain);

            if ($quantity !== null) {
                $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($localeInChain);
                $pluralForm = $this->pluralsReader->getPluralForm($quantity, $localeInChain);

                // We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
                $pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
            } else {
                $pluralFormIndex = 0;
            }
            //if we find a valid translation, we don't have to search in the remaining locale chain
            if (($translation = $model->$functionName($stringToTranslate, $pluralFormIndex)) !== false) {
                break;
            }
        }

        return $translation;
    }

    /**
     * Returns translated label of $originalLabel from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $originalLabel Label used as a key in order to find translation
     * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
     * @param float|int|null $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     *
     * @return mixed Translated label or FALSE on failure
     * @throws \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByOriginalLabel($originalLabel, \TYPO3\Flow\I18n\Locale $locale = null, $quantity = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        return $this->getTranslationByFunction('getTargetBySource', $originalLabel, $locale, $quantity, $sourceName, $packageKey);
    }

    /**
     * Returns label for a key ($labelId) from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $labelId Key used to find translated label
     * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
     * @param float|int|null $quantity A number to find plural form for (float or int), NULL to not use plural forms
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationById($labelId, \TYPO3\Flow\I18n\Locale $locale = null, $quantity = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        return $this->getTranslationByFunction('getTargetByTransUnitId', $labelId, $locale, $quantity, $sourceName, $packageKey);
    }

    /**
     * Returns a XliffModel instance representing desired XLIFF file.
     *
     * Will return existing instance if a model for given $sourceName was already
     * requested before. Returns FALSE when $sourceName doesn't point to existing
     * file.
     *
     * @param string $packageKey Key of the package containing the source file
     * @param string $sourceName Relative path to existing CLDR file
     * @param \TYPO3\Flow\I18n\Locale $locale Locale object
     * @return \TYPO3\Flow\I18n\Xliff\XliffModel New or existing instance
     * @throws \TYPO3\Flow\I18n\Exception
     */
    protected function getModel($packageKey, $sourceName, \TYPO3\Flow\I18n\Locale $locale)
    {
        $sourcePath = \TYPO3\Flow\Utility\Files::concatenatePaths(array('resource://' . $packageKey, $this->xliffBasePath));
        list($sourcePath, $foundLocale) = $this->localizationService->getXliffFilenameAndPath($sourcePath, $sourceName, $locale);

        if ($sourcePath === false) {
            throw new \TYPO3\Flow\I18n\Exception('No XLIFF file is available for ' . $packageKey . '::' . $sourceName . '::' . $locale . ' in the locale chain.', 1334759591);
        }
        if (isset($this->models[$sourcePath])) {
            return $this->models[$sourcePath];
        }
        return $this->models[$sourcePath] = new \TYPO3\Flow\I18n\Xliff\XliffModel($sourcePath, $foundLocale);
    }
}
