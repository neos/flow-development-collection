<?php
namespace Neos\Utility\Lock;

/*
 * This file is part of the Neos.Utility.Lock package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Utility;

/**
 * A flock based lock strategy.
 *
 * This lock strategy is based on Flock.
 *
 */
class FlockLockStrategy implements LockStrategyInterface
{

    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * Identifier used for this lock
     *
     * @var string
     */
    protected $id;

    /**
     * File name used for this lock
     *
     * @var string
     */
    protected $lockFileName;

    /**
     * File pointer if using flock method
     *
     * @var resource
     */
    protected $filePointer;

    /**
     * FlockLockStrategy constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['lockDirectory'])) {
            throw new \InvalidArgumentException('The FlockLockStrategy needs the "lockDirectory" options set and it was not.', 1454695086);
        }
        $this->configureLockDirectory($options['lockDirectory']);
    }

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock.
     * @return void
     * @throws LockNotAcquiredException
     */
    public function acquire($subject, $exclusiveLock)
    {
        $this->lockFileName = Utility\Files::concatenatePaths([$this->temporaryDirectory, md5($subject)]);
        $aquiredLock = false;
        $i = 0;
        while ($aquiredLock === false) {
            $aquiredLock = $this->tryToAcquireLock($exclusiveLock);
            $i++;
            if ($i > 10000) {
                throw new LockNotAcquiredException(sprintf('After 10000 attempts a lock could not be aquired for subject "%s".', $subject), 1449829188);
            }
        }
    }

    /**
     * Sets the temporaryDirectory as static variable for the lock class.
     *
     * @param string $lockDirectory
     * @throws LockNotAcquiredException
     * return void
     */
    protected function configureLockDirectory($lockDirectory)
    {
        Utility\Files::createDirectoryRecursively($lockDirectory);
        $this->temporaryDirectory = $lockDirectory;
    }

    /**
     * Tries to open a lock file and apply the lock to it.
     *
     * @param boolean $exclusiveLock
     * @return boolean Was a lock aquired?
     * @throws LockNotAcquiredException
     */
    protected function tryToAcquireLock($exclusiveLock)
    {
        $this->filePointer = @fopen($this->lockFileName, 'w');
        if ($this->filePointer === false) {
            throw new LockNotAcquiredException(sprintf('Lock file "%s" could not be opened', $this->lockFileName), 1386520596);
        }

        $this->applyFlock($exclusiveLock);

        $fstat = fstat($this->filePointer);
        $stat = @stat($this->lockFileName);
        // Make sure that the file did not get unlinked between the fopen and the actual flock
        // This will always be TRUE on windows, because 'ino' stat will always be 0, but unlink is not possible on opened files anyway
        if ($stat !== false && $stat['ino'] === $fstat['ino']) {
            return true;
        }

        flock($this->filePointer, LOCK_UN);
        fclose($this->filePointer);
        $this->filePointer = null;

        usleep(100 + rand(0, 100));
        return false;
    }

    /**
     * apply flock to the opened lock file.
     *
     * @param boolean $exclusiveLock
     * @throws LockNotAcquiredException
     */
    protected function applyFlock($exclusiveLock)
    {
        $lockOption = $exclusiveLock === true ? LOCK_EX : LOCK_SH;

        if (flock($this->filePointer, $lockOption) !== true) {
            throw new LockNotAcquiredException(sprintf('Could not lock file "%s"', $this->lockFileName), 1386520597);
        }
    }

    /**
     * Releases the lock
     *
     * @return boolean TRUE on success, FALSE otherwise
     */
    public function release()
    {
        $success = true;
        if (is_resource($this->filePointer)) {
            // FIXME: The lockfile should be unlocked at this point but this will again lead to race conditions,
            // so we need to find out how to do this in a safe way. Keeping the lock files is very inode intensive
            // and should therefore change ASAP.
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
}
