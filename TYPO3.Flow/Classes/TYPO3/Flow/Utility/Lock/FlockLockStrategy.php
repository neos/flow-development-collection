<?php
namespace TYPO3\Flow\Utility\Lock;

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
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Utility\Environment;
use TYPO3\Flow\Utility\Exception\LockNotAcquiredException;
use TYPO3\Flow\Utility\Files;

/**
 * A flock based lock strategy.
 *
 * This lock strategy is based on Flock and will create an additional lock file.
 *
 * @Flow\Scope("prototype")
 */
class FlockLockStrategy extends DirectFlockLockStrategy implements LockStrategyInterface
{
    /**
     * @var string
     */
    protected static $temporaryDirectory;

    /**
     * Generates the filepath that is actually locked
     *
     * @return string
     */
    protected function determineLockFilename()
    {
        if (self::$temporaryDirectory === null) {
            $this->configureTemporaryDirectory();
        }

        return Files::concatenatePaths(array(self::$temporaryDirectory, md5($this->subject)));
    }

    /**
     * Sets the temporaryDirectory as static variable for the lock class.
     *
     * @throws LockNotAcquiredException
     * @throws \TYPO3\Flow\Utility\Exception
     * return void;
     */
    protected function configureTemporaryDirectory()
    {
        if (Bootstrap::$staticObjectManager === null || !Bootstrap::$staticObjectManager->isRegistered('TYPO3\Flow\Utility\Environment')) {
            throw new LockNotAcquiredException('Environment object could not be accessed', 1386680952);
        }
        $environment = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\Environment');
        $temporaryDirectory = Files::concatenatePaths(array($environment->getPathToTemporaryDirectory(), 'Lock'));
        Files::createDirectoryRecursively($temporaryDirectory);
        self::$temporaryDirectory = $temporaryDirectory;
    }
}
