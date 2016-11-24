<?php
namespace Neos\Flow\Configuration\Source;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Symfony\Component\Yaml\Yaml;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Exception\ParseErrorException;
use Neos\Flow\Error\Exception;
use Neos\Utility\Arrays;

/**
 * Configuration source based on YAML files
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(FALSE)
 * @api
 */
class YamlSource
{
    /**
     * Will be set if the PHP YAML Extension is installed.
     * Having this installed massively improves YAML parsing performance.
     *
     * @var boolean
     * @see http://pecl.php.net/package/yaml
     */
    protected $usePhpYamlExtension = false;

    public function __construct()
    {
        if (extension_loaded('yaml')) {
            $this->usePhpYamlExtension = true;
        }
    }

    /**
     * Checks for the specified configuration file and returns TRUE if it exists.
     *
     * @param string $pathAndFilename Full path and filename of the file to load, excluding the file extension (ie. ".yaml")
     * @param boolean $allowSplitSource If TRUE, the type will be used as a prefix when looking for configuration files
     * @return boolean
     */
    public function has($pathAndFilename, $allowSplitSource = false)
    {
        if ($allowSplitSource === true) {
            $pathsAndFileNames = glob($pathAndFilename . '.*.yaml');
            if ($pathsAndFileNames !== false) {
                foreach ($pathsAndFileNames as $pathAndFilename) {
                    if (is_file($pathAndFilename)) {
                        return true;
                    }
                }
            }
        }
        if (is_file($pathAndFilename . '.yaml')) {
            return true;
        }
        return false;
    }

    /**
     * Loads the specified configuration file and returns its content as an
     * array. If the file does not exist or could not be loaded, an empty
     * array is returned
     *
     * @param string $pathAndFilename Full path and filename of the file to load, excluding the file extension (ie. ".yaml")
     * @param boolean $allowSplitSource If TRUE, the type will be used as a prefix when looking for configuration files
     * @return array
     * @throws ParseErrorException
     */
    public function load($pathAndFilename, $allowSplitSource = false)
    {
        $pathsAndFileNames = [$pathAndFilename . '.yaml'];
        if ($allowSplitSource === true) {
            $splitSourcePathsAndFileNames = glob($pathAndFilename . '.*.yaml');
            if ($splitSourcePathsAndFileNames !== false) {
                sort($splitSourcePathsAndFileNames);
                $pathsAndFileNames = array_merge($pathsAndFileNames, $splitSourcePathsAndFileNames);
            }
        }
        $configuration = [];
        foreach ($pathsAndFileNames as $pathAndFilename) {
            if (is_file($pathAndFilename)) {
                try {
                    if ($this->usePhpYamlExtension) {
                        if (strpos($pathAndFilename, 'resource://') === 0) {
                            $yaml = file_get_contents($pathAndFilename);
                            $loadedConfiguration = @yaml_parse($yaml);
                            unset($yaml);
                        } else {
                            $loadedConfiguration = @yaml_parse_file($pathAndFilename);
                        }
                        if ($loadedConfiguration === false) {
                            throw new ParseErrorException('A parse error occurred while parsing file "' . $pathAndFilename . '".', 1391894094);
                        }
                    } else {
                        $loadedConfiguration = Yaml::parse($pathAndFilename);
                    }

                    if (is_array($loadedConfiguration)) {
                        $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $loadedConfiguration);
                    }
                } catch (Exception $exception) {
                    throw new ParseErrorException('A parse error occurred while parsing file "' . $pathAndFilename . '". Error message: ' . $exception->getMessage(), 1232014321);
                }
            }
        }
        return $configuration;
    }

    /**
     * Save the specified configuration array to the given file in YAML format.
     *
     * @param string $pathAndFilename Full path and filename of the file to write to, excluding the dot and file extension (i.e. ".yaml")
     * @param array $configuration The configuration to save
     * @return void
     */
    public function save($pathAndFilename, array $configuration)
    {
        $header = '';
        if (file_exists($pathAndFilename . '.yaml')) {
            $header = $this->getHeaderFromFile($pathAndFilename . '.yaml');
        }
        $yaml = Yaml::dump($configuration, 99, 2);
        file_put_contents($pathAndFilename . '.yaml', $header . chr(10) . $yaml);
    }

    /**
     * Read the header part from the given file. That means, every line
     * until the first non comment line is found.
     *
     * @param string $pathAndFilename
     * @return string The header of the given YAML file
     */
    protected function getHeaderFromFile($pathAndFilename)
    {
        $header = '';
        $fileHandle = fopen($pathAndFilename, 'r');
        while ($line = fgets($fileHandle)) {
            if (preg_match('/^#/', $line)) {
                $header .= $line;
            } else {
                break;
            }
        }
        fclose($fileHandle);
        return $header;
    }
}
