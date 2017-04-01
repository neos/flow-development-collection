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
     * Returns translated label of $originalLabel from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $originalLabel Label used as a key in order to find translation
     * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationByOriginalLabel($originalLabel, \TYPO3\Flow\I18n\Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        $model = $this->getModel($packageKey, $sourceName, $locale);

        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!is_array($pluralFormsForProvidedLocale) || !in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
            }
            // We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
            $pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
        } else {
            $pluralFormIndex = 0;
        }

        return $model->getTargetBySource($originalLabel, $pluralFormIndex);
    }

    /**
     * Returns label for a key ($labelId) from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $labelId Key used to find translated label
     * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
     */
    public function getTranslationById($labelId, \TYPO3\Flow\I18n\Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        $model = $this->getModel($packageKey, $sourceName, $locale);

        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033387);
            }
            // We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
            $pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
        } else {
            $pluralFormIndex = 0;
        }

        return $model->getTargetByTransUnitId($labelId, $pluralFormIndex);
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
