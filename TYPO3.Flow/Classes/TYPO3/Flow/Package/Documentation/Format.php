<?php
namespace TYPO3\Flow\Package\Documentation;

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
 * Documentation format of a documentation
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Format
{
    /**
     * @var string
     */
    protected $formatName;

    /**
     * Absolute path to the documentation format
     * @var string
     */
    protected $formatPath;

    /**
     * Constructor
     *
     * @param string $formatName Name of the documentation format
     * @param string $formatPath Absolute path to the documentation format
     */
    public function __construct($formatName, $formatPath)
    {
        $this->formatName = $formatName;
        $this->formatPath = $formatPath;
    }

    /**
     * Get the name of this documentation format
     *
     * @return string The name of this documentation format
     * @api
     */
    public function getFormatName()
    {
        return $this->formatName;
    }

    /**
     * Get the full path to the directory of this documentation format
     *
     * @return string Path to the directory of this documentation format
     * @api
     */
    public function getFormatPath()
    {
        return $this->formatPath;
    }

    /**
     * Returns the available languages for this documentation format
     *
     * @return array Array of string language codes
     * @api
     */
    public function getAvailableLanguages()
    {
        $languages = array();

        $languagesDirectoryIterator = new \DirectoryIterator($this->formatPath);
        $languagesDirectoryIterator->rewind();
        while ($languagesDirectoryIterator->valid()) {
            $filename = $languagesDirectoryIterator->getFilename();
            if ($filename[0] != '.' && $languagesDirectoryIterator->isDir()) {
                $language = $filename;
                $languages[] = $language;
            }
            $languagesDirectoryIterator->next();
        }

        return $languages;
    }
}
