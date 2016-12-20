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
use Neos\Flow\Utility\PhpAnalyzer;

/**
 * Warn about "escapeOutput" property for existing ViewHelpers to ensure backwards-compatibility
 *
 * Note: If an affected ViewHelper does not create HTML output, you should remove this property (or set it TRUE) in order to ensure sanitization of the output
 */
class Version20150214130800 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'TYPO3.Fluid-20150214130800';
    }

    /**
     * @return void
     */
    public function up()
    {
        $affectedViewHelperClassNames = array();
        $allPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'], '.php', true);
        foreach ($allPathsAndFilenames as $pathAndFilename) {
            if (substr($pathAndFilename, -14) !== 'ViewHelper.php') {
                continue;
            }
            $fileContents = file_get_contents($pathAndFilename);
            $className = (new PhpAnalyzer($fileContents))->extractFullyQualifiedClassName();
            if ($className === null) {
                $this->showWarning(sprintf('could not extract class name from file "%s"', $pathAndFilename));
                continue;
            }
            $affectedViewHelperClassNames[] = $className;
        }

        if ($affectedViewHelperClassNames !== array()) {
            $this->showWarning('Make sure that the "escapeOutput" property is correct for the following ViewHelpers:' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', $affectedViewHelperClassNames) . PHP_EOL . PHP_EOL . 'If an affected ViewHelper does not render HTML output, you should set this property TRUE in order to ensure sanitization of the output!');
        }
    }
}
