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

use TYPO3\Flow\I18n\Cldr\Reader\PluralsReader;
use TYPO3\Flow\I18n\Exception;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Service;
use TYPO3\Flow\I18n\Xliff\XliffModel;

/**
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 */
class XliffTranslationProvider implements TranslationProviderInterface
{
    /**
     * An absolute path to the directory where translation files reside.
     *
     * @var string
     */
    protected $xliffBasePath = 'Private/Translations/';

    /**
     * @var Service
     */
    protected $localizationService;

    /**
     * @var PluralsReader
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
     * @param Service $localizationService
     * @return void
     */
    public function injectLocalizationService(Service $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * @param PluralsReader $pluralsReader
     * @return void
     */
    public function injectPluralsReader(PluralsReader $pluralsReader)
    {
        $this->pluralsReader = $pluralsReader;
    }

    /**
     * Returns translated label of $originalLabel from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $originalLabel Label used as a key in order to find translation
     * @param Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws Exception\InvalidPluralFormException
     */
    public function getTranslationByOriginalLabel($originalLabel, Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        $model = $this->getModel($packageKey, $sourceName, $locale);

        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!is_array($pluralFormsForProvidedLocale) || !in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
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
     * @param Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws Exception\InvalidPluralFormException
     */
    public function getTranslationById($labelId, Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        $model = $this->getModel($packageKey, $sourceName, $locale);

        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033387);
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
     * @param Locale $locale Locale object
     * @return XliffModel New or existing instance
     * @throws Exception
     */
    protected function getModel($packageKey, $sourceName, Locale $locale)
    {
        $sourcePath = \TYPO3\Flow\Utility\Files::concatenatePaths(array('resource://' . $packageKey, $this->xliffBasePath));
        list($sourcePath, $foundLocale) = $this->localizationService->getXliffFilenameAndPath($sourcePath, $sourceName, $locale);

        if ($sourcePath === false) {
            throw new Exception('No XLIFF file is available for ' . $packageKey . '::' . $sourceName . '::' . $locale . ' in the locale chain.', 1334759591);
        }
        if (isset($this->models[$sourcePath])) {
            return $this->models[$sourcePath];
        }
        return $this->models[$sourcePath] = new XliffModel($sourcePath, $foundLocale);
    }
}
