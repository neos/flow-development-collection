<?php

namespace TYPO3\Flow\Tests\Functional\Configuration\Fixtures;

use TYPO3\Flow\Annotations as Flow;

class RootDirectoryIgnoringYamlSource extends \TYPO3\Flow\Configuration\Source\YamlSource
{
    /**
     * Loads the specified configuration file and returns its content as an
     * array. If the file does not exist or could not be loaded, an empty
     * array is returned
     *
     * @param string $pathAndFilename Full path and filename of the file to load, excluding the file extension (ie. ".yaml")
     * @param boolean $allowSplitSource If TRUE, the type will be used as a prefix when looking for configuration files
     * @return array
     * @throws \TYPO3\Flow\Configuration\Exception\ParseErrorException
     */

    public function load($pathAndFilename, $allowSplitSource = false)
    {
        if (strpos($pathAndFilename, FLOW_PATH_CONFIGURATION) === 0) {
            return [];
        } else {
            return parent::load($pathAndFilename, $allowSplitSource);
        }
    }
}
