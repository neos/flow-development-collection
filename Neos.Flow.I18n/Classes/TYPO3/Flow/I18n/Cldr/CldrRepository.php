<?php
namespace TYPO3\Flow\I18n\Cldr;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\I18n\Locale;

/**
 * The CldrRepository class
 *
 * CldrRepository manages CldrModel instances across the framework, so there is
 * only one instance of CldrModel for every unique CLDR data file or file group.
 *
 */
class CldrRepository
{
    /**
     * An absolute path to the directory where CLDR resides. It is changed only
     * in tests.
     *
     * @var string
     */
    protected $cldrBasePath;

    /**
     * An array of models requested at least once in current request.
     *
     * This is an associative array with pairs as follow:
     * ['path']['locale'] => $model,
     *
     * where 'path' is a file or directory path and 'locale' is a Locale object.
     * For models representing one CLDR file, the 'path' points to a file and
     * 'locale' is not used. For models representing few CLDR files connected
     * with hierarchical relation, 'path' points to a directory where files
     * reside and 'locale' is used to define which files are included in the
     * relation (e.g. for locale 'en_GB' files would be: root + en + en_GB).
     *
     * @var array<\TYPO3\Flow\I18n\Cldr\CldrModel>
     */
    protected $models;

    /**
     * Constructs the repository based on CLDR data  in the given path.
     *
     * @param string $cldrBasePath
     */
    public function __construct($cldrBasePath)
    {
        $this->cldrBasePath = $cldrBasePath;
    }

    /**
     * Returns an instance of CldrModel which represents CLDR file found under
     * specified path.
     *
     * Will return existing instance if a model for given $filename was already
     * requested before. Returns FALSE when $filename doesn't point to existing
     * file.
     *
     * @param string $filename Relative (from CLDR root) path to existing CLDR file
     * @return CldrModel|boolean A \TYPO3\Flow\I18n\Cldr\CldrModel instance or FALSE on failure
     */
    public function getModel($filename)
    {
        $filename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->cldrBasePath, $filename . '.xml'));

        if (isset($this->models[$filename])) {
            return $this->models[$filename];
        }

        if (!is_file($filename)) {
            return false;
        }

        return $this->models[$filename] = new CldrModel(array($filename));
    }

    /**
     * Returns an instance of CldrModel which represents group of CLDR files
     * which are related in hierarchy.
     *
     * This method finds a group of CLDR files within $directoryPath dir,
     * for particular Locale. Returned model represents whole locale-chain.
     *
     * For example, for locale en_GB, returned model could represent 'en_GB',
     * 'en', and 'root' CLDR files.
     *
     * Returns FALSE when $directoryPath doesn't point to existing directory.
     *
     * @param Locale $locale A locale
     * @param string $directoryPath Relative path to existing CLDR directory which contains one file per locale (see 'main' directory in CLDR for example)
     * @return CldrModel A \TYPO3\Flow\I18n\Cldr\CldrModel instance or NULL on failure
     */
    public function getModelForLocale(Locale $locale, $directoryPath = 'main')
    {
        $directoryPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->cldrBasePath, $directoryPath));

        if (isset($this->models[$directoryPath][(string)$locale])) {
            return $this->models[$directoryPath][(string)$locale];
        }

        if (!is_dir($directoryPath)) {
            return null;
        }

        $filesInHierarchy = $this->findLocaleChain($locale, $directoryPath);

        return $this->models[$directoryPath][(string)$locale] = new CldrModel($filesInHierarchy);
    }

    /**
     * Returns absolute paths to CLDR files connected in hierarchy
     *
     * For given locale, many CLDR files have to be merged in order to get full
     * set of CLDR data. For example, for 'en_GB' locale, files 'root', 'en',
     * and 'en_GB' should be merged.
     *
     * @param Locale $locale A locale
     * @param string $directoryPath Relative path to existing CLDR directory which contains one file per locale (see 'main' directory in CLDR for example)
     * @return array<string> Absolute paths to CLDR files in hierarchy
     */
    protected function findLocaleChain(Locale $locale, $directoryPath)
    {
        $filesInHierarchy = array(\TYPO3\Flow\Utility\Files::concatenatePaths(array($directoryPath, (string)$locale . '.xml')));

        $localeIdentifier = (string)$locale;
        while ($localeIdentifier = substr($localeIdentifier, 0, (int)strrpos($localeIdentifier, '_'))) {
            $possibleFilename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($directoryPath, $localeIdentifier . '.xml'));
            if (file_exists($possibleFilename)) {
                array_unshift($filesInHierarchy, $possibleFilename);
            }
        }
        array_unshift($filesInHierarchy, \TYPO3\Flow\Utility\Files::concatenatePaths(array($directoryPath, 'root.xml')));

        return $filesInHierarchy;
    }
}
