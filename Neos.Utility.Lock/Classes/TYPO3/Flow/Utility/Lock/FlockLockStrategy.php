<?php
namespace TYPO3\Flow\Utility\Lock;

/*
 * This file is part of the Neos.Utility.Lock package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use malkusch\lock\mutex\FlockMutex;
use malkusch\lock\util\DoubleCheckedLocking;
use TYPO3\Flow\Utility\Files;

/**
 * A flock based lock strategy.
 *
 * This lock strategy is based on Flock.
 */
class FlockLockStrategy implements LockStrategyInterface
{
    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * FlockLockStrategy constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['temporaryDirectory'])) {
            throw new \InvalidArgumentException('The FlockLockStrategy needs the "lockDirtemporaryDirectoryectory" options set and it was not.', 1454695086);
        }
        $this->temporaryDirectory = Files::concatenatePaths([$options['temporaryDirectory'], 'Lock']);
        Files::createDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return mixed
     */
    public function synchronized($subject, \Closure $callback)
    {
        $lockFileName = Files::concatenatePaths([$this->temporaryDirectory, md5($subject)]);
        $mutex = new FlockMutex(fopen($lockFileName, 'w'));
        return $mutex->synchronized($callback);
    }

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return DoubleCheckedLocking
     */
    public function check($subject, \Closure $callback)
    {
        $lockFileName = Files::concatenatePaths([$this->temporaryDirectory, md5($subject)]);
        $mutex = new FlockMutex(fopen($lockFileName, 'w'));
        return $mutex->check($callback);
    }
}
