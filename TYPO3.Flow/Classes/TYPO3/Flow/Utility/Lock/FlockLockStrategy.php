<?php
namespace TYPO3\Flow\Utility\Lock;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Utility\Exception\LockNotAcquiredException;
use TYPO3\Flow\Utility\Files;

/**
 * A flock based lock strategy.
 *
 * This lock strategy is based on Flock. This strategy is currently disabled on Windows.
 *
 * @Flow\Scope("prototype")
 */
class FlockLockStrategy implements LockStrategyInterface
{
    /**
     * @var string
     */
    protected static $temporaryDirectory;

    /**
     * Identifier used for this lock
     * @var string
     */
    protected $id;

    /**
     * File name used for this lock
     * @var string
     */
    protected $lockFileName;

    /**
     * File pointer if using flock method
     * @var resource
     */
    protected $filePointer;

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock.
     * @throws LockNotAcquiredException
     * @throws \TYPO3\Flow\Utility\Exception
     * @return void
     */
    public function acquire($subject, $exclusiveLock)
    {
        if ($this->isWindowsOS()) {
            return;
        }
        if (self::$temporaryDirectory === null) {
            if (Bootstrap::$staticObjectManager === null || !Bootstrap::$staticObjectManager->isRegistered(\TYPO3\Flow\Utility\Environment::class)) {
                throw new LockNotAcquiredException('Environment object could not be accessed', 1386680952);
            }
            $environment = Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Utility\Environment::class);
            $temporaryDirectory = Files::concatenatePaths(array($environment->getPathToTemporaryDirectory(), 'Lock'));
            Files::createDirectoryRecursively($temporaryDirectory);
            self::$temporaryDirectory = $temporaryDirectory;
        }
        $this->lockFileName = Files::concatenatePaths(array(self::$temporaryDirectory, md5($subject)));

        if (($this->filePointer = @fopen($this->lockFileName, 'r')) === false) {
            if (($this->filePointer = @fopen($this->lockFileName, 'w')) === false) {
                throw new LockNotAcquiredException(sprintf('Lock file "%s" could not be opened', $this->lockFileName), 1386520596);
            }
        }

        if ($exclusiveLock === false && flock($this->filePointer, LOCK_SH) === true) {
            // Shared lock acquired
        } elseif ($exclusiveLock === true && flock($this->filePointer, LOCK_EX) === true) {
            // Exclusive lock acquired
        } else {
            throw new LockNotAcquiredException(sprintf('Could not lock file "%s"', $this->lockFileName), 1386520597);
        }
    }

    /**
     * Releases the lock
     * @return boolean TRUE on success, FALSE otherwise
     */
    public function release()
    {
        $success = true;
        if ($this->isWindowsOS()) {
            return $success;
        }
        if (is_resource($this->filePointer)) {
            if (flock($this->filePointer, LOCK_UN) === false) {
                $success = false;
            }
            fclose($this->filePointer);
        }

        return $success;
    }

    /**
     * @return string
     */
    public function getLockFileName()
    {
        return $this->lockFileName;
    }

    /**
     * @return boolean
     */
    protected function isWindowsOS()
    {
        return strncasecmp(PHP_OS, 'WIN', 3) == 0;
    }
}
