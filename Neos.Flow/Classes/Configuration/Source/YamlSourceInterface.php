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
use Neos\Flow\Utility\Arrays;

/**
 * Configuration source based on YAML files
 *
 * @api
 */
interface YamlSourceInterface
{

    /**
     * Checks for the specified configuration file and returns TRUE if it exists.
     *
     * @param string $pathAndFilename Full path and filename of the file to load, excluding the file extension (ie. ".yaml")
     * @param boolean $allowSplitSource If TRUE, the type will be used as a prefix when looking for configuration files
     * @return boolean
     */
    public function has($pathAndFilename, $allowSplitSource = false);

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
    public function load($pathAndFilename, $allowSplitSource = false);

    /**
     * Save the specified configuration array to the given file in YAML format.
     *
     * @param string $pathAndFilename Full path and filename of the file to write to, excluding the dot and file extension (i.e. ".yaml")
     * @param array $configuration The configuration to save
     * @return void
     */
    public function save($pathAndFilename, array $configuration);

}
