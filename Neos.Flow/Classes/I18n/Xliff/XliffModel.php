<?php
namespace Neos\Flow\I18n\Xliff;

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Log\LoggerInterface;

/**
 * A model representing data from one XLIFF file.
 *
 * Please note that plural forms for particular translation unit are accessed
 * with integer index (and not string like 'zero', 'one', 'many' etc). This is
 * because they are indexed such way in XLIFF files in order to not break tools'
 * support.
 *
 * There are very few XLIFF editors, but they are nice Gettext's .po editors
 * available. Gettext supports plural forms, but it indexes them using integer
 * numbers. Leaving it this way in .xlf files, makes it possible to easily convert
 * them to .po (e.g. using xliff2po from Translation Toolkit), edit with Poedit,
 * and convert back to .xlf without any information loss (using po2xliff).
 *
 * @see http://docs.oasis-open.org/xliff/v1.2/xliff-profile-po/xliff-profile-po-1.2-cd02.html#s.detailed_mapping.tu
 */
class XliffModel
{
    /**
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * Concrete XML parser which is set by more specific model extending this
     * class.
     *
     * @var XliffParser
     */
    protected $xmlParser;

    /**
     * Absolute path to the file which is represented by this class instance.
     *
     * @var string
     */
    protected $sourcePath;

    /**
     * @var Locale
     */
    protected $locale;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $i18nLogger;

    /**
     * Parsed data (structure depends on concrete model).
     *
     * @var array
     */
    protected $xmlParsedData;

    /**
     * @param string $sourcePath
     * @param Locale $locale The locale represented by the file
     */
    public function __construct($sourcePath, Locale $locale)
    {
        $this->sourcePath = $sourcePath;
        $this->locale = $locale;
    }

    /**
     * Injects the Flow_I18n_XmlModelCache cache
     *
     * @param VariableFrontend $cache
     * @return void
     */
    public function injectCache(VariableFrontend $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param XliffParser $parser
     * @return void
     */
    public function injectParser(XliffParser $parser)
    {
        $this->xmlParser = $parser;
    }

    /**
     * When it's called, XML file is parsed (using parser set in $xmlParser)
     * or cache is loaded, if available.
     *
     * @return void
     */
    public function initializeObject()
    {
        if ($this->cache->has(md5($this->sourcePath))) {
            $this->xmlParsedData = $this->cache->get(md5($this->sourcePath));
        } else {
            $this->xmlParsedData = $this->xmlParser->getParsedData($this->sourcePath);
            $this->cache->set(md5($this->sourcePath), $this->xmlParsedData);
        }
    }

    /**
     * Returns translated label ("target" tag in XLIFF) from source-target
     * pair where "source" tag equals to $source parameter.
     *
     * @param string $source Label in original language ("source" tag in XLIFF)
     * @param integer $pluralFormIndex Index of plural form to use (starts with 0)
     * @return mixed Translated label or FALSE on failure
     */
    public function getTargetBySource($source, $pluralFormIndex = 0)
    {
        if (!isset($this->xmlParsedData['translationUnits'])) {
            $this->i18nLogger->log(sprintf('No trans-unit elements were found in "%s". This is allowed per specification, but no translation can be applied then.', $this->sourcePath), LOG_DEBUG);
            return false;
        }
        foreach ($this->xmlParsedData['translationUnits'] as $translationUnit) {
            // $source is always singular (or only) form, so compare with index 0
            if (!isset($translationUnit[0]) || $translationUnit[0]['source'] !== $source) {
                continue;
            }

            if (count($translationUnit) <= $pluralFormIndex) {
                $this->i18nLogger->log('The plural form index "' . $pluralFormIndex . '" for the source translation "' . $source . '"  in ' . $this->sourcePath . ' is not available.', LOG_DEBUG);
                return false;
            }

            return $translationUnit[$pluralFormIndex]['target'] ?: false;
        }

        return false;
    }

    /**
     * Returns translated label ("target" tag in XLIFF) for the id given.
     * Id is compared with "id" attribute of "trans-unit" tag (see XLIFF
     * specification for details).
     *
     * @param string $transUnitId The "id" attribute of "trans-unit" tag in XLIFF
     * @param integer $pluralFormIndex Index of plural form to use (starts with 0)
     * @return mixed Translated label or FALSE on failure
     */
    public function getTargetByTransUnitId($transUnitId, $pluralFormIndex = 0)
    {
        if (!isset($this->xmlParsedData['translationUnits'][$transUnitId])) {
            $this->i18nLogger->log('No trans-unit element with the id "' . $transUnitId . '" was found in ' . $this->sourcePath . '. Either this translation has been removed or the id in the code or template referring to the translation is wrong.', LOG_DEBUG);
            return false;
        }

        if (!isset($this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex])) {
            $this->i18nLogger->log('The plural form index "' . $pluralFormIndex . '" for the trans-unit element with the id "' . $transUnitId . '" in ' . $this->sourcePath . ' is not available.', LOG_DEBUG);
            return false;
        }

        if ($this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex]['target']) {
            return $this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex]['target'];
        } elseif ($this->locale->getLanguage() === $this->xmlParsedData['sourceLocale']->getLanguage()) {
            return $this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex]['source'] ?: false;
        } else {
            $this->i18nLogger->log('The target translation was empty and the source translation language (' . $this->xmlParsedData['sourceLocale']->getLanguage() . ') does not match the current locale (' . $this->locale->getLanguage() . ') for the trans-unit element with the id "' . $transUnitId . '" in ' . $this->sourcePath, LOG_DEBUG);
            return false;
        }
    }
}
