<?php
namespace TYPO3\Flow\Utility\Lock;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Utility\Exception\LockNotAcquiredException;
use TYPO3\Flow\Utility\Files;

/**
 * A flock based lock strategy.
 *
 * This lock strategy is based on Flock.
 *
 * @Flow\Scope("prototype")
 */
class FlockLockStrategy implements LockStrategyInterface {

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
	public function acquire($subject, $exclusiveLock) {
		if (self::$temporaryDirectory === NULL) {
			if (Bootstrap::$staticObjectManager === NULL || !Bootstrap::$staticObjectManager->isRegistered(\TYPO3\Flow\Utility\Environment::class)) {
				throw new LockNotAcquiredException('Environment object could not be accessed', 1386680952);
			}
			$environment = Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Utility\Environment::class);
			$temporaryDirectory = Files::concatenatePaths(array($environment->getPathToTemporaryDirectory(), 'Lock'));
			Files::createDirectoryRecursively($temporaryDirectory);
			self::$temporaryDirectory = $temporaryDirectory;
		}
		$this->lockFileName = Files::concatenatePaths(array(self::$temporaryDirectory, md5($subject)));

		while (1) {
			if (($this->filePointer = @fopen($this->lockFileName, 'r')) === FALSE) {
				if (($this->filePointer = @fopen($this->lockFileName, 'w')) === FALSE) {
					throw new LockNotAcquiredException(sprintf('Lock file "%s" could not be opened', $this->lockFileName), 1386520596);
				}
			}

			if ($exclusiveLock === FALSE && flock($this->filePointer, LOCK_SH) === TRUE) {
				// Shared lock acquired
			} elseif ($exclusiveLock === TRUE && flock($this->filePointer, LOCK_EX) === TRUE) {
				// Exclusive lock acquired
			} else {
				throw new LockNotAcquiredException(sprintf('Could not lock file "%s"', $this->lockFileName), 1386520597);
			}

			$fstat = fstat($this->filePointer);
			$stat = stat($this->lockFileName);
			// Make sure that the file did not get unlinked between the fopen and the actual flock
			// This will always be TRUE on windows, because 'ino' stat will always be 0, but unlink is not possible on opened files anyway
			if ($stat !== FALSE && $stat['ino'] === $fstat['ino']) break;

			flock($this->filePointer, LOCK_UN);
			fclose($this->filePointer);
			$this->filePointer = NULL;
			usleep(100 + rand(0,100));
		}
	}

	/**
	 * Releases the lock
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public function release() {
		$success = TRUE;
		if (is_resource($this->filePointer)) {
			if (flock($this->filePointer, LOCK_UN) === FALSE) {
				$success = FALSE;
			}
			fclose($this->filePointer);
			Files::unlink($this->lockFileName);
		}

		return $success;
	}

	/**
	 * @return string
	 */
	public function getLockFileName() {
		return $this->lockFileName;
	}
}
