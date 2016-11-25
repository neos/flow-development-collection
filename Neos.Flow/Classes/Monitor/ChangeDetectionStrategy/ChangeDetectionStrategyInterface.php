<?php
namespace Neos\Flow\Monitor\ChangeDetectionStrategy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Monitor\FileMonitor;

/**
 * Contract for a change detection strategy
 *
 * @api
 */
interface ChangeDetectionStrategyInterface
{
    const STATUS_UNCHANGED = 0;
    const STATUS_CREATED = 1;
    const STATUS_CHANGED = 2;
    const STATUS_DELETED = 3;

    /**
     * Checks if the specified file has changed
     *
     * @param string $pathAndFilename
     * @return integer One of the STATUS_* constants
     * @api
     */
    public function getFileStatus($pathAndFilename);

    /**
     * Creates a link to the file monitor using the strategy
     *
     * @param \Neos\Flow\Monitor\FileMonitor $fileMonitor
     * @return mixed
     */
    public function setFileMonitor(FileMonitor $fileMonitor);

    /**
     * Commit any necessary data, like the current modification time.
     *
     * @return void
     */
    public function shutdownObject();
}
