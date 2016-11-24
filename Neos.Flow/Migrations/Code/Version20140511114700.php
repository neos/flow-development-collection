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
 * This migration does not actually change any code. It just displays a warning if a TypeConverters canConvertFrom() is
 * overridden in custom code.
 */
class Version20140511114700 extends AbstractMigration
{
    /**
     * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
     * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
     * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'TYPO3.Flow-201405111147';
    }

    /**
     * @return void
     */
    public function up()
    {
        $affectedFiles = array();
        foreach (Files::getRecursiveDirectoryGenerator($this->targetPackageData['path'], null, true) as $pathAndFilename) {
            if (substr($pathAndFilename, -13) !== 'Converter.php') {
                continue;
            }
            $fileContents = file_get_contents($pathAndFilename);
            if (preg_match('/public\s+function\s+canConvertFrom\s*\(/', $fileContents) === 1) {
                $affectedFiles[] = substr($pathAndFilename, strlen($this->targetPackageData['path']) + 1);
            }
        }

        if ($affectedFiles !== array()) {
            $this->showWarning('Following TypeConverters implement the canConvertFrom() method. The element type of the $targetType argument is no longer cut off, so it might be "array<Some/Element/Type>" instead of just "array" for example. Make sure that this is not an issue or add' . PHP_EOL . '  $targetType = TypeHandling::truncateElementType($targetType);' . PHP_EOL . 'to the beginning of this method body if you\'re not sure:' . PHP_EOL . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $affectedFiles));
        }
    }
}
