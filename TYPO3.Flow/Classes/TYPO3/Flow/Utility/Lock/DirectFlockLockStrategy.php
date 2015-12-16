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
use TYPO3\Flow\Utility\Exception\LockNotAcquiredException;

/**
 * A flock based lock strategy.
 *
 * This lock strategy is based on Flock and will directly lock the subject file.
 *
 * @Flow\Scope("prototype")
 */
class DirectFlockLockStrategy implements LockStrategyInterface
{
    /**
     * File name used for this lock
     *
     * @var string
     */
    protected $lockFilename;

    /**
     * File pointer if using flock method
     *
     * @var resource
     */
    protected $filePointer;

    /**
     * The subject under lock (usually a file path)
     *
     * @var string
     */
    protected $subject;

    /**
     * Is this lock an exclusive lock
     *
     * @var boolean
     */
    protected $exclusiveLock = false;

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock.
     * @return void
     * @throws LockNotAcquiredException
     */
    public function acquire($subject, $exclusiveLock)
    {
        $this->subject = $subject;
        $this->exclusiveLock = $exclusiveLock;
        $this->lockFilename = $this->determineLockFilename();

        $acquiredLock = false;
        $i = 0;
        while ($acquiredLock === false) {
            $acquiredLock = $this->tryToAcquireLock();
            $i++;
            if ($i > 10000) {
                throw new LockNotAcquiredException(sprintf('After 10000 attempts a lock could not be aquired for subject "%s".', $subject), 1449829188);
            }
        }
    }

    /**
     * Generates the filepath that is actually locked
     *
     * @return string
     */
    protected function determineLockFilename()
    {
        return $this->subject;
    }

    /**
     * Tries to open a lock file and apply the lock to it.
     *
     * @return boolean Was a lock aquired?
     * @throws LockNotAcquiredException
     */
    protected function tryToAcquireLock()
    {
        $this->filePointer = fopen($this->lockFilename, 'a+');
        if ($this->filePointer === false) {
            throw new LockNotAcquiredException(sprintf('Lock file "%s" could not be opened', $this->lockFilename), 1386520596);
        }

        $this->applyFlock();

        $fstat = fstat($this->filePointer);
        $stat = @stat($this->lockFilename);
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
     * @throws LockNotAcquiredException
     */
    protected function applyFlock()
    {
        $lockOption = $this->exclusiveLock === true ? LOCK_EX : LOCK_SH;

        if (flock($this->filePointer, $lockOption) !== true) {
            throw new LockNotAcquiredException(sprintf('Could not lock file "%s"', $this->lockFilename), 1386520597);
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
    public function getLockFilename()
    {
        return $this->lockFilename;
    }
}
