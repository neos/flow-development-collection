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

/**
 * An abstract class for all concrete classes that parses any kind of XML data.
 *
 * @Flow\Scope("singleton")
 */
abstract class AbstractXmlParser
{
    /**
     * Associative array of "filename => parsed data" pairs.
     *
     * @var array
     */
    protected $parsedFiles;

    /**
     * Returns parsed representation of XML file.
     *
     * Parses XML if it wasn't done before. Caches parsed data.
     *
     * @param string $sourcePath An absolute path to XML file
     * @return array Parsed XML file
     */
    public function getParsedData($sourcePath)
    {
        if (!isset($this->parsedFiles[$sourcePath])) {
            $this->parsedFiles[$sourcePath] = $this->parseXmlFile($sourcePath);
        }
        return $this->parsedFiles[$sourcePath];
    }

    /**
     * Reads and parses XML file and returns internal representation of data.
     *
     * @param string $sourcePath An absolute path to XML file
     * @return array Parsed XML file
     * @throws Exception\InvalidXmlFileException When SimpleXML couldn't load XML file
     */
    protected function parseXmlFile($sourcePath)
    {
        if (!file_exists($sourcePath)) {
            throw new Exception\InvalidXmlFileException('The path "' . $sourcePath . '" does not point to an existing and accessible XML file.', 1328879703);
        }
        libxml_use_internal_errors(true);
        $rootXmlNode = simplexml_load_file($sourcePath, 'SimpleXmlElement', \LIBXML_NOWARNING);
        if ($rootXmlNode === false) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errorMessage = trim($error->message) . ' (line ' . $error->line . ', column ' . $error->column;
                if ($error->file) {
                    $errorMessage .= ' in ' . $error->file;
                }
                $errors[] = $errorMessage . ')';
            }
            throw new Exception\InvalidXmlFileException('Parsing the XML file failed. These error were reported:' . PHP_EOL . implode(PHP_EOL, $errors), 1278155987);
        }

        return $this->doParsingFromRoot($rootXmlNode);
    }

    /**
     * Returns array representation of XML data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing parsed XML file (structure depends on concrete parser)
     */
    abstract protected function doParsingFromRoot(\SimpleXMLElement $root);
}
