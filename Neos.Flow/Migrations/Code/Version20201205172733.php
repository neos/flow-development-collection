<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Files;

/**
 * This migration does not actually change any code. It just displays a warning if a PHP file still refers to the no longer existing ComponentInterface
 */
class Version20201205172733 extends AbstractMigration
{
    public function getIdentifier(): string
    {
        return 'Neos.Flow-20201205172733';
    }

    public function up(): void
    {
        $affectedFiles = [];
        foreach (Files::getRecursiveDirectoryGenerator($this->targetPackageData['path'], '.php', true) as $pathAndFilename) {
            $fileContents = file_get_contents($pathAndFilename);
            if (preg_match('/(use|implements) Neos\\\Flow\\\Http\\\Component\\\ComponentInterface/', $fileContents) === 1) {
                $affectedFiles[] = substr($pathAndFilename, strlen($this->targetPackageData['path']) + 1);
            }
        }

        if ($affectedFiles !== array()) {
            $this->showWarning('Following files refer to the ComponentInterface that has been removed with Flow 7.0:' . PHP_EOL . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $affectedFiles) . PHP_EOL . PHP_EOL . 'The component chain was replaced with a middleware chain in Flow 7. Please make sure you have read the upgrade instructions and converted your components to middlewares.');
        }
    }
}
