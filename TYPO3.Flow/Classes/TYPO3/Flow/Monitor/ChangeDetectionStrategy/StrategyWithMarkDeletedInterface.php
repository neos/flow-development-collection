<?php
namespace TYPO3\Flow\Monitor\ChangeDetectionStrategy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Monitor\FileMonitor;

/**
 * Contract for a change detection strategy that allows the FileMonitor to mark a file deleted directly.
 *
 * @api
 */
interface StrategyWithMarkDeletedInterface
{
    /**
     * Notify the change strategy that this file was deleted and does not need to be tracked anymore.
     *
     * @param string $pathAndFilename
     * @return void
     */
    public function setFileDeleted($pathAndFilename);
}
