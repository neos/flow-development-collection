<?php
namespace TYPO3\Flow\Package;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Documentation for a package
 *
 * @api
 */
class Documentation
{
    /**
     * Reference to the package of this documentation
     * @var PackageInterface
     */
    protected $package;

    /**
     * @var string
     */
    protected $documentationName;

    /**
     * Absolute path to the documentation
     * @var string
     */
    protected $documentationPath;

    /**
     * Constructor
     *
     * @param PackageInterface $package Reference to the package of this documentation
     * @param string $documentationName Name of the documentation
     * @param string $documentationPath Absolute path to the documentation directory
     */
    public function __construct($package, $documentationName, $documentationPath)
    {
        $this->package = $package;
        $this->documentationName = $documentationName;
        $this->documentationPath = $documentationPath;
    }

    /**
     * Get the package of this documentation
     *
     * @return PackageInterface The package of this documentation
     * @api
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Get the name of this documentation
     *
     * @return string The name of this documentation
     * @api
     */
    public function getDocumentationName()
    {
        return $this->documentationName;
    }

    /**
     * Get the full path to the directory of this documentation
     *
     * @return string Path to the directory of this documentation
     * @api
     */
    public function getDocumentationPath()
    {
        return $this->documentationPath;
    }

    /**
     * Returns the available documentation formats for this documentation
     *
     * @return array<DocumentationFormat>
     * @api
     */
    public function getDocumentationFormats()
    {
        $documentationFormats = array();

        $documentationFormatsDirectoryIterator = new \DirectoryIterator($this->documentationPath);
        $documentationFormatsDirectoryIterator->rewind();
        while ($documentationFormatsDirectoryIterator->valid()) {
            $filename = $documentationFormatsDirectoryIterator->getFilename();
            if ($filename[0] != '.' && $documentationFormatsDirectoryIterator->isDir()) {
                $documentationFormat = new Documentation\Format($filename, $this->documentationPath . $filename . '/');
                $documentationFormats[$filename] = $documentationFormat;
            }
            $documentationFormatsDirectoryIterator->next();
        }

        return $documentationFormats;
    }
}
