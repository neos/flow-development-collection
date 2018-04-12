<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Files;

/**
 * Warn about removed ReflectionService dependency from AbstractViewHelper
 */
class Version20141121091700 extends AbstractMigration
{
    public function getIdentifier()
    {
        return 'TYPO3.Fluid-20141121091700';
    }

    public function up()
    {
        $affectedFiles = array();
        $allPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'], null, true);
        foreach ($allPathsAndFilenames as $pathAndFilename) {
            if (substr($pathAndFilename, -14) !== 'ViewHelper.php') {
                continue;
            }
            $fileContents = file_get_contents($pathAndFilename);
            if (preg_match('/\$this->reflectionService/', $fileContents) === 1) {
                $affectedFiles[] = substr($pathAndFilename, strlen($this->targetPackageData['path']) + 1);
            }
        }

        if ($affectedFiles !== array()) {
            $this->showWarning('Following ViewHelpers might use a removed ReflectionService dependency from AbstractViewHelper, please inject a ReflectionService instance yourself:' . PHP_EOL . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $affectedFiles));
        }
    }
}
